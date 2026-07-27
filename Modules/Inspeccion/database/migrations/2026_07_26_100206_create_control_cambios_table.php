<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('control_cambios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->foreignId('tablero_id')->constrained('tableros')->cascadeOnDelete();
            $table->foreignId('estado_cambio_id')->constrained('estados_cambio');
            $table->text('descripcion');
            $table->string('responsable')->nullable();
            $table->date('fecha');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_cambios');
    }
};
