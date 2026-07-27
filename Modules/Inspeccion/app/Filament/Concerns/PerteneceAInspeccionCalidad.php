<?php

namespace Modules\Inspeccion\Filament\Concerns;

use Modules\Inspeccion\Filament\Clusters\InspeccionCalidad\InspeccionCalidadCluster;

trait PerteneceAInspeccionCalidad
{
    public static function getCluster(): ?string
    {
        return InspeccionCalidadCluster::class;
    }
}
