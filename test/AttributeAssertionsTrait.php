<?php

declare(strict_types=1);

namespace MezzioTest\Helper;

use ReflectionProperty;

trait AttributeAssertionsTrait
{
    /** @param mixed $expected */
    public static function assertAttributeSame($expected, string $attribute, object $object): void
    {
        $r = new ReflectionProperty($object, $attribute);
        $r->setAccessible(true);
        self::assertSame($expected, $r->getValue($object));
    }

    /** @param mixed $expected */
    public static function assertAttributeEquals($expected, string $attribute, object $object): void
    {
        $r = new ReflectionProperty($object, $attribute);
        $r->setAccessible(true);
        self::assertEquals($expected, $r->getValue($object));
    }

    /** @param mixed $expected */
    public static function assertAttributeContains($expected, string $attribute, object $object): void
    {
        $r = new ReflectionProperty($object, $attribute);
        $r->setAccessible(true);
        self::assertContains($expected, $r->getValue($object));
    }
}
