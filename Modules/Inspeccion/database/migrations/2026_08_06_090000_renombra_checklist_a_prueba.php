<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADR de rename Checklist->Prueba: el checklist de inspección (IEC 61439)
 * queda descartado por ahora — se reutiliza la misma infraestructura
 * (una sola, sin duplicar el patrón snapshot) para "Pruebas", asociadas
 * a Tablero en vez de a VisitaInspeccion (de ahí visita_inspeccion_id
 * nullable). Sin datos reales que migrar: checklist_ejecuciones y
 * visitas_inspeccion estaban en 0 filas al momento de este cambio, solo
 * el catálogo (1 template, 8 ítems IEC 61439) tenía contenido, y ese
 * catálogo se reemplaza de todos modos.
 *
 * Se renombran tablas Y columnas (a diferencia del rename
 * TableroHito/GrupoHito->Legado, que solo tocó tablas): ahí las columnas
 * eran puente temporal camino a borrarse en PR9, acá "Prueba" es el
 * nombre definitivo hacia adelante, vale la pena que las columnas
 * calcen desde ahora.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('checklist_item_libraries', 'prueba_item_libraries');
        Schema::rename('checklist_templates', 'prueba_templates');
        Schema::rename('checklist_template_items', 'prueba_template_items');

        Schema::table('prueba_template_items', function (Blueprint $table) {
            $table->renameColumn('checklist_template_id', 'prueba_template_id');
            $table->renameColumn('checklist_item_library_id', 'prueba_item_library_id');
        });

        Schema::rename('checklist_ejecuciones', 'pruebas');

        Schema::table('pruebas', function (Blueprint $table) {
            $table->renameColumn('checklist_template_id', 'prueba_template_id');
        });

        // Nullable: el punto de entrada de Prueba pasa a ser Tablero
        // (PruebasRelationManager), no VisitaInspeccion — ya no siempre
        // hay una visita elegida al crear una Prueba.
        Schema::table('pruebas', function (Blueprint $table) {
            $table->foreignId('visita_inspeccion_id')->nullable()->change();
        });

        Schema::rename('checklist_ejecucion_items', 'prueba_items');

        Schema::table('prueba_items', function (Blueprint $table) {
            $table->renameColumn('checklist_ejecucion_id', 'prueba_id');
            $table->renameColumn('checklist_item_library_id', 'prueba_item_library_id');
        });
    }

    public function down(): void
    {
        Schema::table('prueba_items', function (Blueprint $table) {
            $table->renameColumn('prueba_id', 'checklist_ejecucion_id');
            $table->renameColumn('prueba_item_library_id', 'checklist_item_library_id');
        });

        Schema::rename('prueba_items', 'checklist_ejecucion_items');

        Schema::table('pruebas', function (Blueprint $table) {
            $table->foreignId('visita_inspeccion_id')->nullable(false)->change();
        });

        Schema::table('pruebas', function (Blueprint $table) {
            $table->renameColumn('prueba_template_id', 'checklist_template_id');
        });

        Schema::rename('pruebas', 'checklist_ejecuciones');

        Schema::table('prueba_template_items', function (Blueprint $table) {
            $table->renameColumn('prueba_template_id', 'checklist_template_id');
            $table->renameColumn('prueba_item_library_id', 'checklist_item_library_id');
        });

        Schema::rename('prueba_template_items', 'checklist_template_items');
        Schema::rename('prueba_templates', 'checklist_templates');
        Schema::rename('prueba_item_libraries', 'checklist_item_libraries');
    }
};
