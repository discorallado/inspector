<?php

namespace Modules\Inspeccion\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Inspeccion\Enums\TaskPriority;
use Modules\Inspeccion\Enums\TaskStatus;
use Modules\Inspeccion\Models\Actividad;
use Modules\Inspeccion\Models\GrupoHito;
use Modules\Inspeccion\Models\Tablero;
use Modules\Inspeccion\Models\TableroHito;
use Modules\Inspeccion\Models\Tarea;
use Modules\Inspeccion\Services\CalculadorAvanceTablero;
use RuntimeException;

/**
 * ADR 0009 §2.5 / ADR 0011: comando de migración de datos, PR5.
 *
 * Cada GrupoHito usado por un Tablero se convierte en una Actividad de ese
 * Tablero; cada TableroHito se convierte en una Tarea de esa Actividad,
 * preservando peso/real_inicio/real_fin y mapeando EstadoAvance.codigo al
 * TaskStatus equivalente. No borra ni modifica TableroHito/GrupoHito/
 * EstadoAvance (quedan deprecados hasta el cleanup de PR9).
 *
 * Idempotente: usa updateOrCreate() con clave natural (tablero_id+nombre
 * para Actividad, tablero_hito_id para Tarea) — correr el comando varias
 * veces no duplica filas. Se matchea por tablero_hito_id (no por
 * actividad_id+code): code se deriva de TableroHito.item, un TextInput
 * libre — matchear por code hacía que editar item entre dos corridas
 * dejara huérfana la Tarea existente y creara una nueva en su lugar
 * (hallazgo de /revisor, ver ADR 0012). tablero_hito_id es estable
 * independiente de lo que se edite en TableroHito (aunque ahora ese
 * relation manager quedó de solo lectura, ver ADR 0012).
 */
class MigrarHitosATareasCommand extends Command
{
    protected $signature = 'inspeccion:migrar-hitos-a-tareas';

    protected $description = 'Migra TableroHito/GrupoHito existentes a Actividad/Tarea (ADR 0009 §2.5, PR5)';

    /**
     * EstadoAvance.codigo -> TaskStatus. 'na' se mapea a Bloqueada:
     * es la semántica disponible más cercana (algo que no avanza), no
     * hay un caso "N/A" en TaskStatus (decisión de /ingeniero en PR5,
     * confirmada con el usuario — se pierde el matiz "excluido del
     * cálculo" hasta que CalculadorAvanceTablero (PR6) decida si
     * Bloqueada también se excluye).
     *
     * @var array<string, TaskStatus>
     */
    private const MAPA_ESTADO = [
        'pendiente' => TaskStatus::Pendiente,
        'en_proceso' => TaskStatus::EnProgreso,
        'completado' => TaskStatus::Completada,
        'na' => TaskStatus::Bloqueada,
    ];

    public function handle(): int
    {
        $actividadesCreadas = 0;
        $tareasCreadas = 0;
        $tareasActualizadas = 0;

        DB::transaction(function () use (&$actividadesCreadas, &$tareasCreadas, &$tareasActualizadas) {
            Tablero::query()
                ->with(['tableroHitos.grupoHito', 'tableroHitos.estadoAvance'])
                ->each(function (Tablero $tablero) use (&$actividadesCreadas, &$tareasCreadas, &$tareasActualizadas) {
                    $actividadesPorGrupo = $tablero->tableroHitos
                        ->pluck('grupoHito')
                        ->unique('id')
                        ->sortBy('orden')
                        ->mapWithKeys(function (GrupoHito $grupoHito) use ($tablero, &$actividadesCreadas) {
                            $actividad = Actividad::withoutEvents(fn () => Actividad::query()->updateOrCreate(
                                ['tablero_id' => $tablero->id, 'nombre' => $grupoHito->nombre],
                                ['orden' => $grupoHito->orden],
                            ));

                            if ($actividad->wasRecentlyCreated) {
                                $actividadesCreadas++;
                            }

                            return [$grupoHito->id => $actividad];
                        });

                    $tablero->tableroHitos->each(function (TableroHito $hito) use ($tablero, $actividadesPorGrupo, &$tareasCreadas, &$tareasActualizadas) {
                        $actividad = $actividadesPorGrupo->get($hito->grupo_hito_id);
                        $codigo = $hito->estadoAvance->codigo;

                        // Sin este throw, un EstadoAvance.codigo no
                        // reconocido caía silenciosamente en Pendiente —
                        // codigo es un TextInput libre en el Filament de
                        // Configuración (sin allowlist), así que renombrar
                        // 'completado' antes de correr este comando
                        // migraba hitos ya terminados como si nunca
                        // hubieran empezado, sin ningún aviso. Mejor fallar
                        // fuerte (y hacer rollback, ver DB::transaction
                        // arriba) que corromper el historial de avance en
                        // silencio.
                        if (! array_key_exists($codigo, self::MAPA_ESTADO)) {
                            throw new RuntimeException(
                                "TableroHito #{$hito->id} (tablero '{$tablero->tag}', item '{$hito->item}') tiene ".
                                "estado_avance.codigo = '{$codigo}', que no está mapeado a ningún TaskStatus. ".
                                'Agregalo a MigrarHitosATareasCommand::MAPA_ESTADO antes de re-correr el comando.'
                            );
                        }

                        $status = self::MAPA_ESTADO[$codigo];

                        $tarea = Tarea::withoutEvents(fn () => Tarea::query()->updateOrCreate(
                            [
                                'tablero_hito_id' => $hito->id,
                            ],
                            [
                                'actividad_id' => $actividad->id,
                                'code' => "{$tablero->tag}-{$hito->item}",
                                'nombre' => $hito->nombre,
                                'descripcion' => $hito->observaciones,
                                'status' => $status,
                                'priority' => TaskPriority::Media,
                                'peso' => $hito->peso,
                                // 'na' se mapea a Bloqueada (§ arriba) pero
                                // significa "no aplica", no "trabada" — sin
                                // este flag, se perdería el matiz de
                                // excluir del cálculo de avance (ADR 0012).
                                'excluye_calculo' => $codigo === 'na',
                                'start_date' => $hito->plan_inicio,
                                'due_date' => $hito->plan_fin,
                                'real_inicio' => $hito->real_inicio,
                                'real_fin' => $hito->real_fin,
                            ],
                        ));

                        $tarea->wasRecentlyCreated ? $tareasCreadas++ : $tareasActualizadas++;
                    });

                    // withoutEvents() arriba evita que TareaObserver::saved()
                    // dispare el recálculo — se hace una vez por tablero acá,
                    // no por tarea, para no recalcular 39 veces por nada.
                    app(CalculadorAvanceTablero::class)->recalcularYGuardar($tablero);
                });
        });

        $this->info("Actividades creadas: {$actividadesCreadas}");
        $this->info("Tareas creadas: {$tareasCreadas}, actualizadas: {$tareasActualizadas}");

        return self::SUCCESS;
    }
}
