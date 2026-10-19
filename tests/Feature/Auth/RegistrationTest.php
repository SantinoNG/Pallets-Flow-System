<?php

namespace Tests\Feature\Auth;

use Database\Seeders\RoleSeeder;
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

    public function test_new_users_can_register(): void
    {
        // users.id_role es NOT NULL con Foreign Key a roles.id_role, y
        // RegisteredUserController le asigna el rol "Cliente". Si la tabla
        // roles esta vacia el registro falla, asi que hay que sembrarla.
        //
        // Antes esto no hacia falta porque el controller no seteaba id_role
        // (y por eso el registro estaba roto en la app real).
        $this->seed(RoleSeeder::class);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        // El usuario nuevo debe haber quedado con el rol Cliente.
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'id_role' => \App\Models\Role::where('role_name', 'Cliente')->value('id_role'),
        ]);
    }
}
