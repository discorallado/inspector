<x-filament-panels::page>
@php $ganttData = $this->getGanttData(); @endphp

<div class="flex flex-col gap-4">

    @if (empty($ganttData['data']))
        <div class="flex flex-col items-center justify-center py-16 text-gray-400 dark:text-gray-600">
            <x-filament::icon icon="heroicon-o-calendar-days" class="mb-3 h-12 w-12 opacity-40" />
            <p class="text-sm">{{ __('inspeccion.tarea.gantt.sin_tareas') }}</p>
        </div>
    @else

        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center gap-2">

            {{-- Escalas predefinidas --}}
            <div class="flex items-center gap-1 rounded-lg border border-gray-200 bg-white p-1 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                @foreach ([
                    'day' => __('inspeccion.tarea.gantt.escala_dia'),
                    'week' => __('inspeccion.tarea.gantt.escala_semana'),
                    'month' => __('inspeccion.tarea.gantt.escala_mes'),
                    'year' => __('inspeccion.tarea.gantt.escala_anio'),
                ] as $scale => $label)
                    <button
                        onclick="tableroGanttSetScale('{{ $scale }}')"
                        id="gantt-btn-{{ $scale }}"
                        class="rounded px-3 py-1 text-xs font-medium transition-colors
                               text-gray-600 hover:bg-gray-100
                               dark:text-gray-300 dark:hover:bg-gray-700"
                    >{{ $label }}</button>
                @endforeach
            </div>

            {{-- Zoom +/- y ajustar --}}
            <div class="flex items-center gap-1 rounded-lg border border-gray-200 bg-white p-1 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <button onclick="tableroGanttZoom('in')"
                        class="rounded px-2 py-1 text-sm font-bold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">＋</button>
                <button onclick="tableroGanttZoom('out')"
                        class="rounded px-2 py-1 text-sm font-bold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">－</button>
                <button onclick="tableroGanttFit()" title="{{ __('inspeccion.tarea.gantt.ajustar') }}"
                        class="rounded px-2 py-1 text-xs text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700">◉</button>
            </div>

            {{-- Actualizar --}}
            <button
                onclick="tableroGanttRefresh()"
                class="flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-600 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
            >
                <x-heroicon-o-arrow-path class="h-3.5 w-3.5" />
                {{ __('inspeccion.tarea.gantt.actualizar') }}
            </button>

        </div>

        {{-- Contenedor DHTMLX --}}
        <div
            id="dhx-gantt"
            class="overflow-hidden rounded-md border border-gray-200 shadow-sm dark:border-gray-700"
            style="width:100%; height:580px"
        ></div>

    @endif

</div>

