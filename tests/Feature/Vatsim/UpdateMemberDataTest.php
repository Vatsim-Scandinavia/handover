<?php

declare(strict_types=1);

namespace Tests\Feature\Vatsim;

use App\User;
use Closure;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Testing\PendingCommand;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\FakesVatsimConnect;
use Tests\TestCase;
use Throwable;

class UpdateMemberDataTest extends TestCase
{
    use FakesVatsimConnect;
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function linkedUser(array $attributes = []): User
    {
        return User::factory()->create([
            'first_name' => 'Old',
            'access_token' => 'stored-access',
            'refresh_token' => 'stored-refresh',
            'token_expires' => now()->addHour()->timestamp,
            ...$attributes,
        ]);
    }

    private function updateMemberData(): void
    {
        $command = $this->artisan('member:data');
        $this->assertInstanceOf(PendingCommand::class, $command);
        $command->assertSuccessful()->run();
    }

    public function test_a_member_with_a_live_token_is_updated(): void
    {
        $user = $this->linkedUser();
        $this->fakeVatsim($this->vatsimUserResponse($user->id));

        $this->updateMemberData();

        $this->assertCount(1, $this->vatsimRequests());
        $this->assertSame('Bearer stored-access', $this->vatsimRequests()[0]->getHeaderLine('Authorization'));
        $user->refresh();
        $this->assertSame('Ola', $user->first_name);
        $this->assertSame('stored-access', $user->access_token);
    }

    public function test_an_expired_token_is_refreshed_first(): void
    {
        $user = $this->linkedUser(['token_expires' => now()->subMinute()->timestamp]);
        $this->fakeVatsim(
            $this->vatsimTokenResponse(['access_token' => 'new-access', 'refresh_token' => 'new-refresh']),
            $this->vatsimUserResponse($user->id),
        );

        $this->updateMemberData();

        [$refresh, $fetch] = $this->vatsimRequests();
        parse_str((string) $refresh->getBody(), $refreshRequest);
        $this->assertSame('refresh_token', $refreshRequest['grant_type']);
        $this->assertSame('stored-refresh', $refreshRequest['refresh_token']);
        $this->assertSame('Bearer new-access', $fetch->getHeaderLine('Authorization'));

        $user->refresh();
        $this->assertSame('new-access', $user->access_token);
        $this->assertSame('new-refresh', $user->refresh_token);
        $this->assertEqualsWithDelta(time() + 3600, $user->token_expires, 5);
        $this->assertSame('Ola', $user->first_name);
    }

    public function test_a_rejected_refresh_unlinks_the_member(): void
    {
        $user = $this->linkedUser(['token_expires' => now()->subMinute()->timestamp]);
        $this->fakeVatsim($this->vatsimJson(['error' => 'invalid_grant'], 400));

        $this->updateMemberData();

        $this->assertCount(1, $this->vatsimRequests());
        $user->refresh();
        $this->assertNull($user->access_token);
        $this->assertNull($user->refresh_token);
        $this->assertNull($user->token_expires);
        $this->assertSame('Old', $user->first_name);
    }

    /**
     * @param  Closure(self): (Response|Throwable)  $failure
     */
    #[DataProvider('failedRefreshes')]
    public function test_a_refresh_that_fails_for_other_reasons_keeps_the_token(Closure $failure): void
    {
        Exceptions::fake();
        $user = $this->linkedUser(['token_expires' => now()->subMinute()->timestamp]);
        $this->fakeVatsim($failure($this));

        $this->updateMemberData();

        $this->assertSame('stored-refresh', $user->fresh()?->refresh_token);
        Exceptions::assertReportedCount(1);
    }

    /**
     * @return array<string, array{Closure(self): (Response|Throwable)}>
     */
    public static function failedRefreshes(): array
    {
        return [
            'connection failure' => [fn (self $test): Throwable => new ConnectException('Connection refused', new Request('POST', 'https://vatsim.test/oauth/token'))],
            'server error' => [fn (self $test): Response => $test->vatsimJson(['error' => 'server_error'], 503)],
            'HTML error page' => [fn (self $test): Response => new Response(502, ['Content-Type' => 'text/html'], '<h1>Bad Gateway</h1>')],
            'our client rejected' => [fn (self $test): Response => $test->vatsimJson(['error' => 'invalid_client'], 401)],
        ];
    }

    public function test_the_new_token_is_kept_when_the_fetch_fails(): void
    {
        Exceptions::fake();
        $user = $this->linkedUser(['token_expires' => now()->subMinute()->timestamp]);
        $this->fakeVatsim(
            $this->vatsimTokenResponse(['access_token' => 'new-access', 'refresh_token' => 'new-refresh']),
            $this->vatsimJson(['error' => 'server_error'], 500),
        );

        $this->updateMemberData();

        $this->assertSame('new-refresh', $user->fresh()?->refresh_token);
        Exceptions::assertReported(IdentityProviderException::class);
    }

    public function test_one_failing_member_does_not_stop_the_rest(): void
    {
        Exceptions::fake();
        $first = $this->linkedUser(['id' => 1000001]);
        $second = $this->linkedUser(['id' => 1000002]);
        $this->fakeVatsim(
            $this->vatsimJson(['error' => 'server_error'], 500),
            $this->vatsimUserResponse($second->id),
        );

        $this->updateMemberData();

        $this->assertSame('Old', $first->fresh()?->first_name);
        $this->assertSame('Ola', $second->fresh()?->first_name);
        Exceptions::assertReported(IdentityProviderException::class);
    }

    public function test_members_without_a_token_are_skipped(): void
    {
        User::factory()->create();
        $this->fakeVatsim();

        $this->updateMemberData();

        $this->assertSame([], $this->vatsimRequests());
    }
}
