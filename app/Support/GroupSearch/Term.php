<?php
namespace App\Support\GroupSearch;

use Illuminate\Database\Eloquent\Builder;

/**
 * One term in a group search: "tag:atc", "area=norway", "is:admin" or a
 * word. Double quotes keep spaces in a value, as in area="north sea".
 */
final readonly class Term
{
    /**
     * @param string $raw The term as the user wrote it.
     * @param string $value The word, the tag, or the attribute value.
     * @param string $key The attribute key.
     */
    private function __construct(
        public TermKind $kind,
        public string $raw,
        public string $value = '',
        public string $key = '',
    ) {}

    public static function parse(string $raw): ?self
    {
        if (strtolower($raw) === 'is:admin') {
            return new self(TermKind::Admin, $raw);
        }
        if (preg_match('/^tag:(.+)$/i', $raw, $m) && self::unquote($m[1]) !== '') {
            return new self(TermKind::Tag, $raw, strtolower(self::unquote($m[1])));
        }
        if (preg_match('/^([a-z0-9_-]+)=(.+)$/i', $raw, $m) && self::unquote($m[2]) !== '') {
            return new self(TermKind::Attribute, $raw, self::unquote($m[2]), strtolower($m[1]));
        }
        return self::unquote($raw) === '' ? null : new self(TermKind::Word, $raw, self::unquote($raw));
    }

    public static function tag(string $tag): self
    {
        return new self(TermKind::Tag, 'tag:'.self::quote($tag), strtolower($tag));
    }

    public static function attribute(string $key, string $value): self
    {
        return new self(TermKind::Attribute, $key.'='.self::quote($value), $value, strtolower($key));
    }

    /**
     * Whether both terms mean the same, however they were written. Values
     * compare without case, as MySQL matches them.
     */
    public function is(self $other): bool
    {
        return [$this->kind, $this->key, mb_strtolower($this->value)]
            === [$other->kind, $other->key, mb_strtolower($other->value)];
    }

    /** Keep only the groups that match this term. */
    public function apply(Builder $groups): void
    {
        match ($this->kind) {
            TermKind::Word => $groups->where(function (Builder $q): void {
                $like = '%'.addcslashes($this->value, '%_\\').'%';
                $q->where('name', 'like', $like)
                    ->orWhere('slug', 'like', $like)
                    ->orWhere('description', 'like', $like);
            }),
            TermKind::Tag => $groups->whereHas('tags', fn (Builder $q) => $q->where('tag', $this->value)),
            TermKind::Attribute => $groups->whereHas('attributeValues', fn (Builder $q) => $q->where('value', $this->value)
                ->whereHas('definition', fn (Builder $d) => $d->where('key', $this->key))),
            TermKind::Admin => $groups->where('is_admin_group', true),
        };
    }

    private static function quote(string $value): string
    {
        return preg_match('/\s/', $value) ? '"'.self::unquote($value).'"' : $value;
    }

    private static function unquote(string $value): string
    {
        return str_replace('"', '', $value);
    }
}
