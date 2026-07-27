<?php

namespace Modules\Inspeccion\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inspeccion\Models\EstadoObservacion;

class EstadoObservacionSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['codigo' => 'pendiente', 'nombre' => 'Pendiente', 'es_terminal' => false, 'orden' => 1],
            ['codigo' => 'subsanada_ok', 'nombre' => 'Subsanada (OK)', 'es_terminal' => true, 'orden' => 2],
            ['codigo' => 'informativa', 'nombre' => 'Informativa', 'es_terminal' => true, 'orden' => 3],
        ])->each(fn (array $datos) => EstadoObservacion::query()->firstOrCreate(
            ['codigo' => $datos['codigo']],
            $datos,
        ));
    }
}
