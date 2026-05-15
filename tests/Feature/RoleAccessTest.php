<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * RoleAccessTest
 *
 * Covers the 6 scenarios required by AC-21:
 *   (a) Unauthenticated access to a protected route → 302 to /login
 *   (b) Sales rep accessing an admin route → 403 Forbidden
 *   (c) Admin accessing an admin route → 200 OK
 *   (d) Expired / invalidated session mid-navigation → 302 to /login
 *   (e) User with a missing role claim → 302 to /login (AC-8)
 *   (f) Post-login redirect to originally requested URL (AC-14)
 *
 * Additional scenarios:
 *   (g) super-admin inherits admin access (AC-10)
 *   (h) Denied attempt is written to the security audit log (AC-12)
 *   (i) Admin URL is NOT stored as intended redirect for non-admins (AC-15)
 *   (j) Middleware covers all HTTP methods — DELETE also returns 403 (AC-9)
 *   (k) Sales rep can access their permitted routes (AC-4)
 */
class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    // ─── (a) Unauthenticated access ──────────────────────────────────────────

    /** AC-21a: Unauthenticated user hitting a protected route is sent to /login */
    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/admin/users')->assertRedirect('/login');
        $this->get('/proposals')->assertRedirect('/login');
    }

    // ─── (b) Sales rep accessing admin route ─────────────────────────────────

    /** AC-21b: Sales rep accessing /admin/* receives 403 */
    public function test_sales_rep_cannot_access_admin_routes(): void
    {
        $sales = User::factory()->sales()->create();

        $this->actingAs($sales)->get('/admin/users')->assertForbidden();
        $this->actingAs($sales)->get('/admin/users/'.$sales->id.'/edit')->assertForbidden();
    }

    /** AC-9: 403 also returned for non-GET methods on admin endpoints */
    public function test_sales_rep_cannot_use_any_http_method_on_admin_routes(): void
    {
        $sales  = User::factory()->sales()->create();
        $target = User::factory()->admin()->create();

        $this->actingAs($sales)->patch('/admin/users/'.$target->id, ['role' => 'sales'])->assertForbidden();
        $this->actingAs($sales)->delete('/admin/users/'.$target->id)->assertForbidden();
    }

    // ─── (c) Admin accessing admin route ─────────────────────────────────────

    /** AC-21c: Admin can successfully access admin routes */
    public function test_admin_can_access_admin_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/users')->assertOk();
    }

    // ─── (d) Expired / invalidated session mid-navigation ────────────────────

    /** AC-21d: After session invalidation the user is treated as unauthenticated */
    public function test_invalidated_session_redirects_to_login(): void
    {
        $user = User::factory()->sales()->create();

        // Authenticate, then immediately invalidate the session
        $this->actingAs($user);
        auth()->logout();

        // Next request without a valid session → redirect to login
        $this->get('/dashboard')->assertRedirect('/login');
    }

    // ─── (e) Missing role claim ───────────────────────────────────────────────

    /** AC-21e + AC-8: User with null role is redirected to /login by RoleMiddleware */
    public function test_user_with_null_role_is_redirected_to_login_on_role_protected_routes(): void
    {
        $user = User::factory()->noRole()->create();

        // A route guarded only by 'auth' (e.g. dashboard) can still be reached
        // because auth middleware doesn't inspect role.
        // But a route guarded by 'role:sales' should bounce them.
        $this->actingAs($user)
             ->get('/proposals')
             ->assertRedirect('/login');
    }

    // ─── (f) Post-login redirect to intended URL ──────────────────────────────

    /** AC-21f + AC-14: Unauthenticated user is redirected to their intended URL after login */
    public function test_unauthenticated_user_is_redirected_to_intended_url_after_login(): void
    {
        $user = User::factory()->sales()->create();

        // Attempt the protected URL while unauthenticated — stores intended
        $this->get('/dashboard')->assertRedirect('/login');

        // Now log in — should be sent to the originally intended URL
        $response = $this->post('/login', [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
    }

    // ─── (g) super-admin inherits admin access ────────────────────────────────

    /** AC-10: super-admin can access all admin-scoped routes */
    public function test_super_admin_can_access_admin_routes(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->get('/admin/users')->assertOk();
    }

    // ─── (h) Denied attempt is audit-logged ──────────────────────────────────

    /** AC-12: Security log receives a warning entry when access is denied */
    public function test_denied_access_is_written_to_security_log(): void
    {
        Log::shouldReceive('channel')
           ->with('security')
           ->once()
           ->andReturnSelf();

        Log::shouldReceive('warning')
           ->once()
           ->withArgs(function (string $message, array $context) {
               return str_contains($message, 'Unauthorised')
                   && isset($context['user_id'], $context['user_role'], $context['route'], $context['timestamp']);
           });

        $sales = User::factory()->sales()->create();
        $this->actingAs($sales)->get('/admin/users');
    }

    // ─── (i) Admin URL NOT stored as intended redirect for non-admins ─────────

    /** AC-15: When a sales rep hits an admin URL, it is NOT stored as intended */
    public function test_admin_url_is_not_stored_as_intended_redirect_for_sales_rep(): void
    {
        $sales = User::factory()->sales()->create();

        // Sales rep hits admin route — gets 403, intended URL must NOT be stored
        $this->actingAs($sales)->get('/admin/users')->assertForbidden();

        // Log out, then log back in — should land on dashboard, NOT /admin/users
        auth()->logout();

        $response = $this->post('/login', [
            'email'    => $sales->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
    }

    // ─── (k) Sales rep can access permitted routes ────────────────────────────

    /** AC-4: Sales rep can access their allowed routes without being blocked */
    public function test_sales_rep_can_access_their_own_routes(): void
    {
        $sales = User::factory()->sales()->create();

        $this->actingAs($sales)->get('/proposals')->assertOk();
        $this->actingAs($sales)->get('/proposals/create')->assertOk();
        $this->actingAs($sales)->get('/dashboard')->assertOk();
    }

    /** AC-4: Admin cannot access sales-only routes (role boundary works both ways) */
    public function test_admin_cannot_access_sales_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/proposals')->assertForbidden();
    }
}
