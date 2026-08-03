<div
    class="fi-resource-relation-manager space-y-4"
    x-data
    x-on:arbol-reordenar-actividades.window="$wire.reordenarActividades($event.detail.ids)"
    x-on:arbol-reordenar-tareas.window="$wire.reordenarTareas($event.detail.ids, $event.detail.actividadId)"
>
    <div class="flex items-center justify-between gap-x-2">
        <x-filament::button
            icon="heroicon-o-plus"
            size="sm"
            wire:click="mountAction('crearActividad')"
        >
            {{ __('inspeccion.actividad.arbol.nueva_actividad') }}
        </x-filament::button>

        <x-filament::button
            :icon="$mostrarEliminados ? 'heroicon-o-eye-slash' : 'heroicon-o-trash'"
            size="sm"
            color="gray"
            wire:click="toggleMostrarEliminados"
        >
            {{ $mostrarEliminados ? __('inspeccion.actividad.arbol.ocultar_eliminados') : __('inspeccion.actividad.arbol.mostrar_eliminados') }}
        </x-filament::button>
    </div>

    @php $actividades = $this->getActividades(); @endphp

    @if ($actividades->isEmpty())
        <x-filament::empty-state
            icon="heroicon-o-rectangle-stack"
            :heading="__('inspeccion.actividad.arbol.sin_actividades')"
        />
    @else
        <div
            id="actividades-container-{{ $this->getOwnerRecord()->id }}"
            class="divide-y divide-gray-200 rounded-xl border border-gray-200 dark:divide-white/10 dark:border-white/10"
        >
            @foreach ($actividades as $actividad)
                <div
                    x-data="{ abierto: true }"
                    wire:key="actividad-{{ $actividad->id }}"
                    data-actividad-id="{{ $actividad->id }}"
                >
                    <div @class([
                        'flex items-center justify-between gap-x-2 p-3',
                        'opacity-60' => $actividad->trashed(),
                    ])>
                        @unless ($actividad->trashed() || $mostrarEliminados)
                            <span
                                class="actividad-drag-handle cursor-grab text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                title="{{ __('inspeccion.actividad.arbol.reordenar') }}"
                            >
                                <x-filament::icon icon="heroicon-s-bars-2" class="h-4 w-4" />
                            </span>
                        @endunless

                        <button
                            type="button"
                            x-on:click="abierto = !abierto"
                            @if (! $actividad->trashed())
                                x-on:dblclick="$wire.mountAction('editarActividad', { id: {{ $actividad->id }} })"
                            @endif
                            class="flex flex-1 items-center gap-x-2 text-left rtl:text-right"
                        >
                            <x-filament::icon
                                icon="heroicon-o-chevron-right"
                                x-bind:class="abierto ? 'rotate-90' : ''"
                                class="h-4 w-4 shrink-0 transition-transform"
                            />
                            <span class="font-medium">{{ $actividad->nombre }}</span>
                            @unless ($actividad->trashed())
                                <x-filament::badge :color="$actividad->estadoCalculado()->getColor()" :icon="$actividad->estadoCalculado()->getIcon()">
                                    {{ $actividad->estadoCalculado()->getLabel() }}
                                </x-filament::badge>
                            @endunless
                            <x-filament::badge color="gray">
                                {{ $actividad->tareas->count() }} {{ __('inspeccion.actividad.campos.cantidad_tareas') }}
                            </x-filament::badge>
                            @if (! is_null($avance = $actividad->avance()))
                                <x-filament::badge color="success">
                                    {{ $avance }}%
                                </x-filament::badge>
                            @endif
                            @if ($actividad->trashed())
                                <x-filament::badge color="danger">
                                    {{ __('inspeccion.actividad.arbol.eliminada') }}
                                </x-filament::badge>
                            @endif
                        </button>

                        <div class="flex items-center gap-x-1">
                            @if (! $actividad->trashed())
                                <x-filament::icon-button
                                    icon="heroicon-o-eye"
                                    :tooltip="__('inspeccion.actividad.detalle.ver')"
                                    :href="$this->urlDetalleActividad($actividad)"
                                />
                                <x-filament::icon-button
                                    icon="heroicon-o-plus"
                                    :tooltip="__('inspeccion.tarea.arbol.nueva_tarea')"
                                    wire:click="mountAction('crearTarea', { actividadId: {{ $actividad->id }} })"
                                />
                                <x-filament::icon-button
                                    icon="heroicon-o-pencil"
                                    :tooltip="__('inspeccion.actividad.arbol.editar')"
                                    wire:click="mountAction('editarActividad', { id: {{ $actividad->id }} })"
                                />
                                <x-filament::icon-button
                                    icon="heroicon-o-trash"
                                    color="danger"
                                    :tooltip="__('inspeccion.actividad.arbol.eliminar')"
                                    wire:click="mountAction('eliminarActividad', { id: {{ $actividad->id }} })"
                                />
                            @else
                                <x-filament::icon-button
                                    icon="heroicon-o-arrow-uturn-left"
                                    :tooltip="__('inspeccion.actividad.arbol.restaurar')"
                                    wire:click="mountAction('restaurarActividad', { id: {{ $actividad->id }} })"
                                />
                                <x-filament::icon-button
                                    icon="heroicon-o-x-mark"
                                    color="danger"
                                    :tooltip="__('inspeccion.actividad.arbol.eliminar_definitivo')"
                                    wire:click="mountAction('eliminarDefinitivoActividad', { id: {{ $actividad->id }} })"
                                />
                            @endif
                        </div>
                    </div>

                    <div x-show="abierto" x-collapse class="space-y-1 px-3 pb-3 ps-10">
                        <div
                            data-tareas-list
                            data-actividad-id="{{ $actividad->id }}"
                            class="space-y-1"
                        >
                            @forelse ($actividad->tareas as $tarea)
                                <div
                                    wire:key="tarea-{{ $tarea->id }}"
                                    data-tarea-id="{{ $tarea->id }}"
                                    @class([
                                        'flex items-center justify-between gap-x-2 rounded-lg border border-gray-100 px-2 py-1.5 dark:border-white/5',
                                        'opacity-60' => $tarea->trashed(),
                                    ])
                                >
                                    @unless ($tarea->trashed() || $mostrarEliminados)
                                        <span
                                            class="tarea-drag-handle cursor-grab text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                            title="{{ __('inspeccion.actividad.arbol.reordenar') }}"
                                        >
                                            <x-filament::icon icon="heroicon-s-bars-2" class="h-4 w-4" />
                                        </span>
                                    @endunless

                                    <div
                                        class="flex flex-1 items-center gap-x-2 text-left rtl:text-right"
                                        @if (! $tarea->trashed())
                                            x-on:dblclick="$wire.mountAction('editarTarea', { id: {{ $tarea->id }} })"
                                        @endif
                                    >
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $tarea->code }}</span>
                                        <span class="text-sm">{{ $tarea->nombre }}</span>
                                        <x-filament::badge :color="$tarea->status->getColor()">
                                            {{ $tarea->status->getLabel() }}
                                        </x-filament::badge>
                                        <x-filament::badge :color="$tarea->priority->getColor()">
                                            {{ $tarea->priority->getLabel() }}
                                        </x-filament::badge>
                                        @if (! $tarea->trashed() && $tarea->isOverdue())
                                            <x-filament::badge color="danger" icon="heroicon-o-flag">
                                                {{ __('inspeccion.tarea.arbol.vencida') }}
                                            </x-filament::badge>
                                        @endif
                                        @if ($tarea->trashed())
                                            <x-filament::badge color="danger">
                                                {{ __('inspeccion.tarea.arbol.eliminada') }}
                                            </x-filament::badge>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-x-1">
                                        @if (! $tarea->trashed())
                                            <x-filament::dropdown placement="bottom-end">
                                                <x-slot name="trigger">
                                                    <x-filament::icon-button
                                                        icon="heroicon-o-ellipsis-horizontal"
                                                        color="gray"
                                                    />
                                                </x-slot>
                                                <x-filament::dropdown.list>
                                                    <x-filament::dropdown.list.item
                                                        icon="heroicon-o-arrow-up-on-square"
                                                        x-on:click="close(); $wire.mountAction('insertarTarea', { id: {{ $tarea->id }}, position: 'before' })"
                                                    >
                                                        {{ __('inspeccion.tarea.arbol.insertar_antes') }}
                                                    </x-filament::dropdown.list.item>
                                                    <x-filament::dropdown.list.item
                                                        icon="heroicon-o-arrow-down-on-square"
                                                        x-on:click="close(); $wire.mountAction('insertarTarea', { id: {{ $tarea->id }}, position: 'after' })"
                                                    >
                                                        {{ __('inspeccion.tarea.arbol.insertar_despues') }}
                                                    </x-filament::dropdown.list.item>
                                                    <x-filament::dropdown.list.item
                                                        icon="heroicon-o-calendar-days"
                                                        color="info"
                                                        x-on:click="close(); $wire.mountAction('agendarFechasDesdeAnterior', { id: {{ $tarea->id }} })"
                                                    >
                                                        {{ __('inspeccion.tarea.arbol.agendar_desde_anterior') }}
                                                    </x-filament::dropdown.list.item>
                                                </x-filament::dropdown.list>
                                            </x-filament::dropdown>
                                            <x-filament::icon-button
                                                icon="heroicon-o-pencil"
                                                :tooltip="__('inspeccion.tarea.arbol.editar')"
                                                wire:click="mountAction('editarTarea', { id: {{ $tarea->id }} })"
                                            />
                                            <x-filament::icon-button
                                                icon="heroicon-o-trash"
                                                color="danger"
                                                :tooltip="__('inspeccion.tarea.arbol.eliminar')"
                                                wire:click="mountAction('eliminarTarea', { id: {{ $tarea->id }} })"
                                            />
                                        @else
                                            <x-filament::icon-button
                                                icon="heroicon-o-arrow-uturn-left"
                                                :tooltip="__('inspeccion.tarea.arbol.restaurar')"
                                                wire:click="mountAction('restaurarTarea', { id: {{ $tarea->id }} })"
                                            />
                                            <x-filament::icon-button
                                                icon="heroicon-o-x-mark"
                                                color="danger"
                                                :tooltip="__('inspeccion.tarea.arbol.eliminar_definitivo')"
                                                wire:click="mountAction('eliminarDefinitivoTarea', { id: {{ $tarea->id }} })"
                                            />
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="py-2 text-xs text-gray-400">
                                    {{ __('inspeccion.tarea.arbol.sin_tareas') }}
                                </p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <x-filament-panels::unsaved-action-changes-alert />

    <x-filament-actions::modals />
