@php
    use Filament\Support\Enums\Width;

    $livewire ??= null;

    $hasTopbar = filament()->hasTopbar();
    $isSidebarCollapsibleOnDesktop = filament()->isSidebarCollapsibleOnDesktop();
    $isSidebarFullyCollapsibleOnDesktop = filament()->isSidebarFullyCollapsibleOnDesktop();
    $hasTopNavigation = filament()->hasTopNavigation();
    $hasNavigation = filament()->hasNavigation();
    $renderHookScopes = $livewire?->getRenderHookScopes();
    $maxContentWidth ??= (filament()->getMaxContentWidth() ?? Width::SevenExtraLarge);

    // Breadcrumbs reales de la página activa (Page::getBreadcrumbs()) para
    // inyectarlas en la fila del topbar — $livewire acá es la página, no
    // el topbar (que es un componente Livewire aparte sin este dato). Ver
    // el comentario más abajo, junto al topbar, para el porqué.
    $topbarBreadcrumbs = ($livewire && method_exists($livewire, 'getBreadcrumbs'))
        ? $livewire->getBreadcrumbs()
        : [];

    if (is_string($maxContentWidth)) {
        $maxContentWidth = Width::tryFrom($maxContentWidth) ?? $maxContentWidth;
    }
@endphp

<x-filament-panels::layout.base
    :livewire="$livewire"
    @class([
        'fi-body-has-navigation' => $hasNavigation,
        'fi-body-has-sidebar-collapsible-on-desktop' => $isSidebarCollapsibleOnDesktop,
        'fi-body-has-sidebar-fully-collapsible-on-desktop' => $isSidebarFullyCollapsibleOnDesktop,
        'fi-body-has-topbar' => $hasTopbar,
        'fi-body-has-top-navigation' => $hasTopNavigation,
    ])
>
    {{--
        Fork del layout base de Filament (ADR: sidebar "absorbe" la esquina
        del topbar). Único cambio real vs el original de
        vendor/filament/filament/resources/views/components/layout/index.blade.php:
        el topbar se renderiza DENTRO de .fi-main-ctn (después, junto al
        contenido) en vez de como hermano completo antes de .fi-layout.
        Motivo: .fi-sidebar vive dentro de .fi-layout con position:sticky en
        desktop — un sticky respeta su posición natural en el flujo del
        documento, así que con el topbar como hermano ANTES de .fi-layout
        (ocupando 4rem de alto en el flujo), ningún override de CSS
        (top:0, z-index, etc.) lograba que la sidebar arrancara en el borde
        real de la ventana: el hueco no era visual, era estructural. Moviendo
        el topbar adentro de .fi-main-ctn, la sidebar queda como la única
        columna hermana de .fi-layout que empieza en el top real, sin hacks.

        Mantener sincronizado con el original si Filament cambia esta vista
        en una actualización — revisar este archivo primero ante cualquier
        cambio raro de layout después de un `composer update`.
    --}}
    <a href="#fi-main-content" class="fi-skip-link fi-sr-only">
        {{ __('filament-panels::layout.skip_to_content.label') }}
    </a>

    @if (! $hasTopbar && $hasNavigation)
        <div
            @if ($isSidebarFullyCollapsibleOnDesktop)
                x-data="{}"
                x-bind:class="{ 'lg:fi-hidden': $store.sidebar.isOpen }"
            @endif
            @class([
                'fi-layout-sidebar-toggle-btn-ctn',
                'lg:fi-hidden' => ! $isSidebarFullyCollapsibleOnDesktop,
            ])
        >
            <x-filament::icon-button
                color="gray"
                :icon="\Filament\Support\Icons\Heroicon::OutlinedBars3"
                :icon-alias="\Filament\View\PanelsIconAlias::SIDEBAR_EXPAND_BUTTON"
                icon-size="lg"
                :label="__('filament-panels::layout.actions.sidebar.expand.label')"
                x-cloak
                x-data="{}"
                aria-controls="fi-main-sidebar"
                x-bind:aria-expanded="$store.sidebar.isOpen"
                x-on:click="$store.sidebar.open()"
                class="fi-layout-sidebar-toggle-btn"
            />
        </div>
    @endif

    <div class="fi-layout">
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::LAYOUT_START, scopes: $renderHookScopes) }}

        @if ($hasNavigation)
            <div
                x-cloak
                x-data="{}"
                x-on:click="$store.sidebar.close()"
                x-show="$store.sidebar.isOpen"
                x-transition.opacity.300ms
                class="fi-sidebar-close-overlay"
            ></div>

            @livewire(filament()->getSidebarLivewireComponent())
        @endif

        <div
            @if ($isSidebarCollapsibleOnDesktop)
                x-data="{}"
                x-bind:class="{
                    'fi-main-ctn-sidebar-open': $store.sidebar.isOpen,
                }"
                x-bind:style="'display: flex; opacity:1;'"
                {{-- Mimics `x-cloak`, as using `x-cloak` causes visual issues with chart widgets --}}
            @elseif ($isSidebarFullyCollapsibleOnDesktop)
                x-data="{}"
                x-bind:class="{
                    'fi-main-ctn-sidebar-open': $store.sidebar.isOpen,
                }"
                x-bind:style="'display: flex; opacity:1;'"
                {{-- Mimics `x-cloak`, as using `x-cloak` causes visual issues with chart widgets --}}
            @elseif (! ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop || $hasTopNavigation || (! $hasNavigation)))
                x-data="{}"
                x-bind:style="'display: flex; opacity:1;'" {{-- Mimics `x-cloak`, as using `x-cloak` causes visual issues with chart widgets --}}
            @endif
            class="fi-main-ctn"
        >
            @if ($hasTopbar)
                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::TOPBAR_BEFORE, scopes: $renderHookScopes) }}

                {{--
                    Wrapper posicionado (position:relative vía
                    .fi-topbar-with-breadcrumbs en theme.css) para poder
                    superponer el breadcrumb de la página sobre la fila del
                    topbar — el topbar es un @livewire aparte, autocontenido,
                    no se le puede "pasar" contenido por slot. El breadcrumb
                    se posiciona absolute encima suyo (solo desktop, lg:),
                    con los datos reales de $livewire->getBreadcrumbs()
                    calculados arriba. En mobile no se inyecta (topbar más
                    angosto, con el botón de hamburguesa ahí mismo) — el
                    breadcrumb default de la página (dentro de <main>) sigue
                    viéndose igual que siempre.
                --}}
                <div class="fi-topbar-with-breadcrumbs">
                    @if (filled($topbarBreadcrumbs))
                        <x-filament::breadcrumbs
                            :breadcrumbs="$topbarBreadcrumbs"
                            class="fi-topbar-injected-breadcrumbs"
                        />
                    @endif

                    @livewire(filament()->getTopbarLivewireComponent())
                </div>

                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::TOPBAR_AFTER, scopes: $renderHookScopes) }}
            @endif

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::CONTENT_BEFORE, scopes: $renderHookScopes) }}

            <main
                id="fi-main-content"
                tabindex="-1"
                @class([
                    'fi-main',
                    ($maxContentWidth instanceof Width) ? "fi-width-{$maxContentWidth->value}" : $maxContentWidth,
                ])
            >
                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::CONTENT_START, scopes: $renderHookScopes) }}

                {{ $slot }}

                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::CONTENT_END, scopes: $renderHookScopes) }}
            </main>

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::CONTENT_AFTER, scopes: $renderHookScopes) }}

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::FOOTER, scopes: $renderHookScopes) }}
        </div>

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::LAYOUT_END, scopes: $renderHookScopes) }}
    </div>
</x-filament-panels::layout.base>
