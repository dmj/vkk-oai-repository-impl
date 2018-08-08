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

namespace HAB\VKK\Service\Export;

use HAB\OAI\PMH\Repository\RepositoryInterface;
use Pimple;

/**
 * OAI-PMH 2.0 repository.
 *
 * @author    David Maus <maus@hab.de>
 * @copyright (c) 2016-2018 by Herzog August Bibliothek Wolfenbüttel
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License v3 or higher
 */
class Repository implements RepositoryInterface
{
    private $commands;

    public function __construct (Pimple $commands)
    {
        $this->commands = $commands;
    }

    /**
     * {@inheritDoc}
     */
    public function getRecord ($identifier, $metadataPrefix)
    {
        $command = $this->commands['GetRecord'];
        $command->metadataPrefix = $metadataPrefix;
        $command->identifier = $identifier;

        $feed = $command->execute();
        return $feed;
    }

    /**
     * {@inheritDoc}
     */
    public function identify ()
    {
        $command = $this->commands['Identify'];

        $feed = $command->execute();
        return $feed;
    }

    /**
     * {@inheritDoc}
     */
    public function listIdentifiers ($metadataPrefix, $from = null, $until = null, $set = null)
    {
        $command = $this->commands['ListIdentifiers'];
        $command->metadataPrefix = $metadataPrefix;
        $command->from = $from;
        $command->until = $until;
        $command->set = $set;

        $feed = $command->execute();
        return $feed;
    }

    /**
     * {@inheritDoc}
     */
    public function listRecords ($metadataPrefix, $from = null, $until = null, $set = null)
    {
        $command = $this->commands['ListRecords'];
        $command->metadataPrefix = $metadataPrefix;
        $command->from = $from;
        $command->until = $until;
        $command->set = $set;

        $feed = $command->execute();
        return $feed;
    }

    /**
     * {@inheritDoc}
     */
    public function listMetadataFormats ($identifier = null)
    {
        $command = $this->commands['ListMetadataFormats'];
        $command->identifier = $identifier;

        $feed = $command->execute();
        return $feed;
    }

    /**
     * {@inheritDoc}
     */
    public function listSets ()
    {
        $command = $this->commands['ListSets'];

        $feed = $command->execute();
        return $feed;
    }

    /**
     * {@inheritDoc}
     */
    public function resume ($verb, $resumptionToken)
    {
        $command = $this->commands[$verb];

        $feed = $command->resume($resumptionToken);
        return $feed;
    }

}