{{-- Modal de edición de tarea (siempre en el DOM, oculto por defecto) --}}
<div id="gantt-modal"
     class="fixed inset-0 z-[9999] hidden items-center justify-center p-4"
     role="dialog" aria-modal="true">

    {{-- Overlay --}}
    <div id="gantt-modal-overlay"
         class="absolute inset-0 bg-gray-950/75 backdrop-blur-sm"
         onclick="tableroGanttCloseModal()"></div>

    {{-- Tarjeta --}}
    <div class="relative z-10 w-full max-w-md rounded-xl bg-white shadow-2xl ring-1 ring-gray-950/5
                dark:bg-gray-900 dark:ring-white/10">

        {{-- Cabecera --}}
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-white/10">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                {{ __('inspeccion.tarea.gantt.modal_editar') }}
            </h2>
            <button onclick="tableroGanttCloseModal()" type="button"
                    class="rounded-lg p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600
                           dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300">
                <x-heroicon-m-x-mark class="h-5 w-5" />
            </button>
        </div>

        {{-- Cuerpo --}}
        <div class="space-y-4 px-6 py-5">
            <input type="hidden" id="gantt-modal-id">

            {{-- Nombre --}}
            <div>
                <label for="gantt-modal-title"
                       class="mb-1.5 block text-sm font-medium text-gray-950 dark:text-white">
                    {{ __('inspeccion.tarea.campos.nombre') }}
                </label>
                <input type="text" id="gantt-modal-title" required
                       class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm
                              text-gray-900 shadow-sm transition duration-75
                              placeholder:text-gray-400
                              focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500
                              dark:border-white/10 dark:bg-white/5 dark:text-white dark:placeholder:text-gray-500">
            </div>

            {{-- Descripción --}}
            <div>
                <label for="gantt-modal-desc"
                       class="mb-1.5 block text-sm font-medium text-gray-950 dark:text-white">
                    {{ __('inspeccion.tarea.campos.descripcion') }}
                </label>
                <textarea id="gantt-modal-desc" rows="2"
                          class="block w-full resize-none rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm
                                 text-gray-900 shadow-sm transition duration-75
                                 placeholder:text-gray-400
                                 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500
                                 dark:border-white/10 dark:bg-white/5 dark:text-white dark:placeholder:text-gray-500"></textarea>
            </div>

            {{-- Fechas --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="gantt-modal-start"
                           class="mb-1.5 block text-sm font-medium text-gray-950 dark:text-white">
                        {{ __('inspeccion.tarea.campos.start_date') }}
                    </label>
                    <input type="date" id="gantt-modal-start"
                           class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm
                                  text-gray-900 shadow-sm
                                  focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500
                                  dark:border-white/10 dark:bg-white/5 dark:text-white dark:[color-scheme:dark]">
                </div>
                <div>
                    <label for="gantt-modal-end"
                           class="mb-1.5 block text-sm font-medium text-gray-950 dark:text-white">
                        {{ __('inspeccion.tarea.campos.due_date') }}
                    </label>
                    <input type="date" id="gantt-modal-end"
                           class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm
                                  text-gray-900 shadow-sm
                                  focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500
                                  dark:border-white/10 dark:bg-white/5 dark:text-white dark:[color-scheme:dark]">
                </div>
            </div>
        </div>

        {{-- Pie --}}
        <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-white/10">
            <button onclick="tableroGanttCloseModal()" type="button"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700
                           shadow-sm transition-colors hover:bg-gray-50
                           dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5">
                {{ __('inspeccion.tarea.gantt.modal_cancelar') }}
            </button>
            <button onclick="tableroGanttSaveModal()" type="button"
                    class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white
                           shadow-sm transition-colors hover:bg-primary-500
                           focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2
                           dark:bg-primary-500 dark:hover:bg-primary-400">
                {{ __('inspeccion.tarea.gantt.modal_guardar') }}
            </button>
        </div>

    </div>
</div>

{{--
    dhtmlx-gantt@10.0.0 vía jsdelivr, apuntando directo al paquete npm
    real (edición Community, MIT desde la v10) — NO el CDN
    cdn.dhtmlx.com/gantt/edge/... que usa axon hoy, que no fija
    versión/edición y puede resolver a un build de evaluación PRO con
    marca de agua (ver ADR 0015). Versión fija a propósito: un "edge"
    silencioso podría cambiar de comportamiento o de licencia sin que
    nadie se entere acá.
--}}
@assets
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/dhtmlx-gantt@10.0.0/codebase/dhtmlxgantt.css">
<script src="https://cdn.jsdelivr.net/npm/dhtmlx-gantt@10.0.0/codebase/dhtmlxgantt.js"></script>
<style>
/* ── Progreso: barra más visible y NO editable ────────────────────────────── */
.gantt_task_progress {
    background: rgba(255, 255, 255, 0.48);
    border-right: 2px solid rgba(255, 255, 255, 0.85);
}
.gantt_task_progress_drag { display: none !important; }

/* ── Filas de actividad (type=project) ────────────────────────────────────── */
.gantt_task_line.gantt_project { background: #475569; border-color: #334155; }
.gantt_task_line.gantt_project .gantt_task_progress {
    background: rgba(255, 255, 255, 0.35);
    border-right: 2px solid rgba(255, 255, 255, 0.6);
}

/* ── Botón de escala activo ───────────────────────────────────────────────── */
.gantt-scale-active {
    background: rgb(var(--primary-500, 59 130 246)) !important;
    color: #fff !important;
}

/* ══════════════════════════════════════════════════════════════════════════
   DARK MODE
   ══════════════════════════════════════════════════════════════════════════ */
.dark .gantt_container,
.dark #dhx-gantt {
    background: #0f172a;
    border-color: #1e293b;
    color: #e2e8f0;
}
.dark .gantt_grid_scale,
.dark .gantt_task_scale { background: #1e293b; border-color: #334155; }
.dark .gantt_scale_cell,
.dark .gantt_grid_head_cell { color: #94a3b8; border-color: #334155; }
.dark .gantt_grid_data { background: #0f172a; }
.dark .gantt_cell { color: #cbd5e1; border-color: #1e293b; }
.dark .gantt_row,
.dark .gantt_row.odd { background: #0f172a; border-color: #1e293b; }
.dark .gantt_row:hover,
.dark .gantt_row.gantt_selected,
.dark .gantt_task_row.gantt_selected { background: #172033 !important; }
.dark .gantt_task { background: #0f172a; }
.dark .gantt_task_row,
.dark .gantt_task_row.odd { background: #0f172a; border-color: #1e293b; }
.dark .gantt_task_row:hover { background: #172033; }
.dark .gantt_task_cell { border-color: #1e293b; }
.dark .gantt_task_cell.week_end { background: #131f31; }
.dark .gantt_grid_column_resize_wrap { background: #334155; }
.dark .gantt_line_wrapper div { background: #64748b; }
.dark .gantt_link_arrow { border-color: transparent #64748b transparent transparent; }
.dark .gantt_tooltip { background: #1e293b; color: #e2e8f0; border: 1px solid #475569; }
.dark .gantt_ver_scroll,
.dark .gantt_hor_scroll { background: #1e293b; }
.dark .gantt_drag_marker { background: rgba(59,130,246,0.15); border: 1px dashed #3b82f6; }
.dark .gantt_tree_content { color: #e2e8f0; }
.dark .gantt_tree_icon.gantt_open,
.dark .gantt_tree_icon.gantt_close { filter: invert(0.7); }
</style>
@endassets

@script
<script>
var _ganttData = @json($ganttData);

/* ── Nombres de niveles (índice = orden en zoom.levels) ──────────────────── */
var LEVEL_NAMES = ['day', 'week', 'month', 'year'];
var _levelName = 'week';

function syncButtons(name) {
    document.querySelectorAll('[id^="gantt-btn-"]').forEach(function (b) {
        b.classList.remove('gantt-scale-active');
    });
    var btn = document.getElementById('gantt-btn-' + name);
    if (btn) btn.classList.add('gantt-scale-active');
}

/* ── API pública ─────────────────────────────────────────────────────────── */
window.tableroGanttSetScale = function (name) { gantt.ext.zoom.setLevel(name); };
window.tableroGanttZoom = function (dir) {
    if (dir === 'in') gantt.ext.zoom.zoomIn();
    else gantt.ext.zoom.zoomOut();
};
window.tableroGanttRefresh = function () { $wire.refreshData(); };
window.tableroGanttFit = function () {
    delete gantt.config.start_date;
    delete gantt.config.end_date;
    gantt.render();
    gantt.scrollTo(0, null);
};

/* ── Modal de edición de tarea ───────────────────────────────────────────── */
var _ganttModalId = null;

var _fmtDate = function (d) {
    if (!d) return '';
    var p = function (n) { return String(n).padStart(2, '0'); };
    return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate());
};

window.tableroGanttOpenModal = function (id) {
    var task = gantt.getTask(id);
    _ganttModalId = id;
    document.getElementById('gantt-modal-id').value = id;
    document.getElementById('gantt-modal-title').value = task.text || '';
    document.getElementById('gantt-modal-desc').value = task.description || '';
    document.getElementById('gantt-modal-start').value = _fmtDate(task.start_date);
    document.getElementById('gantt-modal-end').value = _fmtDate(task.end_date);
    var el = document.getElementById('gantt-modal');
    el.classList.remove('hidden');
    el.classList.add('flex');
};

window.tableroGanttCloseModal = function () {
    var el = document.getElementById('gantt-modal');
    el.classList.add('hidden');
    el.classList.remove('flex');
    _ganttModalId = null;
};

window.tableroGanttSaveModal = function () {
    var id = _ganttModalId;
    if (!id) return;

    var title = document.getElementById('gantt-modal-title').value.trim();
    var desc = document.getElementById('gantt-modal-desc').value;
    var start = document.getElementById('gantt-modal-start').value;
    var end = document.getElementById('gantt-modal-end').value;

    if (!title) {
        document.getElementById('gantt-modal-title').focus();
        return;
    }

    /* Actualizar el gantt localmente para respuesta inmediata */
    var task = gantt.getTask(id);
    task.text = title;
    task.description = desc;
    if (start) task.start_date = new Date(start + 'T00:00:00');
    if (end) task.end_date = new Date(end + 'T00:00:00');
    gantt.updateTask(id);

    /* Persistir en el servidor */
    $wire.updateTareaDetalles(String(id), title, desc, start, end);

    tableroGanttCloseModal();
};

/* Cerrar con Escape */
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && _ganttModalId) tableroGanttCloseModal();
});

/* Interceptar el lightbox nativo y mostrar el modal personalizado */
gantt.attachEvent('onBeforeLightbox', function (id) {
    var task = gantt.getTask(id);
    if (task.type === gantt.config.types.project) return false;
    tableroGanttOpenModal(id);
    return false;
});

/*
 * Bloquea dependencias que involucren una fila de Actividad (id con
 * prefijo "act-") — tarea_links solo soporta Tarea-Tarea acá, ver
 * Tarea::linksComoOrigen(). El servidor (agregarLink()) igual lo valida
 * si esto se saltea de algún modo.
 */
gantt.attachEvent('onBeforeLinkAdd', function (id, link) {
    return !(String(link.source).startsWith('act-') || String(link.target).startsWith('act-'));
});

/* ── Zoom (gantt.ext.zoom) ───────────────────────────────────────────────── */
gantt.ext.zoom.init({
    levels: [
        {
            name: 'day',
            scale_height: 50,
            min_column_width: 60,
            scales: [
                { unit: 'month', step: 1, format: '%F %Y' },
                { unit: 'day', step: 1, format: '%d' },
            ],
        },
        {
            name: 'week',
            scale_height: 50,
            min_column_width: 50,
            scales: [
                { unit: 'month', step: 1, format: '%F %Y' },
                { unit: 'week', step: 1, format: 'Sem %W' },
            ],
        },
        {
            name: 'month',
            scale_height: 50,
            min_column_width: 120,
            scales: [
                { unit: 'year', step: 1, format: '%Y' },
                { unit: 'month', step: 1, format: '%F' },
            ],
        },
        {
            name: 'year',
            scale_height: 50,
            min_column_width: 100,
            scales: [
                { unit: 'year', step: 1, format: '%Y' },
                {
                    unit: 'quarter',
                    step: 1,
                    format: function (date) {
                        const quarter = Math.floor(date.getMonth() / 3) + 1;
                        return 'Trimestre 0' + quarter;
                    },
                },
            ],
        },
    ],
    startIndex: 1,
    trigger: 'wheel',
    useKey: 'ctrlKey',
    element: function () { return gantt.$root.querySelector('.gantt_task'); },
});

gantt.ext.zoom.attachEvent('onLevelChange', function (level) {
    _levelName = LEVEL_NAMES[level] || 'week';
    syncButtons(_levelName);
});

/* ── Configuración general ───────────────────────────────────────────────── */
gantt.i18n.setLocale('es');
gantt.config.date_format = '%Y-%m-%d';
gantt.config.fit_tasks = false;
gantt.config.open_tree_initially = true;
gantt.config.scroll_on_click = true;
gantt.config.drag_progress = false;
gantt.config.grid_resize = true;
gantt.config.grid_width = 320;

gantt.config.columns = [
    { name: 'text', label: '{{ __('inspeccion.tarea.gantt.columna_nombre') }}', width: '*', tree: true, resize: true },
    { name: 'duration', label: '{{ __('inspeccion.tarea.gantt.columna_duracion') }}', width: 72, align: 'center', resize: true },
    { name: 'start_date', label: '{{ __('inspeccion.tarea.gantt.columna_inicio') }}', width: 90, align: 'center', resize: true },
    { name: 'end_date', label: '{{ __('inspeccion.tarea.gantt.columna_fin') }}', width: 90, align: 'center', resize: true },
];

/* ── Eventos ─────────────────────────────────────────────────────────────── */
gantt.attachEvent('onAfterTaskDrag', function (id, mode) {
    if (String(id).startsWith('act-')) return true;
    var task = gantt.getTask(id);
    var fmt = function (d) {
        var p = function (n) { return String(n).padStart(2, '0'); };
        return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate());
    };
    $wire.updateTareaFechas(String(id), fmt(task.start_date), fmt(task.end_date));
    return true;
});

gantt.attachEvent('onRowDragEnd', function (id) {
    var actividadIds = [];
    var tareaOrdenes = [];

    gantt.eachTask(function (item) {
        if (item.$level !== 0) return;
        actividadIds.push(String(item.id).slice(4));

        var idsHijos = [];
        gantt.eachTask(function (child) {
            idsHijos.push(String(child.id));
        }, item.id);

        tareaOrdenes.push({
            actividadId: String(item.id).slice(4),
            tareaIds: idsHijos,
        });
    });

    $wire.persistirOrden(actividadIds, tareaOrdenes);
    return true;
});

gantt.attachEvent('onAfterLinkAdd', function (id, link) {
    $wire.agregarLink(String(link.source), String(link.target), link.type)
        .then(function (newId) {
            if (String(newId) !== String(id)) gantt.changeLinkId(id, newId);
        });
    return true;
});

gantt.attachEvent('onAfterLinkDelete', function (id) {
    $wire.eliminarLink(String(id));
    return true;
});

$wire.on('gantt:refresh', function (payload) {
    var data = Array.isArray(payload) ? payload[0] : payload;
    gantt.clearAll();
    gantt.parse(data);
});

gantt.config.order_branch = true;
gantt.config.order_branch_free = false;

/* ── Inicializar ─────────────────────────────────────────────────────────── */
gantt.init('dhx-gantt');
gantt.parse(_ganttData);

syncButtons('week');
</script>
@endscript
</x-filament-panels::page>
