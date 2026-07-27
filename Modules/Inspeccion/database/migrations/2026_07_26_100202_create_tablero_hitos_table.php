<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tablero_hitos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->foreignId('tablero_id')->constrained('tableros')->cascadeOnDelete();
            $table->foreignId('grupo_hito_id')->constrained('grupo_hitos');
            $table->foreignId('estado_avance_id')->constrained('estados_avance');
            $table->string('item', 20);
            $table->string('nombre');
            $table->decimal('peso', 5, 2);
            $table->date('plan_inicio')->nullable();
            $table->date('plan_fin')->nullable();
            $table->date('real_inicio')->nullable();
            $table->date('real_fin')->nullable();
            $table->string('responsable')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['tablero_id', 'item']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tablero_hitos');
    }
};
