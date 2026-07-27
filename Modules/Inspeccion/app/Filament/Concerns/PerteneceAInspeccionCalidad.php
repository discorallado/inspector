<?php

namespace Modules\Inspeccion\Filament\Concerns;

use Modules\Inspeccion\Filament\Clusters\InspeccionCalidad\InspeccionCalidadCluster;

trait PerteneceAInspeccionCalidad
{
    protected static ?string $cluster = InspeccionCalidadCluster::class;
}
