<?php

use Illuminate\Support\Facades\Artisan;
use Modules\Inspeccion\Database\Seeders\EstadoAvanceSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoCambioSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\TransicionEstadoPermitidaSeeder;
use Modules\Inspeccion\Enums\TaskStatus;
use Modules\Inspeccion\Models\Actividad;
use Modules\Inspeccion\Models\EstadoAvance;
use Modules\Inspeccion\Models\GrupoHito;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\Tablero;
use Modules\Inspeccion\Models\TableroHito;
use Modules\Inspeccion\Models\Tarea;

/**
 * ADR 0009 §2.5 / PR5: comando inspeccion:migrar-hitos-a-tareas.
 */
beforeEach(function () {
    $this->seed(EstadoAvanceSeeder::class);
    $this->seed(EstadoObservacionSeeder::class);
    $this->seed(EstadoCambioSeeder::class);
    $this->seed(TransicionEstadoPermitidaSeeder::class);

    $this->tablero = Tablero::factory()->for(Proyecto::factory())->create(['tag' => 'T1']);
    $this->grupo = GrupoHito::factory()->create(['nombre' => 'Fase 1', 'orden' => 1]);
});

it('convierte un GrupoHito usado por el Tablero en una Actividad', function () {
    TableroHito::withoutEvents(fn () => TableroHito::factory()->for($this->tablero)->for($this->grupo)->create([
        'estado_avance_id' => EstadoAvance::query()->where('codigo', 'pendiente')->value('id'),
        'item' => '1.1',
    ]));

    Artisan::call('inspeccion:migrar-hitos-a-tareas');

    $actividad = Actividad::query()->where('tablero_id', $this->tablero->id)->first();
    expect($actividad)->not->toBeNull();
    expect($actividad->nombre)->toBe('Fase 1');
    expect($actividad->orden)->toBe(1);
});

it('convierte un TableroHito en una Tarea, preservando peso y fechas reales', function () {
    TableroHito::withoutEvents(fn () => TableroHito::factory()->for($this->tablero)->for($this->grupo)->create([
        'estado_avance_id' => EstadoAvance::query()->where('codigo', 'en_proceso')->value('id'),
        'item' => '1.1',
        'nombre' => 'Fabricación de estructura',
        'peso' => 12.5,
        'real_inicio' => '2026-01-10',
        'real_fin' => null,
    ]));

    Artisan::call('inspeccion:migrar-hitos-a-tareas');

    $tarea = Tarea::query()->where('code', 'T1-1.1')->first();
    expect($tarea)->not->toBeNull();
    expect($tarea->nombre)->toBe('Fabricación de estructura');
    expect($tarea->status)->toBe(TaskStatus::EnProgreso);
    expect((float) $tarea->peso)->toBe(12.5);
    expect($tarea->real_inicio->toDateString())->toBe('2026-01-10');
    expect($tarea->real_fin)->toBeNull();
});

it('mapea cada EstadoAvance.codigo al TaskStatus correspondiente, incluyendo na -> Bloqueada', function () {
    $casos = [
        '1.1' => ['pendiente', TaskStatus::Pendiente],
        '1.2' => ['en_proceso', TaskStatus::EnProgreso],
        '1.3' => ['completado', TaskStatus::Completada],
        '1.4' => ['na', TaskStatus::Bloqueada],
    ];

    foreach ($casos as $item => [$codigo, $_]) {
        TableroHito::withoutEvents(fn () => TableroHito::factory()->for($this->tablero)->for($this->grupo)->create([
            'estado_avance_id' => EstadoAvance::query()->where('codigo', $codigo)->value('id'),
            'item' => $item,
        ]));
    }

    Artisan::call('inspeccion:migrar-hitos-a-tareas');

    foreach ($casos as $item => [$codigo, $esperado]) {
        $tarea = Tarea::query()->where('code', "T1-{$item}")->first();
        expect($tarea->status)->toBe($esperado);
    }
});

it('no revienta con un hito ya Completado (bypassa TareaObserver en el import histórico)', function () {
    TableroHito::withoutEvents(fn () => TableroHito::factory()->for($this->tablero)->for($this->grupo)->create([
        'estado_avance_id' => EstadoAvance::query()->where('codigo', 'completado')->value('id'),
        'item' => '1.1',
    ]));

    Artisan::call('inspeccion:migrar-hitos-a-tareas');

    $tarea = Tarea::query()->where('code', 'T1-1.1')->first();
    expect($tarea->status)->toBe(TaskStatus::Completada);
});

