<?php

namespace Tests\Feature;

use App\Models\Group;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_error_pages_render_without_a_session(): void
    {
        // No web middleware, so no session or auth, as when those fail. POST,
        // because the catch-all landing route takes every GET.
        Route::post('/_test/missing', fn () => abort(404));

        $this->post('/_test/missing')
            ->assertNotFound()
            ->assertSee(asset('images/logos/'.config('app.logo')), false)
            ->assertSee('/_test/missing'); // the 404 page names the path
    }

    public function test_error_pages_show_the_operators_support_html(): void
    {
        config(['app.owner_support' => '<a href="https://support.test">Support</a>']);
        Route::post('/_test/broken', fn () => abort(500));

        $this->post('/_test/broken')
            ->assertServerError()
            ->assertSee('<a href="https://support.test">', false)
            ->assertDontSee('mailto:'.config('app.owner_contact'), false);
    }

    public function test_managers_get_the_management_links_on_member_pages(): void
    {
        $admin = User::factory()->create();
        Group::factory()->admin()->create()->members()->attach($admin->id, ['created_at' => now()]);

        $this->actingAs($admin)
            ->get('/')
            ->assertOk()
            ->assertSee(route('groups.index'), false)
            ->assertSee(route('admin.oauth-clients.index'), false);
    }

    public function test_members_get_no_management_links(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertDontSee(route('groups.index'), false)
            ->assertDontSee(route('admin.oauth-clients.index'), false);
    }
}
