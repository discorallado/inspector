<?php

namespace Modules\Inspeccion\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inspeccion\Models\ResultadoChecklist;

class ResultadoChecklistSeeder extends Seeder
{
    public function run(): void
    {
        collect(['Cumple', 'No Cumple', 'N/A'])
            ->each(fn (string $nombre, int $orden) => ResultadoChecklist::query()->firstOrCreate(
                ['nombre' => $nombre],
                ['orden' => $orden + 1],
            ));
    }
}
