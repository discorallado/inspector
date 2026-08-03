<?php

use App\Models\User;
use Filament\Notifications\Notification;
use Livewire\Livewire;
use Modules\Inspeccion\Database\Seeders\EstadoAvanceSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoCambioSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\TransicionEstadoPermitidaSeeder;
use Modules\Inspeccion\Enums\ActividadEstado;
use Modules\Inspeccion\Enums\TaskPriority;
use Modules\Inspeccion\Enums\TaskStatus;
use Modules\Inspeccion\Filament\Resources\Tableros\Pages\EditTablero;
use Modules\Inspeccion\Filament\Resources\Tableros\RelationManagers\ActividadesRelationManager;
use Modules\Inspeccion\Filament\Resources\Tableros\TableroResource;
use Modules\Inspeccion\Models\Actividad;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\Tablero;
use Modules\Inspeccion\Models\Tarea;
use Modules\Inspeccion\Services\TareaDependencyService;

beforeEach(function () {
    $this->seed(EstadoAvanceSeeder::class);
    $this->seed(EstadoObservacionSeeder::class);
    $this->seed(EstadoCambioSeeder::class);
    $this->seed(TransicionEstadoPermitidaSeeder::class);

    $this->tablero = Tablero::factory()->for(Proyecto::factory())->create();
});

function testArbol(Tablero $tablero)
{
    return Livewire::test(ActividadesRelationManager::class, [
        'ownerRecord' => $tablero,
        'pageClass' => EditTablero::class,
    ]);
}

it('ingeniero puede crear una Actividad desde el árbol', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $this->actingAs($user);

    testArbol($this->tablero)
        ->callAction('crearActividad', data: [
            'nombre' => 'Montaje',
            'orden' => 1,
        ])
        ->assertHasNoActionErrors();

    expect(Actividad::query()->where('tablero_id', $this->tablero->id)->where('nombre', 'Montaje')->exists())
        ->toBeTrue();
});

it('tecnico (sin tablero_actividad.gestionar) no puede crear una Actividad', function () {
    $user = User::factory()->create(['role' => 'tecnico']);
    $this->actingAs($user);

    testArbol($this->tablero)->assertActionHidden('crearActividad');
});

it('ingeniero puede editar una Actividad desde el árbol', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $actividad = Actividad::factory()->for($this->tablero)->create(['nombre' => 'Original']);
    $this->actingAs($user);

    testArbol($this->tablero)
        ->callAction('editarActividad', data: ['nombre' => 'Renombrada'], arguments: ['id' => $actividad->id])
        ->assertHasNoActionErrors();

    expect($actividad->refresh()->nombre)->toBe('Renombrada');
});

it('ingeniero puede crear una Tarea dentro de una Actividad desde el árbol, con code autogenerado', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $actividad = Actividad::factory()->for($this->tablero)->create(['orden' => 1]);
    $this->actingAs($user);

    testArbol($this->tablero)
        ->mountAction('crearTarea', arguments: ['actividadId' => $actividad->id])
        ->assertActionDataSet(['actividad_id' => $actividad->id])
        ->setActionData([
            'actividad_id' => $actividad->id,
            'nombre' => 'Montaje de riel DIN',
            'status' => TaskStatus::Pendiente->value,
            'priority' => TaskPriority::Media->value,
            'peso' => 10,
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    $tarea = Tarea::where('actividad_id', $actividad->id)->where('nombre', 'Montaje de riel DIN')->firstOrFail();
    expect($tarea->code)->toBe(Tarea::generarCode($this->tablero->tag, 1, 1));
});

it('code autogenerado incluye el orden de la Actividad — dos Actividades no chocan en la misma posición relativa', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $actividadA = Actividad::factory()->for($this->tablero)->create(['orden' => 1]);
    $actividadB = Actividad::factory()->for($this->tablero)->create(['orden' => 2]);
    $this->actingAs($user);

    $arbol = testArbol($this->tablero);
    $arbol->mountAction('crearTarea', arguments: ['actividadId' => $actividadA->id])
        ->setActionData(['actividad_id' => $actividadA->id, 'nombre' => 'Primera de A', 'status' => TaskStatus::Pendiente->value, 'priority' => TaskPriority::Media->value])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    $arbol->mountAction('crearTarea', arguments: ['actividadId' => $actividadB->id])
        ->setActionData(['actividad_id' => $actividadB->id, 'nombre' => 'Primera de B', 'status' => TaskStatus::Pendiente->value, 'priority' => TaskPriority::Media->value])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    $tareaA = Tarea::where('actividad_id', $actividadA->id)->firstOrFail();
    $tareaB = Tarea::where('actividad_id', $actividadB->id)->firstOrFail();

    expect($tareaA->code)->toBe(Tarea::generarCode($this->tablero->tag, 1, 1));
    expect($tareaB->code)->toBe(Tarea::generarCode($this->tablero->tag, 2, 1));
    expect($tareaA->code)->not->toBe($tareaB->code);
});

