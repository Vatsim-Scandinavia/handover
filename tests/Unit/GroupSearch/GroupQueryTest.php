<?php

declare(strict_types=1);

namespace Tests\Unit\GroupSearch;

use App\Support\GroupSearch\GroupQuery;
use App\Support\GroupSearch\Term;
use PHPUnit\Framework\TestCase;

class GroupQueryTest extends TestCase
{
    public function test_attribute_terms_match_without_case(): void
    {
        $query = GroupQuery::parse('Area=Norway');

        $this->assertTrue($query->has(Term::attribute('area', 'norway')));
        $this->assertFalse($query->has(Term::attribute('area', 'denmark')));
    }

    public function test_quoted_values_round_trip(): void
    {
        $term = Term::attribute('area', 'north sea');

        $this->assertSame('area="north sea"', $term->raw);
        $this->assertTrue(GroupQuery::parse($term->raw)->has($term));
    }
}
