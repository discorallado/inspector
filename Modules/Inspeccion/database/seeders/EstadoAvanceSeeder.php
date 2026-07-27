<?php

namespace Modules\Inspeccion\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inspeccion\Models\EstadoAvance;

class EstadoAvanceSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['codigo' => 'pendiente', 'nombre' => 'Pendiente', 'valor' => 0, 'excluye_calculo' => false, 'orden' => 1],
            ['codigo' => 'en_proceso', 'nombre' => 'En proceso', 'valor' => 0.5, 'excluye_calculo' => false, 'orden' => 2],
            ['codigo' => 'completado', 'nombre' => 'Completado', 'valor' => 1, 'excluye_calculo' => false, 'orden' => 3],
            ['codigo' => 'na', 'nombre' => 'N/A', 'valor' => 0, 'excluye_calculo' => true, 'orden' => 4],
        ])->each(fn (array $datos) => EstadoAvance::query()->firstOrCreate(
            ['codigo' => $datos['codigo']],
            $datos,
        ));
    }
}
