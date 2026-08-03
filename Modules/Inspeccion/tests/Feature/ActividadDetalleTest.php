<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\Inspeccion\Database\Seeders\EstadoAvanceSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoCambioSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\TransicionEstadoPermitidaSeeder;
use Modules\Inspeccion\Filament\Resources\Tableros\Pages\EditTablero;
use Modules\Inspeccion\Filament\Resources\Tableros\Pages\TableroKanbanBoard;
use Modules\Inspeccion\Filament\Resources\Tableros\RelationManagers\ActividadesRelationManager;
use Modules\Inspeccion\Filament\Resources\Tableros\TableroResource;
use Modules\Inspeccion\Models\Actividad;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\Tablero;
use Modules\Inspeccion\Models\Tarea;

beforeEach(function () {
    $this->seed(EstadoAvanceSeeder::class);
    $this->seed(EstadoObservacionSeeder::class);
    $this->seed(EstadoCambioSeeder::class);
    $this->seed(TransicionEstadoPermitidaSeeder::class);

    $this->tablero = Tablero::factory()->for(Proyecto::factory())->create();
});

function urlActividadDetalle(Tablero $tablero, Actividad $actividad, ?int $focus = null): string
{
    $params = ['record' => $tablero, 'actividadId' => $actividad->id];

    if ($focus) {
        $params['focus'] = $focus;
    }

    return TableroResource::getUrl('actividad-detalle', $params);
}

it('carga el detalle de una Actividad con sus Tareas', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $actividad = Actividad::factory()->for($this->tablero)->create(['nombre' => 'Cableado de potencia']);
    Tarea::factory()->for($actividad)->create(['nombre' => 'Montaje de riel DIN']);
    $this->actingAs($user);

    $this->get(urlActividadDetalle($this->tablero, $actividad))
        ->assertSuccessful()
        ->assertSee('Cableado de potencia')
        ->assertSee('Montaje de riel DIN');
});

it('responde 404 si la Actividad no pertenece a ese Tablero', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $otroTablero = Tablero::factory()->for(Proyecto::factory())->create();
    $actividadAjena = Actividad::factory()->for($otroTablero)->create();
    $this->actingAs($user);

    $this->get(urlActividadDetalle($this->tablero, $actividadAjena))->assertNotFound();
});

it('sin sesión, redirige al login en vez de mostrar el detalle', function () {
    $actividad = Actividad::factory()->for($this->tablero)->create();

    $this->get(urlActividadDetalle($this->tablero, $actividad))->assertRedirect();
});

it('acepta el query param focus para resaltar una Tarea específica', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $actividad = Actividad::factory()->for($this->tablero)->create();
    $tarea = Tarea::factory()->for($actividad)->create();
    $this->actingAs($user);

    $this->get(urlActividadDetalle($this->tablero, $actividad, $tarea->id))
        ->assertSuccessful()
        ->assertSee('tarea-'.$tarea->id, escape: false);
});

it('el árbol tiene un link "Ver detalle" hacia ActividadDetalle por cada Actividad', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $actividad = Actividad::factory()->for($this->tablero)->create();
    $this->actingAs($user);

    $this->get(TableroResource::getUrl('edit', ['record' => $this->tablero]));

    $url = urlActividadDetalle($this->tablero, $actividad);

    expect(
        Livewire::test(
            ActividadesRelationManager::class,
            ['ownerRecord' => $this->tablero, 'pageClass' => EditTablero::class],
        )->instance()->urlDetalleActividad($actividad)
    )->toBe($url);
});

it('el Kanban trae el conteo de comentarios sin N+1 (withCount)', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $actividad = Actividad::factory()->for($this->tablero)->create();
    $tarea = Tarea::factory()->for($actividad)->create();
    $tarea->filamentComments()->create([
        'user_id' => $user->id,
        'subject_type' => $tarea->getMorphClass(),
        'comment' => 'Revisar torque',
    ]);
    $this->actingAs($user);

    $page = TableroKanbanBoard::class;
    $instance = new $page;
    $instance->record = $this->tablero;

    $columnas = $instance->getColumns();
    $tareaEnColumna = collect($columnas)->flatMap(fn ($c) => $c['tareas'])->firstWhere('id', $tarea->id);

    expect($tareaEnColumna->filament_comments_count)->toBe(1);
});

it('ComentarioPolicy: el dueño puede borrar su comentario, otro usuario no, super_admin sí', function () {
    $autor = User::factory()->create(['role' => 'ingeniero']);
    $otro = User::factory()->create(['role' => 'tecnico']);
    $admin = User::factory()->create(['role' => 'super_admin']);
    $actividad = Actividad::factory()->for($this->tablero)->create();

    $comentario = $actividad->filamentComments()->create([
        'user_id' => $autor->id,
        'subject_type' => $actividad->getMorphClass(),
        'comment' => 'Ojo con el plazo',
    ]);

    // ComentarioPolicy::delete() usa Gate::allows('auditoria.purgar') para
    // el caso "moderación" — igual que el resto de las Policies del
    // módulo, eso resuelve contra el usuario autenticado actual, no
    // contra el $user que Laravel le pasa al método. Por eso acá se
    // autentica de verdad en vez de Gate::forUser().
    $this->actingAs($autor);
    expect(Gate::allows('delete', $comentario))->toBeTrue();

    $this->actingAs($otro);
    expect(Gate::allows('delete', $comentario))->toBeFalse();

    $this->actingAs($admin);
    expect(Gate::allows('delete', $comentario))->toBeTrue();
});
