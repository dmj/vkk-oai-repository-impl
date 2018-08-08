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
 * @copyright (c) 2018 by Herzog August Bibliothek Wolfenbüttel
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License v3 or higher
 */

namespace HAB\VKK\Service\Export\Serializer;

use HAB\VKK\Entity;
use HAB\VKK\Entity\Blatt;

use XMLWriter;

/**
 * Serialize blatt entity to internal format.
 *
 * @author    David Maus <maus@hab.de>
 * @copyright (c) 2018 by Herzog August Bibliothek Wolfenbüttel
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License v3 or higher
 */
class Internal implements SerializerInterface
{
    private $writer;
    public function __construct ()
    {
        $this->writer = new XMLWriter();
    }

    public function serialize (Blatt $blatt)
    {
        $this->writer->openMemory();
        $this->writer->startDocument();
        $this->writer->startElement('blatt');

        $this->literal('id', $blatt->getId());
        $this->literal('signatur', $blatt->getSignatur());
        $this->literal('normsignatur', $blatt->getNormSignatur());
        $this->literal('institution', $blatt->getInstitutionLabelShort());
        $this->literal('inventarnummer', $blatt->getAlteInvnr());
        $this->literal('objektstatus', $blatt->getObjektstatus());

        $this->literal('objekttyp', $blatt->isZeichnung() ? 'Zeichnung' : 'Druckgraphik');
        $this->literal('eigentum', $blatt->getEigentum());
        $this->literal('ausstellung', $blatt->getAusstellung());

        $this->literal('schule', $blatt->getSchule());
        $this->literal('kulturelle_einordnung', $blatt->getKultur());
        $this->literal('manuskriptnotiz', $blatt->getManuskriptnotiz());
        $this->literal('sammelband', $blatt->getSammelband());

        $this->literal('titel', $blatt->getTitel());
        $this->literal('technik_freitext', $blatt->getTechnikFreitext());
        $this->literal('träger', $blatt->getTraeger());
        $this->literal('träger_freitext', $blatt->getTraegerFreitext());
        $this->literal('inschrift', $blatt->getInschrift());
        $this->literal('bezeichnung', $blatt->getBezeichnung());
        $this->literal('vorlage', $blatt->getVorlage());
        $this->literal('vorlagestandort', $blatt->getVorlagestandort());
        $this->literal('wasserzeichen', $blatt->getWasserzeichen());
        $this->literal('anmerkungen', $blatt->getAnmerkungen());
        $this->literal('kommentar', $blatt->getKommentar());

        $this->writer->startElement('iconclass');
        $this->writer->writeAttribute('n', count($blatt->getClassifications()));
        foreach ($blatt->getClassifications() as $classification) {
            $this->literal('notation', $classification->getNotation());
        }
        $this->writer->endElement();

        $this->writer->startElement('schlagwörter');
        $this->writer->writeAttribute('n', count($blatt->getSchlagwort()));
        foreach ($blatt->getSchlagwort() as $schlagwort) {
            $this->simple($schlagwort);
        }
        $this->writer->endElement();

        $this->writer->startElement('provenienzen');
        $this->writer->writeAttribute('n', count($blatt->getProvenance()));
        foreach ($blatt->getProvenance() as $provenance) {
            $this->simple($provenance);
        }
        $this->writer->endElement();

        $this->writer->startElement('techniken');
        $this->writer->writeAttribute('n', count($blatt->getTechnik()));
        foreach ($blatt->getTechnik() as $technik) {
            $this->simple($technik);
        }
        $this->writer->endElement();

        $this->writer->startElement('drucktechniken');
        $this->writer->writeAttribute('n', count($blatt->getDrucktechnik()));
        foreach ($blatt->getDrucktechnik() as $drucktechnik) {
            $this->simple($drucktechnik);
        }
        $this->writer->endElement();

        $this->writer->startElement('referenzen');
        $this->writer->writeAttribute('n', count($blatt->getReferenz()));
        foreach ($blatt->getReferenz() as $referenz) {
            $this->simple($referenz);
        }
        $this->writer->endElement();

        $this->writer->startElement('handbücher');
        $this->writer->writeAttribute('n', count($blatt->getHandbuchreferenz()));
        foreach ($blatt->getHandbuchreferenz() as $handbuch) {
            $this->handbuch($handbuch);
        }
        $this->writer->endElement();

        $this->writer->startElement('literaturen');
        $this->writer->writeAttribute('n', count($blatt->getLiteraturreferenz()));
        foreach ($blatt->getLiteraturreferenz() as $literatur) {
            $this->literatur($literatur);
        }
        $this->writer->endElement();

        $this->writer->startElement('personen');
        $this->writer->writeAttribute('n', count($blatt->getPersonrolle()));
        foreach ($blatt->getPersonrolle() as $person) {
            $this->person($person);
        }
        $this->writer->endElement();

        $this->writer->startElement('orte');
        $this->writer->writeAttribute('n', count($blatt->getOrtrolle()));
        foreach ($blatt->getOrtrolle() as $ort) {
            $this->ort($ort);
        }
        $this->writer->endElement();

        $this->masse('maß_platte', $blatt->getPlatteh(), $blatt->getPlattew(), $blatt->getDurchmesser());
        $this->masse('maß_blatt', $blatt->getBlatth(), $blatt->getBlattw(), $blatt->getDurchmesser());

        $this->literal('datierung_früheste', $blatt->getDatierungF());
        $this->literal('datierung_späteste', $blatt->getDatierungP());

        $this->serie($blatt->getSerie());
        $this->literal('blattnummer', $blatt->getBlattnr());
        $this->kontext($blatt->getKontext());

        $this->writer->endElement();
        $this->writer->endDocument();
        return $this->writer->flush();
    }

