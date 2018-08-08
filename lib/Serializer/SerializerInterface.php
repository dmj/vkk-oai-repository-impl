<?php

namespace HAB\VKK\Service\Export\Serializer;

use HAB\VKK\Entity\Blatt;

interface SerializerInterface
{
    public function serialize (Blatt $blatt);
}