<?php

namespace Modules\Inspeccion\Filament\Resources\Users\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Inspeccion\Filament\Resources\Users\UserResource;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
