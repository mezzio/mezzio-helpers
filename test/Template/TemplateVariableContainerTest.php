<?php

declare(strict_types=1);

namespace MezzioTest\Helper\Template;

use Mezzio\Helper\Template\TemplateVariableContainer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

use function array_merge;

#[CoversClass(TemplateVariableContainer::class)]
final class TemplateVariableContainerTest extends TestCase
{
    private TemplateVariableContainer $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new TemplateVariableContainer();
    }

    public function testContainerIsEmptyByDefault(): void
    {
        self::assertCount(0, $this->container);
        self::assertSame(0, $this->container->count());
    }

    public function testSettingVariablesReturnsNewInstanceContainingValue(): TemplateVariableContainer
    {
        $container = $this->container->with('key', 'value');

        self::assertNotSame($container, $this->container);
        self::assertCount(1, $container);
        self::assertTrue($container->has('key'));
        self::assertSame('value', $container->get('key'));

        return $container;
    }

    public function testHasReturnsFalseForUnsetVariables(): void
    {
        self::assertFalse($this->container->has('key'));
    }

    public function testGetReturnsNullForUnsetVariables(): void
    {
        self::assertNull($this->container->get('key'));
    }

    #[Depends('testSettingVariablesReturnsNewInstanceContainingValue')]
    public function testCallingWithoutReturnsNewInstanceWithoutValue(TemplateVariableContainer $original): void
    {
        $container = $original->without('key');

        self::assertNotSame($container, $original);
        self::assertTrue($original->has('key'));
        self::assertFalse($container->has('key'));
    }

    public function testMergeReturnsNewInstanceContainingMergedArray(): void
    {
        $values = [
            'foo' => 'bar',
            'bar' => 'baz',
            'baz' => 'bat',
        ];

        $container = $this->container->merge($values);

        self::assertNotSame($container, $this->container);

        foreach ($values as $key => $value) {
            self::assertFalse($this->container->has($key));
            self::assertTrue($container->has($key));
            self::assertNull($this->container->get($key));
            self::assertSame($value, $container->get($key));
        }
    }

    public function testWillReturnArrayWhenRequestedToMergeForTemplate(): void
    {
        $containerValues = [
            'foo' => 'bar',
            'bar' => 'baz',
            'baz' => 'bat',
        ];

        $localValues = [
            'foo'  => 'FOO',
            'else' => 'something',
        ];

        $expected = array_merge($containerValues, $localValues);

        $container = $this->container->merge($containerValues);

        $merged = $container->mergeForTemplate($localValues);

        self::assertSame($expected, $merged);
    }
}