it('tecnico (sin tablero_actividad.gestionar) no puede crear una Tarea', function () {
    $user = User::factory()->create(['role' => 'tecnico']);
    $actividad = Actividad::factory()->for($this->tablero)->create();
    $this->actingAs($user);

    testArbol($this->tablero)->assertActionHidden('crearTarea');
});

it('tecnico (con tablero_tarea.actualizar) sí puede editar una Tarea', function () {
    $user = User::factory()->create(['role' => 'tecnico']);
    $actividad = Actividad::factory()->for($this->tablero)->create();
    $tarea = Tarea::factory()->for($actividad)->create();
    $this->actingAs($user);

    testArbol($this->tablero)->assertActionVisible('editarTarea', arguments: ['id' => $tarea->id]);
});

it('ingeniero puede eliminar (soft delete) una Actividad y verla solo con mostrarEliminados', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $actividad = Actividad::factory()->for($this->tablero)->create();
    $this->actingAs($user);

    $component = testArbol($this->tablero)
        ->callAction('eliminarActividad', arguments: ['id' => $actividad->id])
        ->assertHasNoActionErrors();

    expect($actividad->fresh()->trashed())->toBeTrue();
    expect($component->instance()->getActividades()->pluck('id'))->not->toContain($actividad->id);

    $component->set('mostrarEliminados', true);
    expect($component->instance()->getActividades()->pluck('id'))->toContain($actividad->id);
});

it('ingeniero puede restaurar una Actividad eliminada', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $actividad = Actividad::factory()->for($this->tablero)->create();
    $actividad->delete();
    $this->actingAs($user);

    testArbol($this->tablero)
        ->callAction('restaurarActividad', arguments: ['id' => $actividad->id])
        ->assertHasNoActionErrors();

    expect($actividad->fresh()->trashed())->toBeFalse();
});

it('solo super_admin (auditoria.purgar) puede eliminar definitivamente una Actividad', function () {
    $ingeniero = User::factory()->create(['role' => 'ingeniero']);
    $actividad = Actividad::factory()->for($this->tablero)->create();
    $actividad->delete();
    $this->actingAs($ingeniero);

    testArbol($this->tablero)->assertActionHidden('eliminarDefinitivoActividad', arguments: ['id' => $actividad->id]);

    $superAdmin = User::factory()->create(['role' => 'super_admin']);
    $this->actingAs($superAdmin);

    testArbol($this->tablero)
        ->callAction('eliminarDefinitivoActividad', arguments: ['id' => $actividad->id])
        ->assertHasNoActionErrors();

    expect(Actividad::withTrashed()->find($actividad->id))->toBeNull();
});

it('eliminar definitivo de una Actividad con Tareas no revienta con un 500: avisa y no borra', function () {
    $superAdmin = User::factory()->create(['role' => 'super_admin']);
    $actividad = Actividad::factory()->for($this->tablero)->create();
    Tarea::factory()->for($actividad)->create();
    $actividad->delete();
    $this->actingAs($superAdmin);

    testArbol($this->tablero)
        ->callAction('eliminarDefinitivoActividad', arguments: ['id' => $actividad->id])
        ->assertHasNoActionErrors();

    expect(Actividad::withTrashed()->find($actividad->id))->not->toBeNull();
});

