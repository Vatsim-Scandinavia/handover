<?php

declare(strict_types=1);

namespace App\Vatsim;

use Illuminate\Support\Arr;
use UnexpectedValueException;

/**
 * A VATSIM member, read from VATSIM Connect's /api/user. Only this class
 * knows the shape of that response.
 */
final readonly class Member
{
    public function __construct(
        public int $cid,
        public string $email,
        public string $firstName,
        public string $lastName,
        public int $rating,
        public string $ratingShort,
        public string $ratingLong,
        public int $pilotRating,
        public string $pilotRatingShort,
        public string $pilotRatingLong,
        public ?string $country,
        public string $region,
        public ?string $division,
        public ?string $subdivision,
    ) {}

    /**
     * @param  array<mixed>  $owner
     *
     * @throws IncompleteConsent when the member withheld a scope we require
     */
    public static function fromResourceOwner(array $owner): self
    {
        $data = Arr::get($owner, 'data');
        if (
            ! is_array($data)
            || ! is_numeric(Arr::get($data, 'cid'))
            || ! is_array(Arr::get($data, 'vatsim'))
            || ! is_string(Arr::get($data, 'personal.email'))
            || Arr::get($data, 'oauth.token_valid') !== 'true'
        ) {
            throw new IncompleteConsent;
        }

        return new self(
            cid: (int) Arr::get($data, 'cid'),
            email: self::string($data, 'personal.email'),
            firstName: self::name($data, 'personal.name_first'),
            lastName: self::name($data, 'personal.name_last'),
            rating: self::int($data, 'vatsim.rating.id'),
            ratingShort: self::string($data, 'vatsim.rating.short'),
            ratingLong: self::string($data, 'vatsim.rating.long'),
            pilotRating: self::int($data, 'vatsim.pilotrating.id'),
            pilotRatingShort: self::string($data, 'vatsim.pilotrating.short'),
            pilotRatingLong: self::string($data, 'vatsim.pilotrating.long'),
            country: self::nullableString($data, 'personal.country.id'),
            region: self::string($data, 'vatsim.region.id'),
            division: self::nullableString($data, 'vatsim.division.id'),
            subdivision: self::nullableString($data, 'vatsim.subdivision.id'),
        );
    }

    public function isSuspended(): bool
    {
        return $this->rating === 0;
    }

    public function isInactive(): bool
    {
        return $this->rating === -1;
    }

    /**
     * @param  array<mixed>  $data
     */
    private static function name(array $data, string $key): string
    {
        return ucfirst(mb_convert_encoding(self::string($data, $key), 'UTF-8'));
    }

    /**
     * @param  array<mixed>  $data
     */
    private static function string(array $data, string $key): string
    {
        return self::nullableString($data, $key)
            ?? throw new UnexpectedValueException("VATSIM Connect sent no {$key}.");
    }

    /**
     * @param  array<mixed>  $data
     */
    private static function nullableString(array $data, string $key): ?string
    {
        $value = Arr::get($data, $key);

        return is_scalar($value) ? (string) $value : null;
    }

    /**
     * @param  array<mixed>  $data
     */
    private static function int(array $data, string $key): int
    {
        $value = Arr::get($data, $key);

        return is_numeric($value)
            ? (int) $value
            : throw new UnexpectedValueException("VATSIM Connect sent no {$key}.");
    }
}
