<?php

namespace Modules\Inspeccion\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inspeccion\Models\Severidad;

class SeveridadSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['codigo' => 'critica', 'nombre' => 'Crítica', 'orden' => 1],
            ['codigo' => 'mayor', 'nombre' => 'Mayor', 'orden' => 2],
            ['codigo' => 'menor', 'nombre' => 'Menor', 'orden' => 3],
        ])->each(fn (array $datos) => Severidad::query()->firstOrCreate(
            ['codigo' => $datos['codigo']],
            $datos,
        ));
    }
}
