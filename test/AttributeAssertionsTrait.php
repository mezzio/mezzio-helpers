<?php

declare(strict_types=1);

namespace MezzioTest\Helper;

use ReflectionProperty;

trait AttributeAssertionsTrait
{
    public static function assertAttributeSame(mixed $expected, string $attribute, object $object): void
    {
        $r = new ReflectionProperty($object, $attribute);
        $r->setAccessible(true);

        self::assertSame($expected, $r->getValue($object));
    }

    public static function assertAttributeEquals(mixed $expected, string $attribute, object $object): void
    {
        $r = new ReflectionProperty($object, $attribute);
        $r->setAccessible(true);

        self::assertEquals($expected, $r->getValue($object));
    }

    public static function assertAttributeContains(mixed $expected, string $attribute, object $object): void
    {
        $r = new ReflectionProperty($object, $attribute);
        $r->setAccessible(true);

        $value = $r->getValue($object);
        self::assertIsIterable($value);
        self::assertContains($expected, $value);
    }
}
