<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADR 0009 §2.2: Tarea portada de axon (app/Models/Task.php), adaptada al
 * esquema de Inspeccion. `status`/`priority` como string (cast a enum en
 * el modelo, igual que axon) para que el guard genérico pueda validar
 * transiciones de `status` por código. `peso` y `real_inicio`/`real_fin`
 * son extensión de Inspeccion (axon no las tiene) — nullable para no
 * romper un futuro merge.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tareas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->foreignId('actividad_id')->constrained('actividades')->cascadeOnDelete();
            $table->foreignId('parent_tarea_id')->nullable()->constrained('tareas')->nullOnDelete();
            $table->string('code');
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('status')->default('pendiente');
            $table->string('priority')->default('media');
            $table->unsignedSmallInteger('orden')->default(0);
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->decimal('actual_hours', 8, 2)->nullable();
            $table->decimal('peso', 5, 2)->nullable();
            $table->date('real_inicio')->nullable();
            $table->date('real_fin')->nullable();
            $table->timestamps();

            $table->index(['actividad_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tareas');
    }
};
