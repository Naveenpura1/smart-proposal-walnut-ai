<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

<<<<<<< HEAD
    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
=======
    public function test_sales_rep_can_register_and_is_redirected_to_dashboard(): void
    {
        $response = $this->post('/register', [
            'name'                  => 'Test Sales',
            'email'                 => 'sales@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
            'role'                  => 'sales',
>>>>>>> 9ad783d (Initial commit)
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
<<<<<<< HEAD
=======

    public function test_admin_can_register_and_is_redirected_to_admin_users(): void
    {
        $response = $this->post('/register', [
            'name'                  => 'Test Admin',
            'email'                 => 'admin@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
            'role'                  => 'admin',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.users.index', absolute: false));
    }

    public function test_registration_fails_without_role(): void
    {
        $response = $this->post('/register', [
            'name'                  => 'No Role',
            'email'                 => 'norole@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('role');
    }

    public function test_registration_fails_with_invalid_role(): void
    {
        $response = $this->post('/register', [
            'name'                  => 'Bad Role',
            'email'                 => 'badrole@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
            'role'                  => 'super-hacker',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('role');
    }

    public function test_registration_fails_with_short_password(): void
    {
        $response = $this->post('/register', [
            'name'                  => 'Short Pass',
            'email'                 => 'short@example.com',
            'password'              => 'abc',
            'password_confirmation' => 'abc',
            'role'                  => 'sales',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('password');
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        \App\Models\User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->post('/register', [
            'name'                  => 'Duplicate',
            'email'                 => 'taken@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
            'role'                  => 'sales',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }
>>>>>>> 9ad783d (Initial commit)
}
