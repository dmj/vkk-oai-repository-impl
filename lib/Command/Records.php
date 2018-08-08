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
 * Base class for ListIdentifiers and ListRecords.
 *
 * @author    David Maus <maus@hab.de>
 * @copyright (c) 2016-2018 by Herzog August Bibliothek Wolfenbüttel
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License v3 or higher
 */
class Records extends Command implements CommandInterface
{
    public $from;
    public $until;
    public $metadataPrefix;
    public $set;
    public $cursor;

    private $isResumed;
    private $entities;
    private $headersOnly;

    private $serializers;

    public function __construct (EntityRepository $entities, $headersOnly = false)
    {
        $this->entities = $entities;
        $this->headersOnly = $headersOnly;
        $this->serializers = array();
    }

    /**
     * {@inheritDoc}
     */
    public function execute ()
    {
        if ($this->metadataPrefix and !$this->getMetadataSerializer()->hasSerializer($this->metadataPrefix)) {
            throw new ProtocolError\CannotDisseminateFormat(sprintf("The metadata format '%s' is not supported", $this->metadataPrefix));
        }

        $criteria = $this->createCriteria();

        $matches = $this->entities->matching($criteria);
        $completeListSize = $matches->count();
        if ($this->isResumed and ($completeListSize < $this->cursor)) {
            throw new ProtocolError\BadResumptionToken('The resumption token is not longer valid');
        }
        if ($completeListSize == 0) {
            throw new ProtocolError\NoRecordsMatch();
        }

        $entries = array();
        $criteria->setMaxResults(25);

        $feed = new Model\ResponseBody();
        foreach ($matches as $blatt) {
            if ($this->headersOnly) {
                $entity = $this->createRecordHeader($blatt);
            } else {
                $entity = $this->createRecord($blatt);
            }
            $feed->append($entity);
            $this->cursor++;
        }

        if ($completeListSize > $this->cursor) {
            $encoder = new Encoder();
            $token = new Model\ResumptionToken($encoder->encode($this));
            $token->setCompleteListSize($completeListSize);
            $token->setCursor($this->cursor);
            $feed->setResumptionToken($token);
        }

        return $feed;
    }

    public function resume ($token)
    {
        $serializer = new Encoder();
        if ($serializer->decode($this, $token) === false) {
            throw new ProtocolError\BadResumptionToken('The resumption token is not valid');
        }
        return $this->execute();
    }

    /**
     * Mark command as resumed.
     *
     * @param  boolean $isResumed
     * @return void
     */
    public function setIsResumed ($isResumed = true)
    {
        $this->isResumed = $isResumed;
    }
}
