<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La tabla `users` la creo a mano el profesor (2024_01_01_000002) y le faltan
 * dos columnas que Laravel/Breeze dan por sentado:
 *
 *   - email_verified_at : la castea User::casts() y la usa EmailVerificationTest
 *   - remember_token    : la usa el check "Recuerdame" del login de Breeze
 *
 * Sin ellas fallan 20 de 25 tests con:
 *   table users has no column named email_verified_at
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('password');
            }

            if (!Schema::hasColumn('users', 'remember_token')) {
                $table->rememberToken()->after('password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $drop = [];

            if (Schema::hasColumn('users', 'email_verified_at')) {
                $drop[] = 'email_verified_at';
            }

            if (Schema::hasColumn('users', 'remember_token')) {
                $drop[] = 'remember_token';
            }

            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
