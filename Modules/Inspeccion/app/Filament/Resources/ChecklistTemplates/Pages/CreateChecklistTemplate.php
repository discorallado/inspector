<?php

namespace Modules\Inspeccion\Filament\Resources\ChecklistTemplates\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Inspeccion\Filament\Resources\ChecklistTemplates\ChecklistTemplateResource;

class CreateChecklistTemplate extends CreateRecord
{
    protected static string $resource = ChecklistTemplateResource::class;
}
