<?php

namespace Modules\Inspeccion\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inspeccion\Models\GrupoHito;

class GrupoHitoSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            'Armado de Tablero',
            'Montaje de Protecciones',
            'Fabricación y Montaje de Barras',
            'Alambrado del Tablero',
            'Rotulación',
            'Pruebas FAT',
            'Embalaje',
            'Despacho',
        ])->each(fn (string $nombre, int $orden) => GrupoHito::query()->firstOrCreate(
            ['nombre' => $nombre],
            ['orden' => $orden + 1, 'activo' => true],
        ));
    }
}
