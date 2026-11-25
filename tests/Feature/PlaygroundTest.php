<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// The API playground shares the Breeze session with the workspace: no second login, a way back,
// and its Bearer token comes from the session.
class PlaygroundTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_the_same_login_page(): void
    {
        $this->get('/playground')->assertRedirect(route('login'));
        $this->postJson('/playground/token')->assertUnauthorized();
    }

    public function test_logged_in_user_sees_playground_with_link_back_to_workspace_and_no_login_form(): void
    {
        $user = User::factory()->create(['name' => 'Dimas']);

        $this->actingAs($user)->get('/playground')
            ->assertOk()
            ->assertSee(route('workspace'), false)
            ->assertSee('Dimas')
            ->assertDontSee('id="loginForm"', false)
            ->assertDontSee('type="password"', false);
    }

    public function test_session_issues_a_working_bearer_token_and_revokes_the_previous_one(): void
    {
        $user = User::factory()->create();

        $first = $this->actingAs($user)->postJson('/playground/token')
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.id', $user->id)
            ->json('access_token');
        $second = $this->actingAs($user)->postJson('/playground/token')->json('access_token');

        // Only one playground token at a time, so reloading the page does not pile tokens up
        $this->assertSame(1, $user->tokens()->where('name', 'playground')->count());

        // The new token works on the token-protected API, the old one was revoked.
        // Forget the guards between calls, otherwise the session user from actingAs() is reused.
        $this->app['auth']->forgetGuards();
        $this->withToken($second)->getJson('/api/user')->assertOk()->assertJsonPath('id', $user->id);
        $this->app['auth']->forgetGuards();
        $this->withToken($first)->getJson('/api/user')->assertUnauthorized();
    }

    public function test_navigation_links_to_both_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/workspace')->assertSee(route('playground'), false);
        $this->actingAs($user)->get('/playground')->assertSee(route('workspace'), false);
    }
}
