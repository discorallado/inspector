<x-filament-panels::page>
    {{-- Cabecera de la actividad --}}
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1">
                <x-filament::badge :color="$this->actividad->estadoCalculado()->getColor()" :icon="$this->actividad->estadoCalculado()->getIcon()" size="lg">
                    {{ $this->actividad->estadoCalculado()->getLabel() }}
                </x-filament::badge>
                <h2 class="text-xl font-bold text-gray-950 dark:text-white">
                    {{ $this->actividad->nombre }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $this->actividad->descripcion ?: __('inspeccion.actividad.detalle.sin_descripcion') }}
                </p>
            </div>

            <div class="flex items-center gap-6 text-sm text-gray-500 dark:text-gray-400">
                @if ($this->actividad->start_date)
                    <div class="flex items-center gap-1">
                        <x-filament::icon icon="heroicon-m-arrow-right-circle" class="h-4 w-4 shrink-0" />
                        {{ $this->actividad->start_date->format('d/m/Y') }}
                    </div>
                @endif
                @if ($this->actividad->end_date)
                    <div class="flex items-center gap-1">
                        <x-filament::icon icon="heroicon-m-flag" class="h-4 w-4 shrink-0" />
                        {{ $this->actividad->end_date->format('d/m/Y') }}
                    </div>
                @endif
                @php
                    $total = $this->actividad->tareas->count();
                    $done = $this->actividad->tareas->filter(fn ($t) => $t->status->isCompleted())->count();
                @endphp
                <div class="flex items-center gap-1 font-medium text-gray-700 dark:text-gray-300">
                    <x-filament::icon icon="heroicon-m-check-circle" class="h-4 w-4 shrink-0 text-success-500" />
                    {{ $done }}/{{ $total }} {{ __('inspeccion.tarea.plural') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Tareas --}}
    <div class="space-y-3">
        <h3 class="text-base font-semibold text-gray-950 dark:text-white">{{ __('inspeccion.tarea.plural') }}</h3>

        @forelse ($this->actividad->tareas as $tarea)
            @php $isFocus = $tarea->id === $this->focusTareaId; @endphp

            <div
                id="tarea-{{ $tarea->id }}"
                data-tarea-id="{{ $tarea->id }}"
                x-data="{ openComments: {{ $isFocus ? 'true' : 'false' }} }"
                @if ($isFocus)
                    x-init="setTimeout(() => { $el.scrollIntoView({ behavior: 'smooth', block: 'center' }); $el.classList.add('ring-2', 'ring-primary-500'); setTimeout(() => $el.classList.remove('ring-2', 'ring-primary-500'), 3000) }, 300)"
                @endif
                class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 transition-all dark:bg-gray-900 dark:ring-white/10"
            >
                <div class="flex flex-wrap items-center gap-3 p-4">
                    <x-filament::badge :color="$tarea->status->getColor()" size="sm">
                        {{ $tarea->status->getLabel() }}
                    </x-filament::badge>

                    <span class="font-mono text-xs text-gray-400 dark:text-gray-500">{{ $tarea->code }}</span>

                    <span @class([
                        'flex-1 text-sm font-medium text-gray-950 dark:text-white',
                        'line-through opacity-40' => $tarea->status->isCompleted(),
                    ])>
                        {{ $tarea->nombre }}
                    </span>

                    <x-filament::badge :color="$tarea->priority->getColor()" size="sm">
                        {{ $tarea->priority->getLabel() }}
                    </x-filament::badge>

                    <div class="flex items-center gap-3 text-xs text-gray-400 dark:text-gray-500">
                        @if ($tarea->start_date)
                            <span class="flex items-center gap-1">
                                <x-filament::icon icon="heroicon-m-arrow-right-circle" class="h-3 w-3" />
                                {{ $tarea->start_date->format('d/m/Y') }}
                            </span>
                        @endif
                        @if ($tarea->due_date)
                            <span @class([
                                'flex items-center gap-1',
                                'font-semibold text-danger-600 dark:text-danger-400' => $tarea->isOverdue(),
                            ])>
                                <x-filament::icon icon="heroicon-m-flag" class="h-3 w-3" />
                                {{ $tarea->due_date->format('d/m/Y') }}
                            </span>
                        @endif
                    </div>

                    <button
                        type="button"
                        x-on:click="openComments = !openComments"
                        class="ml-auto flex items-center gap-1 rounded-lg px-2 py-1 text-xs text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                    >
                        <x-filament::icon icon="heroicon-m-chat-bubble-left-ellipsis" class="h-4 w-4" />
                        {{ $tarea->filamentComments()->count() }}
                    </button>
                </div>

                @if ($tarea->descripcion)
                    <div class="border-t border-gray-100 px-4 py-2 text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400">
                        {{ $tarea->descripcion }}
                    </div>
                @endif

                <div x-show="openComments" class="border-t border-gray-100 p-4 dark:border-gray-800">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                        {{ __('inspeccion.tarea.singular') }} — {{ __('filament-comments::filament-comments.comments') }}
                    </p>
                    <livewire:comments :record="$tarea" :key="'tarea-comments-'.$tarea->id" />
                </div>
            </div>
        @empty
            <div class="rounded-xl bg-white px-6 py-10 text-center text-sm text-gray-400 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                {{ __('inspeccion.tarea.arbol.sin_tareas') }}
            </div>
        @endforelse
    </div>

    {{-- Comentarios de la actividad --}}
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <h3 class="mb-4 text-base font-semibold text-gray-950 dark:text-white">
            {{ __('inspeccion.actividad.detalle.comentarios') }}
        </h3>
        <livewire:comments :record="$this->actividad" :key="'actividad-comments-'.$this->actividad->id" />
    </div>
</x-filament-panels::page>
