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

/**
 * Resumption token implementation.
 *
 * @author    David Maus <maus@hab.de>
 * @copyright (c) 2016-2018 by Herzog August Bibliothek Wolfenbüttel
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License v3 or higher
 */
class Encoder
{
    private static $separator = ';';
    private static $algorithm = 'ripemd160';
    private static $secret = 'd520738a-c207-450b-b815-65de1b31a4ad';

    public function encode (CommandInterface $command)
    {
        $data = array();
        foreach ($command as $name => $value) {
            $data[$name] = $value;
        }
        $separator = self::$separator;
        $token = base64_encode(http_build_query($data));
        $hash = hash_hmac(self::$algorithm, $token, self::$secret);
        return "{$token}{$separator}{$hash}";
    }

    public function decode (CommandInterface $command, $token)
    {
        if (strpos($token, self::$separator) === false) {
            return false;
        }

        list($token, $hash) = explode(self::$separator, $token, 2);
        if ($hash !== hash_hmac(self::$algorithm, $token, self::$secret)) {
            return false;
        }

        $token = base64_decode($token, true);
        if ($token === false) {
            throw new ProtocolError\BadResumptionToken();
        }

        parse_str($token, $data);
        foreach ($data as $key => $value) {
            if (!property_exists($command, $key)) {
                throw new ProtocolError\BadResumptionToken($key);
            }
            $command->$key = $value;
        }
        $command->setIsResumed(true);
        return true;
    }
}
