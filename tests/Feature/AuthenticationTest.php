<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_from_the_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_login_form_is_available(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSeeText('Bienvenido')
            ->assertSee('name="_token"', false);
    }

    public function test_user_can_sign_in_view_dashboard_and_sign_out(): void
    {
        $user = User::factory()->create([
            'email' => 'operador@sipred.local',
            'password' => 'clave-de-prueba',
        ]);

        $this->post('/login', [
            'email' => 'operador@sipred.local',
            'password' => 'clave-de-prueba',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_access_at);

        $this->get('/dashboard')
            ->assertOk()
            ->assertSeeText('Panel de situación');

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_invalid_credentials_return_an_error(): void
    {
        User::factory()->create([
            'email' => 'admin@sipred.local',
            'password' => 'clave-correcta',
        ]);

        $this->from('/login')->post('/login', [
            'email' => 'admin@sipred.local',
            'password' => 'incorrecta',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');
    }

    public function test_inactive_user_cannot_sign_in(): void
    {
        $user = User::factory()->create([
            'email' => 'inactivo@sipred.local',
            'password' => 'clave-de-prueba',
            'status' => User::STATUS_INACTIVE,
        ]);

        $this->from('/login')->post('/login', [
            'email' => 'inactivo@sipred.local',
            'password' => 'clave-de-prueba',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertNull($user->fresh()->last_access_at);
    }
}
