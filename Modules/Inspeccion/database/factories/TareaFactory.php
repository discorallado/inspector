<?php

namespace Modules\Inspeccion\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inspeccion\Enums\TaskPriority;
use Modules\Inspeccion\Enums\TaskStatus;
use Modules\Inspeccion\Models\Actividad;
use Modules\Inspeccion\Models\Tarea;

class TareaFactory extends Factory
{
    protected $model = Tarea::class;

    public function definition(): array
    {
        return [
            'actividad_id' => Actividad::factory(),
            'code' => strtoupper(fake()->bothify('TAR-###')),
            'nombre' => fake()->sentence(4),
            'descripcion' => fake()->optional()->paragraph(),
            'status' => TaskStatus::Pendiente,
            'priority' => TaskPriority::Media,
            'orden' => fake()->numberBetween(0, 10),
            'peso' => fake()->randomFloat(2, 1, 20),
        ];
    }
}
