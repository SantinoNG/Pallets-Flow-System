<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas de infraestructura que Laravel da por ascertain pero que el repo no
 * traia, mientras que el .env.example pedia usarlas en base de datos:
 *
 *     SESSION_DRIVER=database   -> sessions
 *     QUEUE_CONNECTION=database -> jobs, job_batches, failed_jobs
 *     (Breeze)                  -> password_reset_tokens
 *
 * Sin esta migracion el proyecto revienta con:
 *     Table '<base>.sessions' doesn't exist
 *     Table '<base>.password_reset_tokens' doesn't exist
 *
 * Nota: las de cache (cache, cache_locks) NO van aqui, ya existen en
 * 2026_06_21_230236_create_cache_table.php.
 *
 * Esta migracion reemplaza el SQL que se habia ejecutado a mano sobre MySQL.
 * Con ella, `php artisan migrate:fresh` deja la base completa y usable.
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- Sesiones (SESSION_DRIVER=database) ---
        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }

        // --- Colas (QUEUE_CONNECTION=database) ---
        if (!Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->id();
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        if (!Schema::hasTable('job_batches')) {
            Schema::create('job_batches', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('name');
                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');
                $table->longText('failed_job_ids');
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
            });
        }

        if (!Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }

        // --- Recuperacion de contrasena (Breeze) ---
        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('sessions');
    }
};
