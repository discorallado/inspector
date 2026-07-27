<?php

namespace Modules\Inspeccion\Filament\Clusters\Configuracion;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class ConfiguracionCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('inspeccion.navigation.cluster_configuracion');
    }
}
