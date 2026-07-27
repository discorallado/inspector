<?php

namespace Modules\Inspeccion\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\VisitaInspeccion;

class VisitaInspeccionFactory extends Factory
{
    protected $model = VisitaInspeccion::class;

    public function definition(): array
    {
        return [
            'proyecto_id' => Proyecto::factory(),
            'inspector_id' => User::factory(),
            'fecha' => fake()->date(),
            'observaciones_generales' => fake()->optional()->sentence(),
        ];
    }
}
