<?php

declare(strict_types=1);

namespace Tests\Feature\OAuth;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ApiV1AliasTest extends TestCase
{
    use RefreshDatabase;

    public function test_v1_user_returns_the_same_json_as_api_user(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['full_name', 'email', 'vatsim_details', 'country']);

        $legacy = $this->getJson('/api/user')->assertOk()->json();
        $v1 = $this->getJson('/api/v1/user')->assertOk()->json();

        $this->assertSame($legacy, $v1);
        $this->assertSame('true', $v1['data']['oauth']['token_valid']);
        $this->assertSame('N/A', $v1['data']['personal']['country']['name']);
    }
}
