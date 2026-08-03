<x-filament-panels::page>
    {{-- Filtros --}}
    <div class="mb-4 flex flex-wrap gap-3">
        <div class="w-56">
            <select
                wire:model.live="filterActividad"
                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            >
                <option value="">{{ __('inspeccion.tarea.kanban.todas_las_actividades') }}</option>
                @foreach ($this->getActividadesParaFiltro() as $id => $nombre)
                    <option value="{{ $id }}">{{ $nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-44">
            <select
                wire:model.live="filterPriority"
                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            >
                <option value="">{{ __('inspeccion.tarea.kanban.todas_las_prioridades') }}</option>
                @foreach ($this->getPrioridadesParaFiltro() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Tablero --}}
    <div class="flex gap-4 overflow-x-auto pb-4" id="kanban-board">
        @foreach ($this->getColumns() as $column)
            @php $status = $column['status']; @endphp
            <div
                class="w-[calc((100vw-10rem)/2)] min-w-[280px] flex-shrink-0 md:w-[calc((100vw-10rem)/3)] xl:w-[calc((100vw-10rem)/4)]"
                wire:key="column-{{ $status->value }}"
            >
                <div class="flex h-full flex-col rounded-xl bg-gray-100 p-3 dark:bg-gray-800">
                    {{-- Cabecera --}}
                    <div
                        class="mb-3 flex items-center justify-between rounded-lg px-3 py-2"
                        style="background-color: color-mix(in srgb, var(--color-{{ $status->getColor() }}-500, #6b7280) 15%, transparent)"
                    >
                        <span class="text-sm font-bold text-gray-800 dark:text-gray-100">
                            {{ $status->getLabel() }}
                        </span>
                        <span
                            class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold text-white"
                            style="background-color: var(--color-{{ $status->getColor() }}-500, #6b7280)"
                        >{{ $column['tareas']->count() }}</span>
                    </div>

                    {{-- Zona sortable --}}
                    <div
                        class="kanban-column min-h-16 flex-1 space-y-2"
                        data-status="{{ $status->value }}"
                        id="kanban-col-{{ $status->value }}"
                    >
                        @forelse ($column['tareas'] as $tarea)
                            <div
                                class="kanban-card cursor-grab rounded-lg border border-gray-200 bg-white p-3 shadow-sm active:cursor-grabbing dark:border-gray-700 dark:bg-gray-900"
                                data-tarea-id="{{ $tarea->id }}"
                                wire:key="tarea-{{ $tarea->id }}"
                            >
                                {{-- Fila superior: código + prioridad + fecha --}}
                                <div class="mb-2 flex items-center gap-1.5">
                                    <span class="font-mono text-[11px] text-gray-400 dark:text-gray-500">
                                        {{ $tarea->code }}
                                    </span>

                                    @if ($tarea->priority)
                                        <span
                                            class="inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 text-[10px] font-semibold"
                                            style="
                                                background-color: color-mix(in srgb, var(--color-{{ $tarea->priority->getColor() }}-500, #6b7280) 15%, transparent);
                                                color: var(--color-{{ $tarea->priority->getColor() }}-700, #374151);
                                            "
                                        >
                                            {{ $tarea->priority->getLabel() }}
                                        </span>
                                    @endif

                                    <span class="ml-auto shrink-0">
                                        @if ($tarea->due_date)
                                            <span @class([
                                                'text-[11px]',
                                                'font-semibold text-danger-600 dark:text-danger-400' => $tarea->due_date->isPast() && ! $tarea->status->isCompleted(),
                                                'text-gray-400 dark:text-gray-500' => ! ($tarea->due_date->isPast() && ! $tarea->status->isCompleted()),
                                            ])>
                                                {{ $tarea->due_date->isoFormat('D MMM') }}
                                            </span>
                                        @endif
                                    </span>
                                </div>

                                {{-- Nombre --}}
                                <p class="line-clamp-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $tarea->nombre }}
                                </p>

                                {{-- Descripción --}}
                                @if ($tarea->descripcion)
                                    <p class="mt-1 line-clamp-2 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $tarea->descripcion }}
                                    </p>
                                @endif

                                {{-- Pie: actividad + peso + comentarios --}}
                                <div class="mt-3 flex items-center justify-between text-xs text-gray-400 dark:text-gray-500">
                                    <span class="truncate">{{ $tarea->actividad->nombre }}</span>

                                    <div class="flex shrink-0 items-center gap-2">
                                        @if ($tarea->peso !== null)
                                            <span class="font-semibold">{{ $tarea->peso }}%</span>
                                        @endif

                                        <a
                                            href="{{ $this->urlDetalleTarea($tarea) }}"
                                            title="{{ __('inspeccion.tarea.detalle.ver_comentarios') }}"
                                            class="flex items-center gap-0.5 rounded px-1 py-0.5 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                                        >
                                            <x-filament::icon icon="heroicon-o-chat-bubble-left-ellipsis" class="h-3.5 w-3.5" />
                                            {{ $tarea->filament_comments_count }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="py-6 text-center text-xs text-gray-400 dark:text-gray-600">
                                {{ __('inspeccion.tarea.kanban.columna_vacia') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @assets
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
    @endassets

    @script
    <script>
        function initKanban() {
            document.querySelectorAll('.kanban-column').forEach(function (col) {
                if (col._sortable) col._sortable.destroy();
                col._sortable = Sortable.create(col, {
                    group: 'kanban',
                    animation: 150,
                    ghostClass: 'opacity-40',
                    dragClass: 'shadow-xl',
                    onEnd: function (evt) {
                        const tareaId = evt.item.dataset.tareaId;
                        const newStatus = evt.to.dataset.status;
                        if (tareaId && newStatus) $wire.updateTareaStatus(tareaId, newStatus);
                    },
                });
            });
        }

        initKanban();
        Livewire.hook('morph.updated', () => initKanban());
    </script>
    @endscript
</x-filament-panels::page>
