<?php

namespace Modules\Inspeccion\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inspeccion\Models\Especialidad;
use Modules\Inspeccion\Models\EstadoObservacion;
use Modules\Inspeccion\Models\Observacion;
use Modules\Inspeccion\Models\TipoObservacion;
use Modules\Inspeccion\Models\VisitaInspeccion;

class ObservacionFactory extends Factory
{
    protected $model = Observacion::class;

    public function definition(): array
    {
        return [
            'visita_inspeccion_id' => VisitaInspeccion::factory(),
            'especialidad_id' => Especialidad::factory(),
            'tipo_observacion_id' => TipoObservacion::factory(),
            'estado_observacion_id' => EstadoObservacion::factory(),
            'descripcion' => fake()->paragraph(),
            'responsable' => fake()->name(),
            'fecha_compromiso' => fake()->optional()->dateTimeBetween('now', '+30 days')?->format('Y-m-d'),
        ];
    }
}
