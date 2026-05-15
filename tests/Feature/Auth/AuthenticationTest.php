<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

<<<<<<< HEAD
    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
=======
    public function test_sales_user_is_redirected_to_dashboard_after_login(): void
    {
        $user = User::factory()->sales()->create();

        $response = $this->post('/login', [
            'email'    => $user->email,
>>>>>>> 9ad783d (Initial commit)
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

<<<<<<< HEAD
=======
    public function test_admin_user_is_redirected_to_admin_users_after_login(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->post('/login', [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.users.index', absolute: false));
    }

>>>>>>> 9ad783d (Initial commit)
    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
<<<<<<< HEAD
            'email' => $user->email,
=======
            'email'    => $user->email,
>>>>>>> 9ad783d (Initial commit)
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
