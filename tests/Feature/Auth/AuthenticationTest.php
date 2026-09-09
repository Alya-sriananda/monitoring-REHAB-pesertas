<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered()
    {
        $response = $this->get(route('login'));

        $response->assertOk();
    }

    public function test_users_can_authenticate_and_reach_dashboard_when_password_changed()
    {
        $user = User::factory()->create([
            'must_change_password' => false,
        ]);

        $response = $this->post(route('login.store'), [
            'npp' => $user->npp,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        // Should redirect to dashboard because must_change_password is false
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_are_redirected_to_change_password_when_must_change_password_is_true()
    {
        $user = User::factory()->create([
            'must_change_password' => true,
        ]);

        $response = $this->post(route('login.store'), [
            'npp' => $user->npp,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        // Since must_change_password is true, accessing dashboard will redirect to security edit
        $dashboardResponse = $this->get(route('dashboard'));
        $dashboardResponse->assertRedirect(route('security.edit'));
    }

    public function test_users_can_not_authenticate_with_invalid_password()
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'npp' => $user->npp,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_not_authenticate_if_inactive()
    {
        $user = User::factory()->create([
            'aktif' => false,
        ]);

        $this->post(route('login.store'), [
            'npp' => $user->npp,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_users_are_rate_limited()
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), [
                'npp' => $user->npp,
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post(route('login.store'), [
            'npp' => $user->npp,
            'password' => 'wrong-password',
        ]);

        $response->assertTooManyRequests();
    }
}
