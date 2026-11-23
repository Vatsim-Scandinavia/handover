<?php

declare(strict_types=1);

namespace Tests\Unit\Vatsim;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use League\OAuth2\Client\Token\AccessToken;
use Tests\TestCase;

class AsVatsimTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_and_reads_a_token(): void
    {
        $expires = now()->addHour()->timestamp;
        $user = User::factory()->create();

        $user->vatsim_token = new AccessToken(['access_token' => 'a', 'refresh_token' => 'r', 'expires' => $expires]);
        $user->save();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'access_token' => 'a', 'refresh_token' => 'r', 'token_expires' => $expires]);
        $token = $user->fresh()?->vatsim_token;
        $this->assertInstanceOf(AccessToken::class, $token);
        $this->assertSame(['a', 'r', $expires], [$token->getToken(), $token->getRefreshToken(), $token->getExpires()]);
    }

    public function test_an_expired_token_reads_as_expired(): void
    {
        $user = User::factory()->create(['access_token' => 'a', 'refresh_token' => 'r', 'token_expires' => now()->subMinute()->timestamp]);

        $this->assertTrue($user->vatsim_token?->hasExpired());
    }

    public function test_null_clears_the_token(): void
    {
        $user = User::factory()->create(['access_token' => 'a', 'refresh_token' => 'r', 'token_expires' => 1]);

        $user->vatsim_token = null;
        $user->save();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'access_token' => null, 'refresh_token' => null, 'token_expires' => null]);
        $this->assertNull($user->fresh()?->vatsim_token);
    }
}
