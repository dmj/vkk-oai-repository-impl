<?php

/**
 * This file is part of HAB VKK Admin NG.
 *
 * HAB VKK Admin NG is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * HAB VKK Admin NG is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with HAB VKK Admin NG.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    David Maus <maus@hab.de>
 * @copyright (c) 2016 by Herzog August Bibliothek Wolfenbüttel
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License v3 or higher
 */

namespace HAB\VKK\Service\Export\Serializer;

use HAB\VKK\Entity\Blatt;

use DOMText;
use DOMElement;
use DOMDocument;

/**
 * Serialize blatt entity to Dublin Core™.
 *
 * @author    David Maus <maus@hab.de>
 * @copyright (c) 2016 by Herzog August Bibliothek Wolfenbüttel
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License v3 or higher
 */
class DublinCore implements SerializerInterface
{
    public static $elements = array(
        'creator',
        'contributor',
        'date',
        'description',
        'format',
        'identifier',
        'relation',
        'source',
        'subject',
        'title',
        'type',
    );

    public function serialize (Blatt $blatt)
    {
        $document = new DOMDocument();
        $root = $document->appendChild(
            $document->createElementNS('http://www.openarchives.org/OAI/2.0/oai_dc/', 'oai_dc:dc')
        );
        $root->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:schemaLocation', 'http://purl.org/dc/elements/1.1/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd');
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:dc', 'http://purl.org/dc/elements/1.1/');

        foreach (self::$elements as $name) {
            foreach (call_user_func(array($this, $name), $blatt) as $value) {
                $root
                    ->appendChild($document->createElement("dc:{$name}"))
                    ->appendChild(new DOMText($value));
            }
        }

        return $document->saveXml($root);
    }

    public function title (Blatt $blatt)
    {
        $titles = array();
        if ($title = $blatt->getTitel()) {
            $titles []= $title;
        }
        return $titles;
    }

    public function format (Blatt $blatt)
    {
        $formats = array();
        if ($format = $blatt->getObjektstatus()) {
            $formats []= $format;
        }
        foreach ($blatt->getTechnik() as $format) {
            $formats []= $format->getText();
        }
        if ($format = $blatt->getTechnikFreitext()) {
            $formats []= $format;
        }
        foreach ($blatt->getDrucktechnik() as $format) {
            $formats []= $format->getText();
        }
        if ($format = $blatt->getTraeger()) {
            $formats []= $format;
        }
        if ($format = $blatt->getTraegerFreitext()) {
            $formats []= $format;
        }
        return $formats;
    }

    public function identifier (Blatt $blatt)
    {
        $identifiers = array();
        $identifiers []= sprintf('%s, %s', $blatt->getInstitutionLabel(), $blatt->getSignatur());
        return $identifiers;
    }

    public function subject (Blatt $blatt)
    {
        $subjects = array();

        foreach ($blatt->getClassifications() as $subject) {
            $subjects []= sprintf('(iconclass)%s', $subject->getNotation());
        }

        foreach ($blatt->getSchlagwort() as $subject) {
            $subjects []= $subject->getText();
        }

        return $subjects;
    }

    public function date (Blatt $blatt)
    {
        $dates = array();
        $dates []= $blatt->getDatierung();
        return $dates;
    }

    public function type (Blatt $blatt)
    {
        $types = array('StillImage');
        return $types;
    }

    public function description (Blatt $blatt)
    {
        $descriptions = array();
        if ($description = $blatt->getAnmerkungen()) {
            $descriptions []= $description;
        }
        return $descriptions;
    }

    public function source (Blatt $blatt)
    {
        $sources = array();
        if ($source = $blatt->getVorlage()) {
            if ($blatt->getVorlagestandort()) {
                $source .= ', ' . $blatt->getVorlagestandort();
            }
            $sources []= $source;
        }
        return $sources;
    }

    public function relation (Blatt $blatt)
    {
        $relations = array();
        if ($relation = $blatt->getAusstellung()) {
            $relations []= $relation;
        }
        return $relations;
    }

    public function contributor (Blatt $blatt)
    {
        $contributors = array();
        foreach ($blatt->getPersonrolle() as $rolle) {
            if ($rolle->getFunktion()->getAnzeige() === 'Assoziierte Person') {

                $contributor  = $rolle->getPerson()->getText();
                $contributor .= ' (';
                $contributor .= $rolle->getFunktion();
                if ($rolle->getQualifier()) {
                    $contributor .= ', ' . $rolle->getQualifier();
                }
                $contributor .= ')';
                $contributors []= $contributor;
            }
        }
        return $contributors;
    }

    public function creator (Blatt $blatt)
    {
        $creators = array();

        foreach ($blatt->getPersonrolle() as $rolle) {
            if ($rolle->getFunktion()->getAnzeige() === 'Künstler/Hersteller') {
                $creator  = $rolle->getPerson()->getText();
                $creator .= ' (';
                $creator .= $rolle->getFunktion();
                if ($rolle->getQualifier()) {
                    $creator .= ', ' . $rolle->getQualifier();
                }
                $creator .= ')';
                $creators []= $creator;
            }
        }
        return $creators;
    }

}