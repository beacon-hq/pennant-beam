<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function prop(object $object, string $property)
{
    $reflection = new \ReflectionObject($object);
    $prop = $reflection->getProperty($property);

    return $prop->getValue($object);
}