it('eliminar (soft delete) una Tarea cuya Actividad ya está en la papelera no revienta el recálculo de avance', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $actividad = Actividad::factory()->for($this->tablero)->create();
    $tarea = Tarea::factory()->for($actividad)->create();
    $actividad->delete();
    $this->actingAs($user);

    testArbol($this->tablero)
        ->callAction('eliminarTarea', arguments: ['id' => $tarea->id])
        ->assertHasNoActionErrors();

    expect($tarea->fresh()->trashed())->toBeTrue();
});

it('la vista del árbol renderiza el avance ponderado de cada Actividad', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $actividad = Actividad::factory()->for($this->tablero)->create();
    Tarea::withoutEvents(fn () => Tarea::factory()->for($actividad)->create([
        'status' => TaskStatus::Completada,
        'peso' => 10,
    ]));
    Tarea::withoutEvents(fn () => Tarea::factory()->for($actividad)->create([
        'status' => TaskStatus::Pendiente,
        'peso' => 30,
    ]));
    $this->actingAs($user);

    testArbol($this->tablero)->assertSuccessful();

    expect($actividad->avance())->toBe(25.0);
});

it('la página de edición del Tablero carga OK con el árbol registrado como relation manager', function () {
    // RelationManager es lazy por defecto en Filament (CanBeLazy::$isLazy),
    // así que el contenido del árbol no viaja en este HTML inicial — eso ya
    // se cubre en los tests de arriba contra el componente Livewire
    // directamente. Este test solo confirma que la página no explota al
    // registrar ActividadesRelationManager (p.ej. un $view mal escrito).
    $user = User::factory()->create(['role' => 'ingeniero']);
    $this->actingAs($user);

    $this->get(TableroResource::getUrl('edit', ['record' => $this->tablero]))
        ->assertSuccessful();
});

// -------------------------------------------------------------------
// Estado calculado / vencida (port de axon)
// -------------------------------------------------------------------

it('estadoCalculado() de Actividad refleja el status de sus Tareas', function () {
    $actividad = Actividad::factory()->for($this->tablero)->create();
    expect($actividad->estadoCalculado())->toBe(ActividadEstado::Pendiente);

    $t1 = Tarea::withoutEvents(fn () => Tarea::factory()->for($actividad)->create(['status' => TaskStatus::Pendiente]));
    expect($actividad->fresh()->estadoCalculado())->toBe(ActividadEstado::Pendiente);

    Tarea::withoutEvents(fn () => $t1->update(['status' => TaskStatus::EnProgreso]));
    expect($actividad->fresh()->estadoCalculado())->toBe(ActividadEstado::EnProgreso);

    Tarea::withoutEvents(fn () => $t1->update(['status' => TaskStatus::Completada]));
    expect($actividad->fresh()->estadoCalculado())->toBe(ActividadEstado::Completada);
});

it('Tarea::isOverdue() es true solo si due_date pasó y no está completada', function () {
    $actividad = Actividad::factory()->for($this->tablero)->create();

    $vencida = Tarea::withoutEvents(fn () => Tarea::factory()->for($actividad)->create([
        'status' => TaskStatus::Pendiente,
        'due_date' => now()->subDay(),
    ]));
    expect($vencida->isOverdue())->toBeTrue();

    $completadaVencida = Tarea::withoutEvents(fn () => Tarea::factory()->for($actividad)->create([
        'status' => TaskStatus::Completada,
        'due_date' => now()->subDay(),
    ]));
    expect($completadaVencida->isOverdue())->toBeFalse();

    $sinFecha = Tarea::factory()->for($actividad)->create(['due_date' => null]);
    expect($sinFecha->isOverdue())->toBeFalse();
});

// -------------------------------------------------------------------
// Reorder (drag-and-drop, port de axon ActivityAccordion)
// -------------------------------------------------------------------

