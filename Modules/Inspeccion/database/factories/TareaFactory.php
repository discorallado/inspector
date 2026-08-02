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

    /**
     * Contador en vez de fake()->bothify('TAR-###') (3 dígitos al azar,
     * 1000 combinaciones): con el unique(actividad_id, code) agregado en
     * /qa, una colisión ya no es solo un dato feo — rompe el insert. Un
     * contador de proceso garantiza code único en todo el test run, más
     * estricto de lo que pide el constraint (que es por actividad) pero
     * simple y sin estado de Faker que gestionar.
     */
    private static int $contadorCode = 0;

    public function definition(): array
    {
        return [
            'actividad_id' => Actividad::factory(),
            'code' => 'TAR-'.str_pad((string) (++self::$contadorCode), 4, '0', STR_PAD_LEFT),
            'nombre' => fake()->sentence(4),
            'descripcion' => fake()->optional()->paragraph(),
            'status' => TaskStatus::Pendiente,
            'priority' => TaskPriority::Media,
            'orden' => fake()->numberBetween(0, 10),
            'peso' => fake()->randomFloat(2, 1, 20),
        ];
    }
}