    private function literal ($name, $content)
    {
        $this->writer->startElement($name);
        $this->writer->writeAttribute('n', trim($content) ? '1' : '0');
        $this->writer->text(trim($content));
        $this->writer->endElement();
    }

    private function simple ($entity)
    {
        $class = get_class($entity);
        $name = strtolower(substr($class, 1 + strrpos($class, '\\')));
        $this->writer->startElement($name);
        $this->literal('id', $entity->getId());
        $this->literal('label', (string)$entity);
        $this->writer->endElement();
    }

    private function handbuch ($handbuch)
    {
        $this->writer->startElement('handbuch');
        $this->literal('id', $handbuch->getHandbuch()->getId());
        $this->literal('label', $handbuch->getHandbuch());
        $this->literal('seite', $handbuch->getText());
        $this->writer->endElement();
    }

    private function literatur ($literatur)
    {
        $this->writer->startElement('literatur');
        $this->literal('id', $literatur->getLiteratur()->getId());
        $this->literal('label', $literatur->getLiteratur());
        $this->literal('seite', $literatur->getSeite());
        $this->writer->endElement();
    }

    private function person ($person)
    {
        $this->writer->startElement('person');
        $this->literal('id', $person->getPerson()->getId());
        $this->literal('label', $person->getPerson()->getText());
        foreach (explode(';', $person->getPerson()->getGnd()) as $normref) {
            $this->literal('normref', trim($normref));
        }
        $this->writer->startElement('rolle');
        $this->literal('id', $person->getFunktion()->getId());
        $this->literal('label', $person->getFunktion());
        $this->writer->endElement();
        $this->writer->endElement();
    }

    private function ort ($ort)
    {
        $this->writer->startElement('ort');
        $this->literal('id', $ort->getOrt()->getId());
        $this->literal('label', $ort->getOrt()->getText());
        foreach (explode(';', $ort->getOrt()->getTgn()) as $normref) {
            $this->literal('normref', trim($normref));
        }
        $this->writer->startElement('rolle');
        $this->literal('id', $ort->getFunktion()->getId());
        $this->literal('label', $ort->getFunktion());
        $this->writer->endElement();
        $this->writer->endElement();
    }

    private function masse ($name, $height, $width, $durchmesser)
    {
        $this->writer->startElement($name);
        $this->writer->writeAttribute('n', ($height or $width) ? '1' : '0');
        if ($height or $width) {
            $this->literal('höhe', $height);
            $this->literal('breite', $width);
            $this->literal('durchmesser', $durchmesser ? 'true' : 'false');
        }
        $this->writer->endElement();
    }

    private function serie ($serie)
    {
        $this->writer->startElement('serie');
        $this->writer->writeAttribute('n', $serie ? '1' : '0');
        if ($serie) {
            $this->literal('id', $serie->getId());
            $this->literal('label', $serie);
        }
        $this->writer->endElement();
    }

    private function kontext ($kontext)
    {
        $this->writer->startElement('kontext');
        $this->writer->writeAttribute('n', $kontext ? '1' : '0');
        if ($kontext) {
            $this->literal('id', $kontext->getId());
            $this->literal('label', $kontext);
        }
        $this->writer->endElement();
    }

}
