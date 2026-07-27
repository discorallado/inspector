<?php

namespace Modules\Inspeccion\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inspeccion\Models\ControlCambio;
use Modules\Inspeccion\Models\EstadoAvance;
use Modules\Inspeccion\Models\EstadoCambio;
use Modules\Inspeccion\Models\GrupoHito;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\Tablero;
use Modules\Inspeccion\Models\TableroHito;
use Modules\Inspeccion\Services\CalculadorAvanceTablero;

/**
 * Importa los datos históricos de "Seguimiento Integracion Tableros.xlsx"
 * (avance ponderado por tablero + control de cambios). El archivo no trae
 * NC registradas (hoja "NoConformidades" vacía), así que no hay Observacion
 * que importar desde acá.
 *
 * Los 39 hitos/sub-actividades y sus pesos son idénticos en los 6 tableros;
 * lo que cambia entre ellos es el estado de avance y, para BUS_A/BUS_B, las
 * fechas plan de los grupos 2 a 8.
 */
class SeguimientoIntegracionTablerosSeeder extends Seeder
{
    /**
     * @var list<array{item: string, nombre: string, grupo: int, responsable: string}>
     */
    private const ITEMS = [
        ['item' => '1.1', 'nombre' => 'Recepción y verificación de envolvente (dimensiones, IP/IK, RAL)', 'grupo' => 1, 'responsable' => 'CSE'],
        ['item' => '1.2', 'nombre' => 'Ensamble de estructura y paneles del gabinete', 'grupo' => 1, 'responsable' => 'CMF'],
        ['item' => '1.3', 'nombre' => 'Montaje de perfilería DIN y placas de montaje', 'grupo' => 1, 'responsable' => 'CMF'],
        ['item' => '1.4', 'nombre' => 'Instalación de puertas, bisagras y cerraduras', 'grupo' => 1, 'responsable' => 'CMF'],
        ['item' => '1.5', 'nombre' => 'Fijación de canaletas y sistema de cableado', 'grupo' => 1, 'responsable' => 'CSE'],
        ['item' => '2.1', 'nombre' => 'Verificación de aparatos vs. lista de materiales (marca/In/Icu)', 'grupo' => 2, 'responsable' => 'CSE'],
        ['item' => '2.2', 'nombre' => 'Montaje de interruptor principal', 'grupo' => 2, 'responsable' => 'CMF'],
        ['item' => '2.3', 'nombre' => 'Montaje de protecciones de derivación', 'grupo' => 2, 'responsable' => 'CMF'],
        ['item' => '2.4', 'nombre' => 'Montaje de diferenciales / protecciones auxiliares', 'grupo' => 2, 'responsable' => 'CMF'],
        ['item' => '2.5', 'nombre' => 'Instalación de instrumentación (medidores, TC, relés)', 'grupo' => 2, 'responsable' => 'CMF'],
        ['item' => '2.6', 'nombre' => 'Fijación y torque de aparatos según layout', 'grupo' => 2, 'responsable' => 'CMF'],
        ['item' => '3.1', 'nombre' => 'Corte y conformado de barras (fases, N)', 'grupo' => 3, 'responsable' => 'CMF'],
        ['item' => '3.2', 'nombre' => 'Perforado y tratamiento de superficie / aislamiento', 'grupo' => 3, 'responsable' => 'CMF'],
        ['item' => '3.3', 'nombre' => 'Montaje de aisladores y soportes de barra', 'grupo' => 3, 'responsable' => 'CMF'],
        ['item' => '3.4', 'nombre' => 'Instalación de barras principales con torque especificado', 'grupo' => 3, 'responsable' => 'CMF'],
        ['item' => '3.5', 'nombre' => 'Montaje de barra de tierra (PE) y neutro (N)', 'grupo' => 3, 'responsable' => 'CMF'],
        ['item' => '3.6', 'nombre' => 'Conexión de derivaciones a protecciones', 'grupo' => 3, 'responsable' => 'CMF'],
        ['item' => '4.1', 'nombre' => 'Alambrado de circuito de fuerza', 'grupo' => 4, 'responsable' => 'CMF'],
        ['item' => '4.2', 'nombre' => 'Alambrado de circuito de control / mando', 'grupo' => 4, 'responsable' => 'CMF'],
        ['item' => '4.3', 'nombre' => 'Conexionado de instrumentación y señalización', 'grupo' => 4, 'responsable' => 'CMF'],
        ['item' => '4.4', 'nombre' => 'Montaje de punteras/terminales y apriete con torque', 'grupo' => 4, 'responsable' => 'CMF'],
        ['item' => '4.5', 'nombre' => 'Peinado y ordenamiento de mazos de cable', 'grupo' => 4, 'responsable' => 'CMF'],
        ['item' => '4.6', 'nombre' => 'Conexión de barra PE a partes metálicas accesibles', 'grupo' => 4, 'responsable' => 'CMF'],
        ['item' => '5.1', 'nombre' => 'Etiquetado de conductores en ambos extremos', 'grupo' => 5, 'responsable' => 'CMF'],
        ['item' => '5.2', 'nombre' => 'Rotulado de aparatos y bornes según planos', 'grupo' => 5, 'responsable' => 'CMF'],
        ['item' => '5.3', 'nombre' => 'Instalación de placa de características (nameplate)', 'grupo' => 5, 'responsable' => 'CMF'],
        ['item' => '5.4', 'nombre' => 'Rótulos de advertencia y seguridad', 'grupo' => 5, 'responsable' => 'CMF'],
        ['item' => '5.5', 'nombre' => 'Verificación de coincidencia rótulos vs. esquema', 'grupo' => 5, 'responsable' => 'CSE'],
        ['item' => '6.1', 'nombre' => 'Ejecución de protocolo FAT (según alcance definido)', 'grupo' => 6, 'responsable' => 'CMF'],
        ['item' => '6.2', 'nombre' => 'Levantamiento y cierre de punchlist', 'grupo' => 6, 'responsable' => 'CSE'],
        ['item' => '6.3', 'nombre' => 'Protocolo FAT firmado / liberación', 'grupo' => 6, 'responsable' => 'CSE'],
        ['item' => '7.1', 'nombre' => 'Limpieza y protección interior del tablero', 'grupo' => 7, 'responsable' => 'CMF'],
        ['item' => '7.2', 'nombre' => 'Bloqueo/aseguramiento de aparatos móviles para transporte', 'grupo' => 7, 'responsable' => 'CMF'],
        ['item' => '7.3', 'nombre' => 'Embalaje protector (film, esquineros, IP de transporte)', 'grupo' => 7, 'responsable' => 'CMF'],
        ['item' => '7.4', 'nombre' => 'Identificación externa del bulto y documentación adjunta', 'grupo' => 7, 'responsable' => 'CSE'],
        ['item' => '8.1', 'nombre' => 'Coordinación logística de retiro/entrega', 'grupo' => 8, 'responsable' => 'CSE'],
        ['item' => '8.2', 'nombre' => 'Verificación documental (guía de despacho, as-built)', 'grupo' => 8, 'responsable' => 'CSE'],
        ['item' => '8.3', 'nombre' => 'Carga y despacho', 'grupo' => 8, 'responsable' => 'CSE'],
        ['item' => '8.4', 'nombre' => 'Confirmación de recepción en destino', 'grupo' => 8, 'responsable' => 'CLIENTE'],
    ];