</div>

@assets
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
@endassets

@script
    <script>
        let _actividadSortable = null;
        const _tareaSortables = [];
        let _reinitTimer = null;

        function destroySortables() {
            _actividadSortable?.destroy();
            _actividadSortable = null;
            _tareaSortables.splice(0).forEach(s => s.destroy());
        }

        function initSortables() {
            destroySortables();

            const actividadesContainer = $el.querySelector('[id^="actividades-container-"]');
            if (actividadesContainer) {
                _actividadSortable = Sortable.create(actividadesContainer, {
                    animation: 150,
                    handle: '.actividad-drag-handle',
                    onEnd() {
                        const ids = [...actividadesContainer.querySelectorAll(':scope > [data-actividad-id]')]
                            .map(el => el.dataset.actividadId);
                        window.dispatchEvent(new CustomEvent('arbol-reordenar-actividades', {
                            detail: { ids },
                        }));
                    },
                });
            }

            $el.querySelectorAll('[data-tareas-list]').forEach(tareasEl => {
                const s = Sortable.create(tareasEl, {
                    animation: 150,
                    handle: '.tarea-drag-handle',
                    group: 'arbol-tareas',
                    onEnd(evt) {
                        const destContainer = evt.to;
                        const destIds = [...evt.to.querySelectorAll(':scope > [data-tarea-id]')]
                            .map(el => el.dataset.tareaId);
                        window.dispatchEvent(new CustomEvent('arbol-reordenar-tareas', {
                            detail: { ids: destIds, actividadId: destContainer?.dataset.actividadId },
                        }));
                        if (evt.from !== evt.to) {
                            const srcContainer = evt.from;
                            const srcIds = [...evt.from.querySelectorAll(':scope > [data-tarea-id]')]
                                .map(el => el.dataset.tareaId);
                            window.dispatchEvent(new CustomEvent('arbol-reordenar-tareas', {
                                detail: { ids: srcIds, actividadId: srcContainer?.dataset.actividadId },
                            }));
                        }
                    },
                });
                _tareaSortables.push(s);
            });
        }

        initSortables();

        Livewire.hook('morph.updated', ({ el }) => {
            if (el === $el || $el.contains(el)) {
                clearTimeout(_reinitTimer);
                _reinitTimer = setTimeout(() => initSortables(), 80);
            }
        });
    </script>
@endscript
