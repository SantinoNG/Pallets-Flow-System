<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    /**
     * Catalogo real de roles del proyecto.
     *
     * Antes usaba fake()->jobTitle(), que generaba nombres del tipo
     * "Senior Developer" o "Chief Executive" que NO existen en la tabla
     * `roles` del sistema. Un rol con nombre inventado no corresponde a
     * ningun permiso ni a ninguna regla de negocio, asi que se usa el
     * catalogo real.
     */
    public const ROLES = [
        'Jefe',
        'Administrador',
        'Encargado',
        'Cliente',
        'Vendedor',
        'Deposito',
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role_name'   => fake()->randomElement(self::ROLES),
            'description' => fake()->sentence(3),
        ];
    }
}
