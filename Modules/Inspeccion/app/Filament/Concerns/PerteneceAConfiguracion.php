<?php

namespace Modules\Inspeccion\Filament\Concerns;

use Modules\Inspeccion\Filament\Clusters\Configuracion\ConfiguracionCluster;

trait PerteneceAConfiguracion
{
    public static function getCluster(): ?string
    {
        return ConfiguracionCluster::class;
    }
}
