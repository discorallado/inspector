<?php

namespace Modules\Inspeccion\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inspeccion\Models\TipoObservacion;

class TipoObservacionSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['codigo' => 'consulta_integrador', 'nombre' => 'Consulta al Integrador', 'requiere_severidad' => false, 'orden' => 1],
            ['codigo' => 'sugerencia', 'nombre' => 'Sugerencia', 'requiere_severidad' => false, 'orden' => 2],
            ['codigo' => 'observacion_subsanar', 'nombre' => 'Observación a Subsanar', 'requiere_severidad' => true, 'orden' => 3],
        ])->each(fn (array $datos) => TipoObservacion::query()->firstOrCreate(
            ['codigo' => $datos['codigo']],
            $datos,
        ));
    }
}
