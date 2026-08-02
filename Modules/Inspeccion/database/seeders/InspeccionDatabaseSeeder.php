<?php

namespace Modules\Inspeccion\Database\Seeders;

use Illuminate\Database\Seeder;

class InspeccionDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            GrupoHitoLegadoSeeder::class,
            EstadoAvanceSeeder::class,
            EspecialidadSeeder::class,
            TipoObservacionSeeder::class,
            SeveridadSeeder::class,
            EstadoObservacionSeeder::class,
            EstadoCambioSeeder::class,
            ResultadoChecklistSeeder::class,
            TransicionEstadoPermitidaSeeder::class,
            ChecklistIec61439Seeder::class,
            SeguimientoIntegracionTablerosSeeder::class,
        ]);
    }
}
