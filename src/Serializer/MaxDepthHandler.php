<?php

namespace App\Serializer;

use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

class MaxDepthHandler
{
    public function __invoke($object, $format, $context)
    {
        return null;
    }
}
