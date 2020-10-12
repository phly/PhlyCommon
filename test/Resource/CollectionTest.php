<?php

namespace PhlyCommonTest\Resource;

use PhlyCommon\Resource\Collection;
use PhlyCommonTest\Resource\TestAsset\TestEntity;
use PHPUnit\Framework\TestCase;

use function count;
use function strtotime;

class CollectionTest extends TestCase
{
    public function getItems(): array
    {
        return [
            [
                'id'        => 'some-slug',
                'title'     => 'Some Slug',
                'body'      => 'Some Slug.',
                'author'    => 'matthew',
                'is_draft'  => false,
                'is_public' => true,
                'created'   => strtotime('today'),
                'updated'   => strtotime('today'),
                'timezone'  => 'America/New_York',
                'tags'      => ['foo', 'bar'],
                'version'   => 2,
            ],
            [
                'id'        => 'some-other-slug',
                'title'     => 'Some Other Slug',
                'body'      => 'Some other slug.',
                'author'    => 'matthew',
                'is_draft'  => true,
                'is_public' => true,
                'created'   => strtotime('yesterday'),
                'updated'   => strtotime('today'),
                'timezone'  => 'America/New_York',
                'tags'      => ['foo'],
                'version'   => 2,
            ],
            [
                'id'        => 'some-final-slug',
                'title'     => 'Some Final Slug',
                'body'      => 'Some final slug.',
                'author'    => 'matthew',
                'is_draft'  => false,
                'is_public' => true,
                'created'   => strtotime('2 days ago'),
                'updated'   => strtotime('yesterday'),
                'timezone'  => 'America/New_York',
                'tags'      => ['bar'],
                'version'   => 2,
            ],
        ];
    }

    public function testCanIterateCollection(): void
    {
        $items      = $this->getItems();
        $collection = new Collection($items, TestEntity::class);
        $i          = 0;
        foreach ($collection as $item) {
            $i++;
        }
        self::assertTrue($i > 0);
    }

    public function testCollectionIsCountable(): void
    {
        $items      = $this->getItems();
        $collection = new Collection($items, TestEntity::class);
        self::assertEquals(count($items), count($collection));
    }

    public function testIteratingOverCollectionReturnsObjectsOfSpecifiedClass(): void
    {
        $items      = $this->getItems();
        $collection = new Collection($items, TestEntity::class);
        $i          = 0;
        foreach ($collection as $item) {
            self::assertInstanceOf(TestEntity::class, $item);
        }
    }
}
