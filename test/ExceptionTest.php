<?php

declare(strict_types=1);

namespace MezzioTest\Helper;

use Generator;
use Mezzio\Helper\Exception\ExceptionInterface;
use PHPUnit\Framework\TestCase;

use function assert;
use function basename;
use function glob;
use function is_a;
use function is_int;
use function strrpos;
use function substr;

final class ExceptionTest extends TestCase
{
    /** @return Generator<string, array{0: string}> */
    public static function exception(): Generator
    {
        $endPos = strrpos(ExceptionInterface::class, '\\');
        assert(is_int($endPos));
        $namespace = substr(ExceptionInterface::class, 0, $endPos + 1);

        $exceptions = glob(__DIR__ . '/../src/Exception/*.php');
        foreach ($exceptions as $exception) {
            $class = substr(basename($exception), 0, -4);

            yield $class => [$namespace . $class];
        }
    }

    /**
     * @dataProvider exception
     */
    public function testExceptionIsInstanceOfExceptionInterface(string $exception): void
    {
        self::assertStringContainsString('Exception', $exception);
        self::assertTrue(is_a($exception, ExceptionInterface::class, true));
    }
}
