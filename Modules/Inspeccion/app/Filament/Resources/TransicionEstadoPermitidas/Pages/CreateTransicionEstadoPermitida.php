<?php

namespace Modules\Inspeccion\Filament\Resources\TransicionEstadoPermitidas\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Inspeccion\Filament\Resources\TransicionEstadoPermitidas\TransicionEstadoPermitidaResource;

class CreateTransicionEstadoPermitida extends CreateRecord
{
    protected static string $resource = TransicionEstadoPermitidaResource::class;
}
