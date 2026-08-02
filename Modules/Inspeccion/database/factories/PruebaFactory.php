<?php

namespace Modules\Inspeccion\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inspeccion\Models\ChecklistEjecucion;
use Modules\Inspeccion\Models\ChecklistTemplate;
use Modules\Inspeccion\Models\Tablero;
use Modules\Inspeccion\Models\VisitaInspeccion;

class ChecklistEjecucionFactory extends Factory
{
    protected $model = ChecklistEjecucion::class;

    public function definition(): array
    {
        return [
            'visita_inspeccion_id' => VisitaInspeccion::factory(),
            'tablero_id' => Tablero::factory(),
            'checklist_template_id' => ChecklistTemplate::factory(),
        ];
    }
}
