<?php

use Illuminate\Database\QueryException;
use Modules\Inspeccion\Database\Seeders\EstadoAvanceSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoCambioSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\TransicionEstadoPermitidaSeeder;
use Modules\Inspeccion\Enums\TaskStatus;
use Modules\Inspeccion\Models\Actividad;
use Modules\Inspeccion\Models\Tarea;
use Modules\Inspeccion\Models\TransicionEstadoPermitida;
use Modules\Inspeccion\Services\TransicionEstadoGuard;

/**
 * /qa sobre PR4 (ADR 0010): casos borde del dominio Actividad/Tarea que
 * ningún test cubría todavía. No son bugs a corregir por QA — documentan
 * el comportamiento actual del sistema para que una futura decisión de
 * diseño (constraint, validación) tenga una regresión clara que actualizar.
 */
beforeEach(function () {
    $this->seed(EstadoAvanceSeeder::class);
    $this->seed(EstadoObservacionSeeder::class);
    $this->seed(EstadoCambioSeeder::class);
    $this->seed(TransicionEstadoPermitidaSeeder::class);
});

it('code es único dentro de la misma Actividad (fix de /revisor, ADR 0012)', function () {
    // unique(['actividad_id', 'code']) agregado sobre el hallazgo original
    // ("code no tiene índice único") — mismo patrón que tableros usa con
    // unique(['proyecto_id', 'tag']): único dentro del padre, no global.
    $actividad = Actividad::factory()->create();
    Tarea::factory()->for($actividad)->create(['code' => 'TAR-001']);

    expect(fn () => Tarea::factory()->for($actividad)->create(['code' => 'TAR-001']))
        ->toThrow(QueryException::class);
});

it('GAP residual: code SÍ puede repetirse entre Tareas de distinta Actividad', function () {
    // El unique de arriba es compuesto (actividad_id+code), no global —
    // decisión deliberada (mismo criterio que tableros.tag), no un
    // descuido. Si en algún momento el negocio necesita que "code" sea
    // legible-único en todo el sistema (p. ej. para el Gantt/reportes de
    // PR7/PR8), esto queda sin garantizar — decisión de diseño futura,
    // no algo que QA/revisor deba resolver unilateralmente.
    $t1 = Tarea::factory()->create(['code' => 'TAR-001']);
    $t2 = Tarea::factory()->create(['code' => 'TAR-001']);

    expect($t1->code)->toBe($t2->code);
    expect($t1->actividad_id)->not->toBe($t2->actividad_id);
    expect(Tarea::query()->where('code', 'TAR-001')->count())->toBe(2);
});

it('GAP: nada impide que una Tarea sea su propio padre (ciclo trivial)', function () {
    // parent_tarea_id no valida ciclos (tampoco lo hace axon). Un ciclo
    // real (A padre de B, B padre de A) o este caso trivial (A padre de
    // sí misma) haría loop infinito en cualquier recorrido recursivo
    // futuro (indentado del Gantt, conteo de subtareas). Documentado
    // para que PR6-PR8 decidan si vale la pena validarlo antes de que
    // haya una UI que permita crearlo por accidente.
    $tarea = Tarea::factory()->create();

    $tarea->update(['parent_tarea_id' => $tarea->id]);

    expect($tarea->refresh()->parent_tarea_id)->toBe($tarea->id);
});

it('un status inválido explota al leerlo (cast a enum), no lo acepta silenciosamente', function () {
    // No es un gap — es la red de seguridad que sí existe: si algo
    // (p. ej. un import futuro en PR5) escribe un status que no es
    // ninguno de los 5 casos del enum, el cast lo rechaza fuerte en vez
    // de dejarlo pasar como string libre.
    $tarea = Tarea::factory()->create();

    $tarea->setRawAttributes(array_merge($tarea->getAttributes(), ['status' => 'no_existe']), true);

    expect(fn () => $tarea->status)->toThrow(ValueError::class);
});

it('el guard permite quedarse en el mismo status sin necesitar una transición sembrada', function () {
    // Cortocircuito de puedeTransicionarPorCodigo() cuando origen ===
    // destino — probado directo contra el guard (no vía TareaObserver,
    // que ya ni siquiera llega a llamarlo si status no está dirty) para
    // que quede cubierto incluso si algún caller futuro sí llama al
    // guard con origen === destino a propósito.
    $guard = new TransicionEstadoGuard;

    expect($guard->puedeTransicionarPorCodigo(
        TransicionEstadoPermitida::TIPO_TAREA_STATUS,
        TaskStatus::Bloqueada->value,
        TaskStatus::Bloqueada->value,
    ))->toBeTrue();
});
