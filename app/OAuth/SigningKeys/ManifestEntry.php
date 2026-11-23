<?php

declare(strict_types=1);

namespace App\OAuth\SigningKeys;

use Carbon\CarbonImmutable;

/**
 * One key's record in the manifest: its state, when it entered that state, and
 * whether it was adopted from Passport's original key files.
 */
final readonly class ManifestEntry
{
    public function __construct(
        public KeyState $state,
        public CarbonImmutable $since,
        public bool $imported = false,
    ) {}

    /** An entry for a key entering $state now. */
    public static function now(KeyState $state, bool $imported = false): self
    {
        return new self($state, CarbonImmutable::now(), $imported);
    }

    /** @param  array{state: string, since: string, imported: bool}  $data */
    public static function fromArray(array $data): self
    {
        return new self(KeyState::from($data['state']), CarbonImmutable::parse($data['since']), $data['imported']);
    }

    /** @return array{state: string, since: string, imported: bool} */
    public function toArray(): array
    {
        return ['state' => $this->state->value, 'since' => $this->since->toIso8601String(), 'imported' => $this->imported];
    }
}
