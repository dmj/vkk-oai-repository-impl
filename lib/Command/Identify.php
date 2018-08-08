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

use Doctrine\ORM\EntityRepository;

use HAB\OAI\PMH\Model\UtcDateTime;
use HAB\OAI\PMH\Model\Identity;

/**
 * Identify response.
 *
 * @author    David Maus <maus@hab.de>
 * @copyright (c) 2016-2018 by Herzog August Bibliothek Wolfenbüttel
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License v3 or higher
 */
class Identify implements CommandInterface
{
    private $identity;
    private $entities;

    public function __construct (Identity $identity, EntityRepository $entities)
    {
        $this->identity = $identity;
        $this->entities = $entities;
    }

    /**
     * {@inheritDoc}
     */
    public function execute ()
    {
        $builder = $this->entities->createQueryBuilder('blatt');
        $builder
            ->select('min(blatt.exportDatestamp)')
            ->where('blatt.public = ?0')
            ->setParameter(0, 1);

        $datestamp = $builder->getQuery()->execute();

        $this->identity->earliestDatestamp = date_create_from_format('Y-m-d H:i:s', $datestamp[0][1])->format(UtcDateTime::G_DATETIME);
        return $this->identity;
    }
}
