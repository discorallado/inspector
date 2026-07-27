<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tableros', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->foreignId('proyecto_id')->constrained('proyectos')->cascadeOnDelete();
            $table->string('tag', 50);
            $table->string('nombre');
            $table->string('fabricante')->nullable();
            $table->string('oc_contrato')->nullable();
            $table->decimal('avance_global', 5, 2)->nullable();
            $table->timestamp('avance_calculado_at')->nullable();
            $table->timestamps();

            $table->unique(['proyecto_id', 'tag']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tableros');
    }
};
