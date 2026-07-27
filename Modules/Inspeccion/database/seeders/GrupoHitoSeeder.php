<?php

namespace Modules\Inspeccion\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inspeccion\Models\GrupoHito;

class GrupoHitoSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            'Recepción de Materiales',
            'Armado de Tablero',
            'Cableado',
            'Pruebas FAT',
            'Despacho',
        ])->each(fn (string $nombre, int $orden) => GrupoHito::query()->firstOrCreate(
            ['nombre' => $nombre],
            ['orden' => $orden + 1, 'activo' => true],
        ));
    }
}