    /**
     * Fechas plan por grupo (orden 1-8), por familia de plan (A = TP/T_G2/CLIMA_A/CLIMA_B, B = BUS_A/BUS_B).
     *
     * @var array<string, array<int, array{0: string, 1: string}>>
     */
    private const PLAN_GRUPOS = [
        'A' => [
            1 => ['2026-06-29', '2026-07-02'],
            2 => ['2026-07-02', '2026-07-03'],
            3 => ['2026-07-02', '2026-07-03'],
            4 => ['2026-07-05', '2026-07-09'],
            5 => ['2026-07-10', '2026-07-10'],
            6 => ['2026-07-10', '2026-07-10'],
            7 => ['2026-07-13', '2026-07-14'],
            8 => ['2026-07-14', '2026-07-14'],
        ],
        'B' => [
            1 => ['2026-06-29', '2026-07-02'],
            2 => ['2026-07-05', '2026-07-10'],
            3 => ['2026-07-05', '2026-07-10'],
            4 => ['2026-07-09', '2026-07-10'],
            5 => ['2026-07-14', '2026-07-14'],
            6 => ['2026-07-14', '2026-07-14'],
            7 => ['2026-07-14', '2026-07-14'],
            8 => ['2026-07-14', '2026-07-14'],
        ],
    ];

