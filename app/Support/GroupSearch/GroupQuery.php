<?php

declare(strict_types=1);

namespace App\Support\GroupSearch;

use App\Models\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * The search box on the groups overview: a list of terms, and every term
 * must match. See Term for the syntax.
 */
class GroupQuery
{
    /** @param list<Term> $terms */
    private function __construct(public readonly array $terms) {}

    public static function parse(string $q): self
    {
        preg_match_all('/(?:[^\s"]+|"[^"]*")+/', $q, $matches);

        return new self(array_values(array_filter(array_map(Term::parse(...), $matches[0]))));
    }

    public function isEmpty(): bool
    {
        return $this->terms === [];
    }

    /** Whether the query already holds a term, however the user wrote it. */
    public function has(Term $term): bool
    {
        foreach ($this->terms as $t) {
            if ($t->is($term)) {
                return true;
            }
        }

        return false;
    }

    /** The query string with one more term. */
    public function with(Term $term): string
    {
        return trim($this->toString().' '.$term->raw);
    }

    /** The query string without one of its terms, as the user wrote it. */
    public function without(Term $term): string
    {
        return implode(' ', array_map(fn (Term $t): string => $t->raw, array_filter($this->terms, fn (Term $t): bool => $t !== $term)));
    }

    public function toString(): string
    {
        return implode(' ', array_map(fn (Term $t): string => $t->raw, $this->terms));
    }

    public function apply(Builder $groups): Builder
    {
        foreach ($this->terms as $term) {
            $term->apply($groups);
        }

        return $groups;
    }

    /**
     * Tag and attribute terms that would narrow these results without
     * emptying them, most common first.
     *
     * @param  Collection<int, Group>  $groups
     * @return Collection<int, array{term: Term, count: int}>
     */
    public function suggestions(Collection $groups, int $limit = 10): Collection
    {
        return $groups->toBase()
            ->flatMap(fn (Group $g) => $g->tags->toBase()->map(fn ($t): Term => Term::tag($t->tag))
                ->merge($g->attributeValues->map(fn ($av): Term => Term::attribute($av->definition->key, $av->value))))
            ->groupBy(fn (Term $t): string => $t->raw)
            ->map(fn (Collection $same): array => ['term' => $same->first(), 'count' => $same->count()])
            ->reject(fn (array $s): bool => $s['count'] === $groups->count() || $this->has($s['term']))
            ->sortByDesc('count')
            ->take($limit)
            ->values();
    }
}
