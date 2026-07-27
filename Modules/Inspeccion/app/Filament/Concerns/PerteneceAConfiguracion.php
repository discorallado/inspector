<?php

namespace Modules\Inspeccion\Filament\Concerns;

use Modules\Inspeccion\Filament\Clusters\Configuracion\ConfiguracionCluster;

trait PerteneceAConfiguracion
{
    protected static ?string $cluster = ConfiguracionCluster::class;
}
