<?php

namespace Modules\Inspeccion\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inspeccion\Models\Especialidad;

class EspecialidadSeeder extends Seeder
{
    public function run(): void
    {
        collect(['Eléctrico', 'Mecánico', 'Control', 'Documentación', 'HSE', 'Otro'])
            ->each(fn (string $nombre, int $orden) => Especialidad::query()->firstOrCreate(
                ['nombre' => $nombre],
                ['orden' => $orden + 1, 'activo' => true],
            ));
    }
}
