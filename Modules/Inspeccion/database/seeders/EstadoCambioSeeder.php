<?php

namespace Modules\Inspeccion\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inspeccion\Models\EstadoCambio;

class EstadoCambioSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['codigo' => 'propuesto', 'nombre' => 'Propuesto', 'orden' => 1],
            ['codigo' => 'aprobado', 'nombre' => 'Aprobado', 'orden' => 2],
            ['codigo' => 'rechazado', 'nombre' => 'Rechazado', 'orden' => 3],
            ['codigo' => 'implementado', 'nombre' => 'Implementado', 'orden' => 4],
        ])->each(fn (array $datos) => EstadoCambio::query()->firstOrCreate(
            ['codigo' => $datos['codigo']],
            $datos,
        ));
    }
}
