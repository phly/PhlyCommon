<?php

namespace PhlyCommonTest\DataSource;

use PhlyCommon\DataSource\Mock;
use PhlyCommon\DataSource\Query;
use PHPUnit\Framework\TestCase;

use function array_merge;

class MockTest extends TestCase
{
    /** @var Mock */
    private $mock;

    protected function setUp(): void
    {
        $this->mock = new Mock();
    }

    public function testCanMockQueries(): void
    {
        $query = new Query();
        $query->where('foo', 'eq', 'bar')
            ->orWhere('bar', 'ne', 'baz')
            ->where('baz', '>', 1)
            ->limit(10, 10);
        $return = [
            ['id' => 1, 'foo' => 'bar', 'bar' => 'nada', 'baz' => 2],
            ['id' => 1, 'foo' => 'bar', 'bar' => 'nothing', 'baz' => 3],
        ];
        $this->mock->when($query, $return);

        $query2 = new Query();
        $query2->where('foo', 'eq', 'bar')
            ->orWhere('bar', 'ne', 'baz')
            ->where('baz', '>', 1)
            ->limit(10, 10);
        self::assertEquals($return, $this->mock->query($query2));
    }

    public function testQueryReturnsEmptyArrayWhenQueryObjectIsUnmatched(): void
    {
        $query = new Query();
        $query->where('foo', 'eq', 'bar')
            ->orWhere('bar', 'ne', 'baz')
            ->where('baz', '>', 1)
            ->limit(10, 10);
        $return = [
            ['id' => 1, 'foo' => 'bar', 'bar' => 'nada', 'baz' => 2],
            ['id' => 1, 'foo' => 'bar', 'bar' => 'nothing', 'baz' => 3],
        ];
        $this->mock->when($query, $return);

        $query2 = new Query();
        $query2->where('foo', 'eq', 'bar');
        self::assertEquals([], $this->mock->query($query2));
    }

    public function testCreateUsesIdFromDefinitionWhenAvailable(): void
    {
        $definition = [
            'id'  => 'foo',
            'bar' => 'baz',
        ];

        $result = $this->mock->create($definition);
        self::assertSame($definition, $result);
    }

    public function testCreateInsertsIdWhenNoneProvidedInDefinition(): void
    {
        $definition = [
            'bar' => 'baz',
        ];

        $result = $this->mock->create($definition);
        self::assertNotSame($definition, $result);
        self::assertArrayHasKey('id', $result);
    }

    public function testCreateRaisesExceptionIfItemWithIdExists(): void
    {
        $definition = [
            'id'  => 'foo',
            'bar' => 'baz',
        ];

        $this->mock->create($definition);
        $this->expectException('DomainException');
        $this->expectExceptionMessage('already exists');
        $this->mock->create($definition);
    }

    public function testUpdateRaisesExceptionIfItemWithIdDoesNotExist(): void
    {
        $definition = [
            'id'  => 'foo',
            'bar' => 'baz',
        ];

        $this->expectException('DomainException');
        $this->expectExceptionMessage('does not yet exist');
        $this->mock->update($definition['id'], $definition);
    }

    public function testUpdateMergesFieldsWithExistingDefinition(): void
    {
        $definition = [
            'id'  => 'foo',
            'bar' => 'baz',
        ];
        $this->mock->create($definition);
        $fields = [
            'bar' => 'BAZBAT',
            'baz' => 'bat',
        ];
        $result = $this->mock->update($definition['id'], $fields);
        self::assertEquals(array_merge($definition, $fields), $result);
    }

    public function testGetReturnsNullIfItemWithIdDoesNotExist(): void
    {
        $definition = [
            'id'  => 'foo',
            'bar' => 'baz',
        ];
        $this->mock->create($definition);
        self::assertNull($this->mock->get('bar'));
    }

    public function testGetReturnsPreviouslyStoredItemIfIdExists(): void
    {
        $definition = [
            'id'  => 'foo',
            'bar' => 'baz',
        ];
        $this->mock->create($definition);
        $test = $this->mock->get('foo');
        self::assertSame($definition, $test);
    }

    public function testDeleteRemovesPreviouslyStoredItems(): void
    {
        $definition = [
            'id'  => 'foo',
            'bar' => 'baz',
        ];
        $this->mock->create($definition);
        $this->mock->delete('foo');
        self::assertNull($this->mock->get('foo'));
    }
}