it('ingeniero puede reordenar Actividades, y el code de sus Tareas se recalcula en cascada', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $a1 = Actividad::factory()->for($this->tablero)->create(['orden' => 1]);
    $a2 = Actividad::factory()->for($this->tablero)->create(['orden' => 2]);
    $t1 = Tarea::factory()->for($a1)->create(['orden' => 1]);
    $t2 = Tarea::factory()->for($a2)->create(['orden' => 1]);
    $this->actingAs($user);

    testArbol($this->tablero)->call('reordenarActividades', [$a2->id, $a1->id]);

    expect($a2->fresh()->orden)->toBe(1);
    expect($a1->fresh()->orden)->toBe(2);

    // a2 pasó a orden=1 -> su Tarea (orden=1 dentro de la actividad) ahora es X-1.1
    expect($t2->fresh()->code)->toBe(Tarea::generarCode($this->tablero->tag, 1, 1));
    // a1 pasó a orden=2 -> su Tarea ahora es X-2.1
    expect($t1->fresh()->code)->toBe(Tarea::generarCode($this->tablero->tag, 2, 1));
});

it('reordenarActividades no reordena Actividades de OTRO Tablero', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $otroTablero = Tablero::factory()->for(Proyecto::factory())->create();
    $ajena = Actividad::factory()->for($otroTablero)->create(['orden' => 5]);
    $this->actingAs($user);

    testArbol($this->tablero)->call('reordenarActividades', [$ajena->id]);

    expect($ajena->fresh()->orden)->toBe(5);
});

it('tecnico (sin tablero.gestionar) no puede reordenar Actividades', function () {
    $user = User::factory()->create(['role' => 'tecnico']);
    $actividad = Actividad::factory()->for($this->tablero)->create(['orden' => 9]);
    $this->actingAs($user);

    // authorize() lanza AuthorizationException, pero el harness de test de
    // Livewire la resuelve como respuesta de error en vez de propagarla al
    // closure PHP que la llama — se verifica el efecto (nada cambió) en vez
    // de esperar la excepción cruda.
    testArbol($this->tablero)->call('reordenarActividades', [$actividad->id]);

    expect($actividad->fresh()->orden)->toBe(9);
});

it('ingeniero puede reordenar Tareas dentro de la misma Actividad, y su code se recalcula', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $actividad = Actividad::factory()->for($this->tablero)->create(['orden' => 1]);
    $t1 = Tarea::factory()->for($actividad)->create(['orden' => 1]);
    $t2 = Tarea::factory()->for($actividad)->create(['orden' => 2]);
    $this->actingAs($user);

    testArbol($this->tablero)->call('reordenarTareas', [$t2->id, $t1->id], $actividad->id);

    expect($t2->fresh()->orden)->toBe(1);
    expect($t1->fresh()->orden)->toBe(2);
    expect($t2->fresh()->code)->toBe(Tarea::generarCode($this->tablero->tag, 1, 1));
    expect($t1->fresh()->code)->toBe(Tarea::generarCode($this->tablero->tag, 1, 2));
});

it('reordenarTareas mueve una Tarea a otra Actividad del mismo Tablero (drag entre columnas), recalcula su code', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $origen = Actividad::factory()->for($this->tablero)->create(['orden' => 1]);
    $destino = Actividad::factory()->for($this->tablero)->create(['orden' => 2]);
    $tarea = Tarea::factory()->for($origen)->create();
    $this->actingAs($user);

    testArbol($this->tablero)->call('reordenarTareas', [$tarea->id], $destino->id);

    expect($tarea->fresh()->actividad_id)->toBe($destino->id);
    expect($tarea->fresh()->code)->toBe(Tarea::generarCode($this->tablero->tag, 2, 1));
});

it('reordenarTareas no mueve nada si el actividadId destino es de OTRO Tablero', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $otroTablero = Tablero::factory()->for(Proyecto::factory())->create();
    $actividadAjena = Actividad::factory()->for($otroTablero)->create();
    $actividad = Actividad::factory()->for($this->tablero)->create();
    $tarea = Tarea::factory()->for($actividad)->create();
    $this->actingAs($user);

    testArbol($this->tablero)->call('reordenarTareas', [$tarea->id], $actividadAjena->id);

    expect($tarea->fresh()->actividad_id)->toBe($actividad->id);
});

// -------------------------------------------------------------------
// Insertar Tarea en posición / agendar fechas (port de axon)
// -------------------------------------------------------------------