    /**
     * @var array<string, array{plan: string, oc_contrato: ?string, completado: list<string>, en_proceso: list<string>, na: list<string>}>
     */
    private const TABLEROS = [
        'TP' => ['plan' => 'A', 'oc_contrato' => null, 'completado' => ['1.1', '1.2', '2.1', '2.2'], 'en_proceso' => ['3.1'], 'na' => ['2.4']],
        'T_G2' => ['plan' => 'A', 'oc_contrato' => 'IN-17248', 'completado' => ['1.1', '1.2', '2.1', '2.2'], 'en_proceso' => ['3.1'], 'na' => ['2.4']],
        'BUS_A' => ['plan' => 'B', 'oc_contrato' => null, 'completado' => ['1.1', '1.2', '2.1', '2.2'], 'en_proceso' => ['3.1'], 'na' => ['2.4']],
        'BUS_B' => ['plan' => 'B', 'oc_contrato' => null, 'completado' => ['1.1', '1.2', '2.1', '2.2'], 'en_proceso' => ['3.1'], 'na' => ['2.4']],
        'CLIMA_A' => ['plan' => 'A', 'oc_contrato' => null, 'completado' => ['1.1', '1.2', '2.1', '2.2'], 'en_proceso' => ['1.3', '2.3', '2.4', '3.1', '4.1', '4.4', '4.5', '5.1'], 'na' => []],
        'CLIMA_B' => ['plan' => 'A', 'oc_contrato' => null, 'completado' => ['1.1', '1.2', '2.1', '2.2'], 'en_proceso' => ['1.3', '2.3', '2.4', '3.1', '4.1', '4.4', '4.5', '5.1'], 'na' => []],
    ];

    /**
     * @var list<array{numero: string, tablero: string, descripcion: string, motivo: string, impacto: string, solicitante: string, fecha: string}>
     */
    private const CONTROL_CAMBIOS = [
        ['numero' => 'CC-001', 'tablero' => 'CLIMA_A', 'descripcion' => 'Reducción del largo total', 'motivo' => 'Espacio de instalación con poca holgura', 'impacto' => 'Bajo-Medio', 'solicitante' => 'PM / Cliente', 'fecha' => '2026-07-20'],
        ['numero' => 'CC-002', 'tablero' => 'CLIMA_B', 'descripcion' => 'Reducción del largo total', 'motivo' => 'Espacio de instalación con poca holgura', 'impacto' => 'Bajo-Medio', 'solicitante' => 'PM / Cliente', 'fecha' => '2026-07-20'],
    ];

    public function run(): void
    {
        // TODO: nombre de Proyecto tentativo — la planilla no lo especifica, viene con el campo "Proyecto:" en blanco.
        $proyecto = Proyecto::query()->firstOrCreate(['nombre' => 'IFX']);

        $grupos = GrupoHito::query()->orderBy('orden')->pluck('id', 'orden');
        $estadosAvance = EstadoAvance::query()->pluck('id', 'codigo');
        $estadoCambioPropuesto = EstadoCambio::query()->where('codigo', 'propuesto')->value('id');

        foreach (self::TABLEROS as $tag => $config) {
            $tablero = Tablero::query()->firstOrCreate(
                ['proyecto_id' => $proyecto->id, 'tag' => $tag],
                ['nombre' => "Tablero {$tag}", 'oc_contrato' => $config['oc_contrato']],
            );

            $planGrupos = self::PLAN_GRUPOS[$config['plan']];

            foreach (self::ITEMS as $definicion) {
                $estadoCodigo = match (true) {
                    in_array($definicion['item'], $config['completado'], true) => 'completado',
                    in_array($definicion['item'], $config['en_proceso'], true) => 'en_proceso',
                    in_array($definicion['item'], $config['na'], true) => 'na',
                    default => 'pendiente',
                };

                [$planInicio, $planFin] = $planGrupos[$definicion['grupo']];

                TableroHito::query()->updateOrCreate(
                    ['tablero_id' => $tablero->id, 'item' => $definicion['item']],
                    [
                        'grupo_hito_id' => $grupos[$definicion['grupo']],
                        'estado_avance_id' => $estadosAvance[$estadoCodigo],
                        'nombre' => $definicion['nombre'],
                        'peso' => 1,
                        'plan_inicio' => $planInicio,
                        'plan_fin' => $planFin,
                        'responsable' => $definicion['responsable'],
                    ],
                );
            }
        }

        foreach (self::CONTROL_CAMBIOS as $cambio) {
            $tablero = Tablero::query()->where('proyecto_id', $proyecto->id)->where('tag', $cambio['tablero'])->firstOrFail();

            ControlCambio::query()->firstOrCreate(
                ['tablero_id' => $tablero->id, 'descripcion' => $cambio['descripcion'], 'fecha' => $cambio['fecha']],
                [
                    'estado_cambio_id' => $estadoCambioPropuesto,
                    'responsable' => $cambio['solicitante'],
                ],
            );
        }

        $calculador = app(CalculadorAvanceTablero::class);
        Tablero::query()->where('proyecto_id', $proyecto->id)->get()->each(
            fn (Tablero $tablero) => $calculador->recalcularYGuardar($tablero)
        );
    }
}
