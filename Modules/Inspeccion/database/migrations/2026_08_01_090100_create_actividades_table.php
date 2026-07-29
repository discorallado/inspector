<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADR 0009 §2.2: Actividad portada de axon (app/Models/Activity.php),
 * adaptada al esquema de Inspeccion — id autoincremental (no ULID, para
 * mantener la convención del resto del módulo) y solo tablero_id (sin
 * project_id duplicado; se deriva vía tablero->proyecto_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actividades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->foreignId('tablero_id')->constrained('tableros')->cascadeOnDelete();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->index(['tablero_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividades');
    }
};
