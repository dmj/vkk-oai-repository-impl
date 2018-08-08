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

use HAB\OAI\PMH\Model;

/**
 * ListSets operation.
 *
 * @author    David Maus <maus@hab.de>
 * @copyright (c) 2016-2018 by Herzog August Bibliothek Wolfenbüttel
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License v3 or higher
 */
class Sets extends Command implements CommandInterface
{
    private $sets = [
        [
            'name' => 'Herzog August Bibliothek',
            'spec' => 'HAB',
        ],
        [
            'name' => 'Herzog August Bibliothek, Virtuelles Kupferstichkabinett',
            'spec' => 'HAB:VKK'
        ],

        [
            'name' => 'Herzog Anton Ulrich-Museum',
            'spec' => 'HAUM'
        ],
        [
            'name' => 'Herzog Anton Ulrich-Museum, Virtuelles Zeichnungskabinett',
            'spec' => 'HAUM:VZK'
        ],
        [
            'name' => 'Herzog Anton Ulrich-Museum, Virtuelles Kupferstichkabinett',
            'spec' => 'HAUM:VKK'
        ],
    ];
    
    public function execute ()
    {
        $feed = new Model\ResponseBody();
        foreach ($this->sets as $decl) {
            $feed->append(new Model\Set($decl['name'], $decl['spec']));
        }
        return $feed;
    }
}
