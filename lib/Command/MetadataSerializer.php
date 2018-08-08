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

use HAB\OAI\PMH\Model\MetadataFormat;

use HAB\VKK\Service\Export\Serializer\SerializerInterface;

/**
 * Metadata serializer.
 * 
 * @author    David Maus <maus@hab.de>
 * @copyright (c) 2016-2018 by Herzog August Bibliothek Wolfenbüttel
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License v3 or higher
 */
class MetadataSerializer
{
    private $serializers;
    private $formats;

    public function __construct ()
    {
        $this->formats = array();
        $this->serializers = array();
    }

    public function addSerializer (MetadataFormat $format, SerializerInterface $serializer)
    {
        $this->formats []= $format;
        $this->serializers[$format->getPrefix()] = $serializer;
    }

    public function getSerializer ($metadataPrefix)
    {
        if (array_key_exists($metadataPrefix, $this->serializers)) {
            return $this->serializers[$metadataPrefix];
        }
    }

    public function hasSerializer ($metadataPrefix)
    {
        if (array_key_exists($metadataPrefix, $this->serializers)) {
            return true;
        }
        return false;
    }

    public function getFormats ()
    {
        return $this->formats;
    }
}
