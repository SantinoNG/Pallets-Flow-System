<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id('id_receipt');
            $table->foreignId('id_sale')->constrained('sales', 'id_sale');
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('receipts'); }
};
