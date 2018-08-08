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

use HAB\OAI\PMH\ProtocolError;
use HAB\OAI\PMH\Model;

use HAB\VKK\Service\Export\Serializer\SerializerInterface;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;

/**
 * GetRecord.
 *
 * @author    David Maus <maus@hab.de>
 * @copyright (c) 2016-2018 by Herzog August Bibliothek Wolfenbüttel
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License v3 or higher
 */
class GetRecord extends Command implements CommandInterface
{
    public $identifier;
    public $metadataPrefix;

    private $entities;

    public function __construct (EntityRepository $entities)
    {
        $this->entities = $entities;
        $this->serializers = array();
    }

    public function execute ()
    {
        $blattId = self::identifier2id($this->identifier);
        if ($blattId == 0) {
            throw new ProtocolError\IdDoesNotExist(sprintf("Record '%s' does not exist", $this->identifier));
        }

        $criteria = new Criteria();
        $criteria
            ->where(Criteria::expr()->eq('public', 1))
            ->andWhere(Criteria::expr()->eq('id', $blattId));
        $matches = $this->entities->matching($criteria);

        if ($matches->count() == 0) {
            throw new ProtocolError\IdDoesNotExist(sprintf("Record '%s' does not exist", $this->identifier));
        }

        $blatt  = $matches->first();
        $record = $this->createRecord($blatt);

        $feed = new Model\ResponseBody();
        $feed->append($record);

        return $feed;
    }
}
