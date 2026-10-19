/**
 * Tabla de auditoria de owen-it/laravel-auditing.
 *
 * App\Models\User implementa OwenIt\Auditing\Contracts\Auditable y
 * config/audit.php tiene 'enabled' => true, asi que CADA create/update/delete
 * de un usuario escribe una fila aca. Sin esta migracion la app revienta con:
 *
 *     Table '<base>.audits' doesn't exist
 *
 * Es el stub oficial del paquete (vendor/owen-it/laravel-auditing/database/
 * migrations/audits.stub) copiado tal cual, que ademas lee el nombre de tabla
 * y de conexion de config/audit.php.
 */
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $connection = config('audit.drivers.database.connection', config('database.default'));
        $table = config('audit.drivers.database.table', 'audits');

        Schema::connection($connection)->create($table, function (Blueprint $table) {

            $morphPrefix = config('audit.user.morph_prefix', 'user');

            $table->bigIncrements('id');
            $table->string($morphPrefix . '_type')->nullable();
            $table->unsignedBigInteger($morphPrefix . '_id')->nullable();
            $table->string('event');
            $table->morphs('auditable');
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->text('url')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 1023)->nullable();
            $table->string('tags')->nullable();
            $table->timestamps();

            $table->index([$morphPrefix . '_id', $morphPrefix . '_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = config('audit.drivers.database.connection', config('database.default'));
        $table = config('audit.drivers.database.table', 'audits');

        Schema::connection($connection)->drop($table);
    }
};
