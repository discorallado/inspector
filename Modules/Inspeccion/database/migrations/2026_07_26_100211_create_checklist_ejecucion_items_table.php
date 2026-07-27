<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_ejecucion_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_ejecucion_id')->constrained('checklist_ejecuciones')->cascadeOnDelete();
            // Nullable: solo trazabilidad. El snapshot de abajo es la fuente de verdad.
            $table->foreignId('checklist_item_library_id')->nullable()->constrained('checklist_item_libraries')->nullOnDelete();
            $table->string('categoria');
            $table->text('item');
            $table->string('referencia_normativa')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->foreignId('resultado_checklist_id')->nullable()->constrained('resultados_checklist');
            $table->text('observacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_ejecucion_items');
    }
};
