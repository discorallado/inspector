<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_ejecuciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->foreignId('visita_inspeccion_id')->constrained('visitas_inspeccion')->cascadeOnDelete();
            $table->foreignId('tablero_id')->constrained('tableros')->cascadeOnDelete();
            $table->foreignId('checklist_template_id')->constrained('checklist_templates');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_ejecuciones');
    }
};
