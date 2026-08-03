<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create(config('filament-comments.table_name', 'filament_comments'), function (Blueprint $table) {
            $table->id();
            // organization_id nullable: convención del resto del módulo
            // (CLAUDE.md §3.3) aplicada acá aunque la tabla la trae el
            // paquete parallax/filament-comments — no la usa el paquete en
            // ninguna query propia, es gratis dejarla lista para cuando
            // este módulo se integre a axon (multi-tenant real).
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->longText('comment');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists(config('filament-comments.table_name', 'filament_comments'));
    }
};
