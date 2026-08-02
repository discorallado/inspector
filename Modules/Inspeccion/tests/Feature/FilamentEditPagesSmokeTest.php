<?php

use App\Models\User;
use Modules\Inspeccion\Database\Seeders\InspeccionDatabaseSeeder;
use Modules\Inspeccion\Models\ChecklistEjecucion;
use Modules\Inspeccion\Models\ChecklistTemplate;
use Modules\Inspeccion\Models\ControlCambio;
use Modules\Inspeccion\Models\EstadoAvance;
use Modules\Inspeccion\Models\EstadoCambio;
use Modules\Inspeccion\Models\EstadoObservacion;
use Modules\Inspeccion\Models\GrupoHitoLegado;
use Modules\Inspeccion\Models\HitoLegado;
use Modules\Inspeccion\Models\Observacion;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\Tablero;
use Modules\Inspeccion\Models\VisitaInspeccion;

beforeEach(function () {
    $this->seed(InspeccionDatabaseSeeder::class);
    $this->admin = User::factory()->create(['role' => 'super_admin']);

    $this->proyecto = Proyecto::factory()->create();
    $this->tablero = Tablero::factory()->for($this->proyecto)->create();
    HitoLegado::factory()->for($this->tablero)->create([
        'grupo_hito_id' => GrupoHitoLegado::first()->id,
        'estado_avance_id' => EstadoAvance::query()->where('codigo', 'pendiente')->value('id'),
    ]);

    $this->visita = VisitaInspeccion::factory()->for($this->proyecto)->create(['inspector_id' => $this->admin->id]);
    $this->visita->tableros()->attach($this->tablero);

    Observacion::factory()->for($this->visita, 'visitaInspeccion')->create([
        'tablero_id' => $this->tablero->id,
        'estado_observacion_id' => EstadoObservacion::query()->where('codigo', 'pendiente')->value('id'),
    ]);

    ControlCambio::factory()->for($this->tablero)->create([
        'estado_cambio_id' => EstadoCambio::query()->where('codigo', 'propuesto')->value('id'),
    ]);

    $this->ejecucion = ChecklistEjecucion::crearDesdeTemplate([
        'visita_inspeccion_id' => $this->visita->id,
        'tablero_id' => $this->tablero->id,
    ], ChecklistTemplate::first());
});

it('carga la edición de tablero con sus relation managers', function () {
    $this->actingAs($this->admin)->get("/admin/tableros/{$this->tablero->id}/edit")->assertSuccessful();
});

it('carga la edición de visita de inspección con sus relation managers', function () {
    $this->actingAs($this->admin)->get("/admin/inspeccion-calidad/visita-inspeccions/{$this->visita->id}/edit")->assertSuccessful();
});

it('carga la edición de checklist ejecucion con sus ítems', function () {
    $this->actingAs($this->admin)->get("/admin/inspeccion-calidad/checklist-ejecucions/{$this->ejecucion->id}/edit")->assertSuccessful();
});

it('carga la edición de checklist template con sus ítems', function () {
    $this->actingAs($this->admin)->get('/admin/configuracion/checklist-templates/'.ChecklistTemplate::first()->id.'/edit')->assertSuccessful();
});