it('es idempotente: correrlo dos veces no duplica Actividades ni Tareas', function () {
    TableroHito::withoutEvents(fn () => TableroHito::factory()->for($this->tablero)->for($this->grupo)->create([
        'estado_avance_id' => EstadoAvance::query()->where('codigo', 'pendiente')->value('id'),
        'item' => '1.1',
    ]));

    Artisan::call('inspeccion:migrar-hitos-a-tareas');
    $tareaId = Tarea::query()->where('code', 'T1-1.1')->value('id');

    Artisan::call('inspeccion:migrar-hitos-a-tareas');

    expect(Actividad::query()->count())->toBe(1);
    expect(Tarea::query()->count())->toBe(1);
    expect(Tarea::query()->where('code', 'T1-1.1')->value('id'))->toBe($tareaId);
});

it('no borra ni modifica TableroHito/GrupoHito/EstadoAvance (quedan deprecados, no eliminados)', function () {
    $hito = TableroHito::withoutEvents(fn () => TableroHito::factory()->for($this->tablero)->for($this->grupo)->create([
        'estado_avance_id' => EstadoAvance::query()->where('codigo', 'pendiente')->value('id'),
        'item' => '1.1',
    ]));

    Artisan::call('inspeccion:migrar-hitos-a-tareas');

    expect(TableroHito::query()->find($hito->id))->not->toBeNull();
    expect(GrupoHito::query()->find($this->grupo->id))->not->toBeNull();
    expect(EstadoAvance::query()->count())->toBeGreaterThan(0);
});

it('falla fuerte (no migra a Pendiente en silencio) si un EstadoAvance.codigo no está mapeado', function () {
    // codigo es un TextInput libre en el Filament de Configuración, sin
    // allowlist — un super_admin puede renombrarlo. Antes del fix, un
    // hito ya 'completado' renombrado a un codigo desconocido se migraba
    // como Pendiente sin ningún aviso (hallazgo de /revisor sobre PR5).
    $estadoRenombrado = EstadoAvance::query()->where('codigo', 'completado')->first();
    $estadoRenombrado->update(['codigo' => 'finalizado']);

    TableroHito::withoutEvents(fn () => TableroHito::factory()->for($this->tablero)->for($this->grupo)->create([
        'estado_avance_id' => $estadoRenombrado->id,
        'item' => '1.1',
    ]));

    Artisan::call('inspeccion:migrar-hitos-a-tareas');
})->throws(RuntimeException::class, "codigo = 'finalizado'");

it('no deja Actividades a medio crear si el comando falla a mitad de camino (rollback)', function () {
    $estadoRenombrado = EstadoAvance::query()->where('codigo', 'completado')->first();
    $estadoRenombrado->update(['codigo' => 'finalizado']);

    // Un hito válido primero (crearía su Actividad), uno inválido después
    // en el mismo Tablero — si el rollback no funcionara, quedaría una
    // Actividad huérfana de una migración que "falló".
    TableroHito::withoutEvents(fn () => TableroHito::factory()->for($this->tablero)->for($this->grupo)->create([
        'estado_avance_id' => EstadoAvance::query()->where('codigo', 'pendiente')->value('id'),
        'item' => '1.1',
    ]));
    TableroHito::withoutEvents(fn () => TableroHito::factory()->for($this->tablero)->for($this->grupo)->create([
        'estado_avance_id' => $estadoRenombrado->id,
        'item' => '1.2',
    ]));

    try {
        Artisan::call('inspeccion:migrar-hitos-a-tareas');
    } catch (RuntimeException) {
        // esperado
    }

    expect(Actividad::query()->count())->toBe(0);
    expect(Tarea::query()->count())->toBe(0);
});

it('GAP /qa: editar item de un TableroHito entre dos corridas deja una Tarea huérfana en vez de actualizarla', function () {
    // TableroHitosRelationManager sigue activo y editable (TableroHito no
    // es de solo lectura hasta el cleanup de PR9) — la clave natural de
    // Tarea es actividad_id+code, y code se genera con el item. Si item
    // cambia entre corridas, updateOrCreate() no encuentra la Tarea vieja
    // (code distinto) y crea una nueva, dejando la anterior huérfana con
    // datos ya desactualizados. No es algo que /qa deba resolver
    // unilateralmente (¿updateOrCreate por hito_id en vez de por code?
    // ¿hacer TableroHito de solo lectura ya?) — decisión para /arquitecto
    // antes de PR9.
    TableroHito::withoutEvents(fn () => $hito = TableroHito::factory()->for($this->tablero)->for($this->grupo)->create([
        'estado_avance_id' => EstadoAvance::query()->where('codigo', 'pendiente')->value('id'),
        'item' => '1.1',
    ]));
    $hito = TableroHito::query()->where('item', '1.1')->first();

    Artisan::call('inspeccion:migrar-hitos-a-tareas');
    expect(Tarea::query()->count())->toBe(1);

    $hito->update(['item' => '1.2']);
    Artisan::call('inspeccion:migrar-hitos-a-tareas');

    expect(Tarea::query()->count())->toBe(2);
    expect(Tarea::query()->pluck('code')->sort()->values()->all())->toBe(['T1-1.1', 'T1-1.2']);
});