it('insertarTareaAction crea una Tarea después de la referencia, corre el orden Y el code de las siguientes', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $actividad = Actividad::factory()->for($this->tablero)->create(['orden' => 1]);
    $t1 = Tarea::factory()->for($actividad)->create(['orden' => 1]);
    $t2 = Tarea::factory()->for($actividad)->create(['orden' => 2]);
    $this->actingAs($user);

    testArbol($this->tablero)
        ->mountAction('insertarTarea', arguments: ['id' => $t1->id, 'position' => 'after'])
        ->assertActionDataSet(['actividad_id' => $actividad->id])
        ->setActionData([
            'actividad_id' => $actividad->id,
            'nombre' => 'Insertada después de t1',
            'status' => TaskStatus::Pendiente->value,
            'priority' => TaskPriority::Media->value,
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    $nueva = Tarea::where('actividad_id', $actividad->id)->where('nombre', 'Insertada después de t1')->firstOrFail();
    expect($nueva->orden)->toBe(2);
    expect($nueva->code)->toBe(Tarea::generarCode($this->tablero->tag, 1, 2));

    expect($t2->fresh()->orden)->toBe(3);
    expect($t2->fresh()->code)->toBe(Tarea::generarCode($this->tablero->tag, 1, 3));
});

it('agendarFechasDesdeAnteriorAction sugiere start_date desde el due_date de la tarea anterior', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $actividad = Actividad::factory()->for($this->tablero)->create();
    Tarea::factory()->for($actividad)->create(['orden' => 1, 'due_date' => '2026-01-10']);
    $t2 = Tarea::factory()->for($actividad)->create(['orden' => 2]);
    $this->actingAs($user);

    testArbol($this->tablero)
        ->mountAction('agendarFechasDesdeAnterior', arguments: ['id' => $t2->id])
        ->assertActionDataSet(['start_date' => '2026-01-11'])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect($t2->fresh()->start_date->format('Y-m-d'))->toBe('2026-01-11');
});

// -------------------------------------------------------------------
// Predecesoras (port de axon TaskDependencyService)
// -------------------------------------------------------------------

it('crear una Tarea con predecessors crea el TareaLink correspondiente', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $actividad = Actividad::factory()->for($this->tablero)->create();
    $predecesora = Tarea::factory()->for($actividad)->create();
    $this->actingAs($user);

    testArbol($this->tablero)
        ->mountAction('crearTarea', arguments: ['actividadId' => $actividad->id])
        ->setActionData([
            'actividad_id' => $actividad->id,
            'nombre' => 'Con predecesora',
            'status' => TaskStatus::Pendiente->value,
            'priority' => TaskPriority::Media->value,
            'predecessors' => [$predecesora->id],
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    $tarea = Tarea::where('actividad_id', $actividad->id)->where('nombre', 'Con predecesora')->firstOrFail();
    expect($tarea->predecessors->pluck('id')->all())->toBe([$predecesora->id]);
});

it('editar predecessors de una Tarea sincroniza altas y bajas', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $actividad = Actividad::factory()->for($this->tablero)->create();
    $p1 = Tarea::factory()->for($actividad)->create();
    $p2 = Tarea::factory()->for($actividad)->create();
    $tarea = Tarea::factory()->for($actividad)->create();
    TareaDependencyService::syncPredecessors($tarea, [$p1->id]);
    $this->actingAs($user);

    testArbol($this->tablero)
        ->callAction('editarTarea', data: ['predecessors' => [$p2->id]], arguments: ['id' => $tarea->id])
        ->assertHasNoActionErrors();

    expect($tarea->fresh()->predecessors->pluck('id')->all())->toBe([$p2->id]);
});

it('omite una predecesora que generaría un ciclo y notifica', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $actividad = Actividad::factory()->for($this->tablero)->create();
    $a = Tarea::factory()->for($actividad)->create();
    $b = Tarea::factory()->for($actividad)->create();
    TareaDependencyService::syncPredecessors($b, [$a->id]);
    $this->actingAs($user);

    testArbol($this->tablero)
        ->callAction('editarTarea', data: ['predecessors' => [$b->id]], arguments: ['id' => $a->id])
        ->assertHasNoActionErrors();

    expect($a->fresh()->predecessors->pluck('id')->all())->toBe([]);
    Notification::assertNotified(__('inspeccion.tarea.arbol.ciclo_omitido'));
});
