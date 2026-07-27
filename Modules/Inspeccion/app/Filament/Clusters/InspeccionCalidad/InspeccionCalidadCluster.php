<?php

namespace Modules\Inspeccion\Filament\Clusters\InspeccionCalidad;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class InspeccionCalidadCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('inspeccion.navigation.cluster_inspeccion_calidad');
    }
}
