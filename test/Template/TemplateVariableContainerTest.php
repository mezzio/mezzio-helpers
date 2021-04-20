<?php

/**
 * @see       https://github.com/mezzio/mezzio-helpers for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-helpers/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-helpers/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Helper\Template;

use Mezzio\Helper\Template\TemplateVariableContainer;
use PHPUnit\Framework\TestCase;

use function array_merge;

class TemplateVariableContainerTest extends TestCase
{
    public function setUp(): void
    {
        $this->container = new TemplateVariableContainer();
    }

    public function testContainerIsEmptyByDefault()
    {
        $this->assertCount(0, $this->container);
    }

    public function testSettingVariablesReturnsNewInstanceContainingValue(): TemplateVariableContainer
    {
        $container = $this->container->with('key', 'value');

        $this->assertNotSame($container, $this->container);
        $this->assertCount(1, $container);
        $this->assertTrue($container->has('key'));
        $this->assertSame('value', $container->get('key'));

        return $container;
    }

    public function testHasReturnsFalseForUnsetVariables()
    {
        $this->assertFalse($this->container->has('key'));
    }

    public function testGetReturnsNullForUnsetVariables()
    {
        $this->assertNull($this->container->get('key'));
    }

    /**
     * @depends testSettingVariablesReturnsNewInstanceContainingValue
     */
    public function testCallingWithoutReturnsNewInstanceWithoutValue(TemplateVariableContainer $original)
    {
        $container = $original->without('key');

        $this->assertNotSame($container, $original);
        $this->assertTrue($original->has('key'));
        $this->assertFalse($container->has('key'));
    }

    public function testMergeReturnsNewInstanceContainingMergedArray()
    {
        $values = [
            'foo' => 'bar',
            'bar' => 'baz',
            'baz' => 'bat',
        ];

        $container = $this->container->merge($values);

        $this->assertNotSame($container, $this->container);

        foreach ($values as $key => $value) {
            $this->assertFalse($this->container->has($key));
            $this->assertTrue($container->has($key));
            $this->assertNull($this->container->get($key));
            $this->assertEquals($value, $container->get($key));
        }
    }

    public function testWillReturnArrayWhenRequestedToMergeForTemplate()
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

        $this->assertSame($expected, $merged);
    }
}
