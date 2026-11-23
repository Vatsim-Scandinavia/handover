<?php

declare(strict_types=1);

namespace Tests\Feature\Groups;

use App\Models\Group;
use App\Models\GroupAttributeDefinition;
use App\Models\GroupManagerRuleByGroup;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupOverviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();
        Group::factory()->admin()->create(['name' => 'Handover admins', 'slug' => 'handover-admins'])
            ->members()->attach($user->id, ['created_at' => now()]);

        return $user;
    }

    /**
     * @param  array<string, string>  $attributes  key => value
     * @param  string[]  $tags
     * @param  array<string, bool|string>  $extra
     */
    private function group(string $name, array $tags = [], array $attributes = [], array $extra = []): Group
    {
        $group = Group::factory()->create(array_merge(['name' => $name, 'description' => null], $extra));
        foreach ($tags as $tag) {
            $group->tags()->create(['tag' => $tag]);
        }
        foreach ($attributes as $key => $value) {
            $definition = GroupAttributeDefinition::firstOrCreate(['key' => $key], ['label' => ucfirst($key)]);
            $group->attributeValues()->create(['attribute_definition_id' => $definition->id, 'value' => $value]);
        }

        return $group;
    }

    public function test_text_matches_name_slug_or_description(): void
    {
        $this->group('Sweden ATC');
        $this->group('Oslo approach', extra: ['slug' => 'engm-sweden-border']);
        $this->group('Mentors', extra: ['description' => 'Mentors for Sweden']);
        $this->group('Norway ATC');

        $this->actingAs($this->adminUser())
            ->get(route('groups.index', ['q' => 'sweden']))
            ->assertOk()
            ->assertSee('Sweden ATC')
            ->assertSee('Oslo approach')
            ->assertSee('Mentors')
            ->assertDontSee('Norway ATC');
    }

    public function test_tag_terms_require_every_tag(): void
    {
        $this->group('Sweden ATC', tags: ['atc', 'roster']);
        $this->group('Arlanda TWR', tags: ['atc', 'endorsement']);
        $this->group('Events team', tags: ['events']);

        $this->actingAs($this->adminUser())
            ->get(route('groups.index', ['q' => 'tag:atc tag:roster']))
            ->assertOk()
            ->assertSee('Sweden ATC')
            ->assertDontSee('Arlanda TWR')
            ->assertDontSee('Events team');
    }

    public function test_attribute_terms_match_key_and_value(): void
    {
        $this->group('Norway training', attributes: ['area' => 'norway']);
        $this->group('Denmark training', attributes: ['area' => 'denmark']);
        $this->group('Norway wiki', attributes: ['wiki' => 'norway']);

        $this->actingAs($this->adminUser())
            ->get(route('groups.index', ['q' => 'area=norway']))
            ->assertOk()
            ->assertSee('Norway training')
            ->assertDontSee('Denmark training')
            ->assertDontSee('Norway wiki');
    }

    public function test_quoted_attribute_values_keep_their_spaces(): void
    {
        $this->group('North sea training', attributes: ['area' => 'north sea']);
        $this->group('North pole training', attributes: ['area' => 'north']);

        $html = $this->actingAs($this->adminUser())
            ->get(route('groups.index', ['q' => 'Area="north sea"']))
            ->assertOk()
            ->assertSee('North sea training')
            ->assertDontSee('North pole training')
            ->getContent();

        // The row chip is already in the search, so it is not a link to add it again.
        $this->assertStringNotContainsString(e(route('groups.index', ['q' => 'Area="north sea" area="north sea"'])), $html);
    }

    public function test_row_chips_quote_values_with_spaces(): void
    {
        $this->group('North sea training', tags: ['training'], attributes: ['area' => 'north sea']);

        $this->actingAs($this->adminUser())
            ->get(route('groups.index', ['q' => 'tag:training']))
            ->assertOk()
            ->assertSee(e(route('groups.index', ['q' => 'tag:training area="north sea"'])), false);
    }

    public function test_repeated_attribute_keys_must_all_match(): void
    {
        $this->group('Norway training', attributes: ['area' => 'norway']);

        $this->actingAs($this->adminUser())
            ->get(route('groups.index', ['q' => 'area=denmark area=norway']))
            ->assertOk()
            ->assertSee('No groups match');
    }

    public function test_admin_term_shows_only_admin_groups(): void
    {
        $this->group('Web team', extra: ['is_admin_group' => true]);
        $this->group('Events team');

        $this->actingAs($this->adminUser())
            ->get(route('groups.index', ['q' => 'is:admin team']))
            ->assertOk()
            ->assertSee('Web team')
            ->assertDontSee('Events team');
    }

    public function test_search_never_shows_a_manager_groups_they_cannot_manage(): void
    {
        $user = User::factory()->create();
        $managers = $this->group('Norway staff');
        $managers->members()->attach($user->id, ['created_at' => now()]);
        $target = $this->group('Norway training', tags: ['training']);
        $this->group('Denmark training', tags: ['training']);
        GroupManagerRuleByGroup::create(['manager_group_id' => $managers->id, 'target_group_id' => $target->id]);

        $this->actingAs($user)
            ->get(route('groups.index', ['q' => 'tag:training training']))
            ->assertOk()
            ->assertSee('Norway training')
            ->assertDontSee('Denmark training');
    }

    public function test_groups_can_be_grouped_by_an_attribute(): void
    {
        $this->group('Oslo approach', attributes: ['area' => 'norway']);
        $this->group('Copenhagen tower', attributes: ['area' => 'denmark']);
        $this->group('Bergen tower', attributes: ['area' => 'norway']);
        $this->group('Events team');

        $this->actingAs($this->adminUser())
            ->get(route('groups.index', ['group' => 'area']))
            ->assertOk()
            ->assertSeeInOrder(['denmark', 'Copenhagen tower', 'norway', 'Bergen tower', 'Oslo approach', 'No area', 'Events team']);
    }

    public function test_groups_can_be_sorted_by_member_count(): void
    {
        $small = $this->group('Alpha team');
        $large = $this->group('Bravo team');
        $large->members()->attach(User::factory()->count(3)->create()->pluck('id'), ['created_at' => now()]);
        $small->members()->attach(User::factory()->create()->id, ['created_at' => now()]);

        $this->actingAs($this->adminUser())
            ->get(route('groups.index', ['q' => 'team', 'sort' => 'members']))
            ->assertOk()
            ->assertSeeInOrder(['Bravo team', 'Alpha team']);
    }

    public function test_suggests_terms_that_narrow_the_result(): void
    {
        $this->group('Norway training', tags: ['training', 'mentors'], attributes: ['area' => 'norway']);
        $this->group('Denmark training', tags: ['training'], attributes: ['area' => 'denmark']);

        $html = $this->actingAs($this->adminUser())
            ->get(route('groups.index', ['q' => 'training']))
            ->assertOk()
            ->getContent();
        preg_match('/aria-label="Narrow down">(.*?)<\/div>/s', $html, $m);
        $suggestions = $m[1] ?? '';

        // Terms held by some results narrow the search; a tag every result has does not.
        $this->assertStringContainsString(e(route('groups.index', ['q' => 'training area=norway'])), $suggestions);
        $this->assertStringContainsString(e(route('groups.index', ['q' => 'training tag:mentors'])), $suggestions);
        $this->assertStringNotContainsString(e(route('groups.index', ['q' => 'training tag:training'])), $suggestions);
    }

    public function test_ignores_a_search_that_is_not_a_string(): void
    {
        $this->group('Events team');

        $this->actingAs($this->adminUser())
            ->get(route('groups.index').'?q[]=events')
            ->assertOk()
            ->assertSee('Events team');
    }

    public function test_says_when_nothing_matches(): void
    {
        $this->group('Events team');

        $this->actingAs($this->adminUser())
            ->get(route('groups.index', ['q' => 'tag:nothing']))
            ->assertOk()
            ->assertSee('No groups match');
    }
}
