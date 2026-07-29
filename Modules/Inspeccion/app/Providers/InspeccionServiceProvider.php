<?php

namespace Modules\Inspeccion\Providers;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Gate;
use Modules\Inspeccion\Console\Commands\MigrarHitosATareasCommand;
use Modules\Inspeccion\Models\Actividad;
use Modules\Inspeccion\Models\ChecklistEjecucion;
use Modules\Inspeccion\Models\ChecklistEjecucionItem;
use Modules\Inspeccion\Models\ChecklistItemLibrary;
use Modules\Inspeccion\Models\ChecklistTemplate;
use Modules\Inspeccion\Models\ControlCambio;
use Modules\Inspeccion\Models\Especialidad;
use Modules\Inspeccion\Models\EstadoAvance;
use Modules\Inspeccion\Models\EstadoCambio;
use Modules\Inspeccion\Models\EstadoObservacion;
use Modules\Inspeccion\Models\GrupoHito;
use Modules\Inspeccion\Models\Observacion;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\ResultadoChecklist;
use Modules\Inspeccion\Models\Severidad;
use Modules\Inspeccion\Models\Tablero;
use Modules\Inspeccion\Models\TableroHito;
use Modules\Inspeccion\Models\Tarea;
use Modules\Inspeccion\Models\TipoObservacion;
use Modules\Inspeccion\Models\TransicionEstadoPermitida;
use Modules\Inspeccion\Models\VisitaInspeccion;
use Modules\Inspeccion\Observers\ControlCambioObserver;
use Modules\Inspeccion\Observers\ObservacionObserver;
use Modules\Inspeccion\Observers\TableroHitoObserver;
use Modules\Inspeccion\Observers\TareaObserver;
use Modules\Inspeccion\Policies\ActividadPolicy;
use Modules\Inspeccion\Policies\CatalogoPolicy;
use Modules\Inspeccion\Policies\ChecklistEjecucionItemPolicy;
use Modules\Inspeccion\Policies\ChecklistEjecucionPolicy;
use Modules\Inspeccion\Policies\ControlCambioPolicy;
use Modules\Inspeccion\Policies\ObservacionPolicy;
use Modules\Inspeccion\Policies\ProyectoPolicy;
use Modules\Inspeccion\Policies\TableroHitoPolicy;
use Modules\Inspeccion\Policies\TableroPolicy;
use Modules\Inspeccion\Policies\TareaPolicy;
use Modules\Inspeccion\Policies\UserPolicy;
use Modules\Inspeccion\Policies\VisitaInspeccionPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class InspeccionServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Inspeccion';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'inspeccion';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    protected array $commands = [
        MigrarHitosATareasCommand::class,
    ];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        $this->registerGates();
        $this->registerObservers();
        $this->registerPolicies();
        $this->registerFactoryResolver();
    }

    /**
     * Los modelos del módulo viven en Modules\Inspeccion\Models, así que la
     * convención por defecto de Eloquent (App\Models -> Database\Factories)
     * no los encuentra. Se resuelve explícitamente hacia las factories del módulo.
     */
    protected function registerFactoryResolver(): void
    {
        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => str_starts_with($modelName, 'Modules\\Inspeccion\\Models\\')
                ? 'Modules\\Inspeccion\\Database\\Factories\\'.class_basename($modelName).'Factory'
                : 'Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    /**
     * TODO: reemplazar por Policies reales con Shield al integrar a axon.
     */
    protected function registerPolicies(): void
    {
        Gate::policy(Proyecto::class, ProyectoPolicy::class);
        Gate::policy(Tablero::class, TableroPolicy::class);
        Gate::policy(TableroHito::class, TableroHitoPolicy::class);
        Gate::policy(VisitaInspeccion::class, VisitaInspeccionPolicy::class);
        Gate::policy(Observacion::class, ObservacionPolicy::class);
        Gate::policy(ControlCambio::class, ControlCambioPolicy::class);
        Gate::policy(ChecklistEjecucion::class, ChecklistEjecucionPolicy::class);
        Gate::policy(ChecklistEjecucionItem::class, ChecklistEjecucionItemPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Actividad::class, ActividadPolicy::class);
        Gate::policy(Tarea::class, TareaPolicy::class);

        foreach ([
            GrupoHito::class,
            EstadoAvance::class,
            Especialidad::class,
            TipoObservacion::class,
            Severidad::class,
            EstadoObservacion::class,
            EstadoCambio::class,
            ResultadoChecklist::class,
            TransicionEstadoPermitida::class,
            ChecklistItemLibrary::class,
            ChecklistTemplate::class,
        ] as $catalogo) {
            Gate::policy($catalogo, CatalogoPolicy::class);
        }
    }

    protected function registerObservers(): void
    {
        TableroHito::observe(TableroHitoObserver::class);
        Observacion::observe(ObservacionObserver::class);
        ControlCambio::observe(ControlCambioObserver::class);
        Tarea::observe(TareaObserver::class);
    }

    /**
     * Gates simples basados en config/inspeccion.php.
     *
     * TODO: reemplazar por Policies reales con Shield al integrar a axon.
     */
    protected function registerGates(): void
    {
        foreach (config('inspeccion.permisos', []) as $permiso => $roles) {
            Gate::define($permiso, fn ($user) => in_array($user->role, $roles, true));
        }
    }

    /**
     * Define module schedules.
     *
     * @param  $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }
}
