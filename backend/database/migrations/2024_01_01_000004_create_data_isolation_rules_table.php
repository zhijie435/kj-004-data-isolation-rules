<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_isolation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('type', ['tenant', 'role', 'field', 'custom']);
            $table->string('model', 200);
            $table->string('role', 100)->nullable();
            $table->string('field', 100)->nullable();
            $table->string('operator', 20)->nullable();
            $table->text('value')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->string('description', 500)->nullable();
            $table->timestamps();

            $table->index(['type', 'model']);
            $table->index(['role', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_isolation_rules');
    }
};
