<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Pedido del usuario: la confusión entre "qué es padre de qué" (¿TableroHito
 * es padre de Actividad?) venía de que el sistema viejo (TableroHito /
 * GrupoHito, deprecado desde ADR 0009/0011, pendiente de drop en PR9) y el
 * nuevo (Actividad / Tarea, portado de axon) cuelgan los dos de Tablero,
 * pero son árboles paralelos sin relación entre sí — el nombre "TableroHito"
 * no dejaba eso claro. Se renombra a HitoLegado/GrupoHitoLegado para que el
 * nombre mismo diga "esto es el sistema anterior, no una capa de Actividad".
 *
 * Solo se renombran las TABLAS acá — las columnas (tablero_id,
 * grupo_hito_id, tareas.tablero_hito_id) se mantienen igual: son
 * temporales de todos modos (se van completas en PR9), renombrarlas
 * también no aporta claridad adicional y sí agrega riesgo (tocar FKs ya
 * corridas) sin necesidad — Schema::rename() no rompe las FK existentes,
 * MariaDB actualiza la referencia interna sola.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('grupo_hitos', 'grupos_hitos_legados');
        Schema::rename('tablero_hitos', 'hitos_legados');
    }

    public function down(): void
    {
        Schema::rename('hitos_legados', 'tablero_hitos');
        Schema::rename('grupos_hitos_legados', 'grupo_hitos');
    }
};
