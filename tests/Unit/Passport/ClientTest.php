<?php

namespace Tests\Unit\Passport;

use App\Models\Passport\Client;
use Illuminate\Contracts\Auth\Authenticatable;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public function test_skips_authorization_always_returns_true(): void
    {
        $client = new Client();
        $user = $this->createStub(Authenticatable::class);

        $this->assertTrue($client->skipsAuthorization($user, ['*']));
    }
}
