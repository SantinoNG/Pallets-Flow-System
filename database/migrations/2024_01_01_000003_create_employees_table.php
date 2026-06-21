<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('employees', function (Blueprint $table) {
            $table->id('id_employee');
            $table->foreignId('id_user')->unique()->constrained('users', 'id_user');
            $table->foreignId('id_role')->constrained('roles', 'id_role');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('employees'); }
};
