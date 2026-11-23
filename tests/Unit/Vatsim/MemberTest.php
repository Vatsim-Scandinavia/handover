<?php

declare(strict_types=1);

namespace Tests\Unit\Vatsim;

use App\Vatsim\IncompleteConsent;
use App\Vatsim\Member;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\FakesVatsimConnect;
use Tests\TestCase;
use UnexpectedValueException;

class MemberTest extends TestCase
{
    use FakesVatsimConnect;

    public function test_it_reads_a_full_response(): void
    {
        $member = Member::fromResourceOwner($this->vatsimUser(1234567));

        $this->assertSame(1234567, $member->cid);
        $this->assertSame('Ola', $member->firstName);
        $this->assertSame(5, $member->rating);
        $this->assertSame('NO', $member->country);
        $this->assertSame('SCA', $member->subdivision);
        $this->assertFalse($member->isSuspended());
        $this->assertFalse($member->isInactive());
    }

    public function test_a_member_outside_a_subdivision_has_none(): void
    {
        $member = Member::fromResourceOwner($this->vatsimUser(1234567, ['subdivision' => ['id' => null, 'name' => null]]));

        $this->assertNull($member->subdivision);
    }

    public function test_ratings_mark_suspended_and_inactive_members(): void
    {
        $this->assertTrue(Member::fromResourceOwner($this->vatsimUser(1, ['rating' => ['id' => 0, 'short' => 'SUS', 'long' => 'Suspended']]))->isSuspended());
        $this->assertTrue(Member::fromResourceOwner($this->vatsimUser(1, ['rating' => ['id' => -1, 'short' => 'INA', 'long' => 'Inactive']]))->isInactive());
    }

    #[DataProvider('withheldConsent')]
    public function test_withheld_consent_is_rejected(string $key): void
    {
        $owner = $this->vatsimUser(1234567);
        Arr::forget($owner, $key);

        $this->expectException(IncompleteConsent::class);
        Member::fromResourceOwner($owner);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function withheldConsent(): array
    {
        return [
            'email' => ['data.personal.email'],
            'VATSIM details' => ['data.vatsim'],
            'continuous access' => ['data.oauth'],
        ];
    }

    public function test_a_missing_rating_is_an_error_not_a_suspension(): void
    {
        $owner = $this->vatsimUser(1234567);
        Arr::forget($owner, 'data.vatsim.rating');

        $this->expectException(UnexpectedValueException::class);
        Member::fromResourceOwner($owner);
    }
}
