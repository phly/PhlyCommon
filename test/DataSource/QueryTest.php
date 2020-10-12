<?php

namespace PhlyCommonTest\DataSource;

use PhlyCommon\DataSource\Query;
use PhlyCommon\DataSource\Where;
use PHPUnit\Framework\TestCase;

use function array_shift;

class QueryTest extends TestCase
{
    /** @var Query */
    private $query;

    protected function setUp(): void
    {
        $this->query = new Query();
    }

    public function testAggregatesWhereClausesAsAQueue(): void
    {
        $this->query->where('foo', '=', 'bar')
            ->orWhere('bar', 'IS NOT NULL')
            ->where('baz', '!=', 'bat');
        $clauses  = $this->query->getWhereClauses();
        $expected = [
            new Where('and', 'foo', '=', 'bar'),
            new Where('or', 'bar', 'IS NOT NULL', null),
            new Where('and', 'baz', '!=', 'bat'),
        ];
        foreach ($expected as $clause) {
            $test = array_shift($clauses);
            self::assertEquals((array) $clause, (array) $test);
        }
    }

    public function testAggregatesLimitAndOffset(): void
    {
        $this->query->limit(10, 15);
        self::assertEquals(10, $this->query->getLimit());
        self::assertEquals(15, $this->query->getOffset());
    }

    public function testRepeatedCallsToLimitOverwriteLimitAndOffset(): void
    {
        $this->query->limit(10, 15)
            ->limit(20, 30);
        self::assertEquals(20, $this->query->getLimit());
        self::assertEquals(30, $this->query->getOffset());
    }
}
