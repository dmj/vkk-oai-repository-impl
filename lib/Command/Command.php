<?php

/**
 * This file is part of HAB VKK OAI-PMH.
 *
 * HAB VKK OAI-PMH is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * HAB VKK OAI-PMH is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with HAB VKK OAI-PMH.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @author    David Maus <maus@hab.de>
 * @copyright (c) 2016-2018 by Herzog August Bibliothek Wolfenbüttel
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License v3 or higher
 */

namespace HAB\VKK\Service\Export\Command;

use HAB\VKK\Entity\Blatt;

use HAB\OAI\PMH\Model;
use HAB\OAI\PMH\ProtocolError;

use Doctrine\Common\Collections\Criteria;
use DateTimeImmutable;

/**
 * Abstract base class of OAI commands.
 *
 * @author    David Maus <maus@hab.de>
 * @copyright (c) 2016-2018 by Herzog August Bibliothek Wolfenbüttel
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License v3 or higher
 */
class Command
{
    protected static function id2identifier ($id)
    {
        return sprintf('oai:virtuelles-kupferstichkabinett.de:%d', $id);
    }

    protected static function identifier2id ($identifier)
    {
        if (sscanf($identifier, 'oai:virtuelles-kupferstichkabinett.de:%d', $id)) {
            return $id;
        }
    }

    protected static function blatt2setspecs (Blatt $blatt)
    {
        $setspecs   = array();
        $setspecs []= $blatt->getInstitutionLabelShort();
        $setspecs []= $blatt->getInstitutionLabelShort() . ':' . $blatt->getProjekt();
        return $setspecs;
    }

    protected function createRecordHeader (Blatt $blatt)
    {
        $identifier = self::id2identifier($blatt->getId());
        $datestamp  = new Model\UtcDateTime($blatt->getExportDatestamp()->format('Y-m-d\TH:i:s'));;
        $setspecs   = self::blatt2setspecs($blatt);
        $header     = new Model\Header($identifier, $datestamp, $setspecs);
        return $header;
    }

    protected function createRecord (Blatt $blatt)
    {
        $header = $this->createRecordHeader($blatt);
        $metadata = $this->createRecordMetadata($blatt);
        return new Model\Record($header, $metadata);
    }

    protected function createRecordMetadata (Blatt $blatt)
    {
        $format = $this->metadataPrefix;
        $serializer = $this->getMetadataSerializer()->getSerializer($format);
        if (is_null($serializer)) {
            throw new ProtocolError\CannotDisseminateFormat(sprintf("Cannot disemminate format '%s'", $format));
        }
        $payload = $serializer->serialize($blatt);
        return new Model\Metadata($payload);
    }

    protected function createCriteria ()
    {
        $criteria = new Criteria();
        $criteria->orderBy(array('exportDatestamp' => 'asc', 'id' => 'asc'));
        $criteria->where(Criteria::expr()->eq('public', 1));

        if ($this->set) {
            $parts = explode(':', $this->set, 2);
            if ($institution = array_shift($parts)) {
                if ($institution == 'HAB') {
                    $criteria->andWhere(Criteria::expr()->eq('institution', 1));
                } else {
                    $criteria->andWhere(Criteria::expr()->eq('institution', 2));
                }
            }
            if ($projekt = array_shift($parts)) {
                $criteria->andWhere(Criteria::expr()->eq('projekt', $projekt));
            }
        }
        if ($this->from) {
            $criteria->andWhere(Criteria::expr()->gte('exportDatestamp', new DateTimeImmutable($this->from)));
        }
        if ($this->until) {
            $criteria->andWhere(Criteria::expr()->lte('exportDatestamp', new DateTimeImmutable($this->until)));
        }
        if ($this->cursor) {
            $criteria->setFirstResult($this->cursor);
        }
        return $criteria;
    }

    protected function getMetadataSerializer ()
    {
        if (!$this->serializer) {
            $this->setMetadataSerializer(new MetadataSerializer());
        }
        return $this->serializer;
    }

    public function setMetadataSerializer (MetadataSerializer $serializer)
    {
        $this->serializer = $serializer;
    }
}
