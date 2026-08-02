<?php

namespace Modules\Inspeccion\Filament\Resources\Tableros\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Modules\Inspeccion\Filament\Resources\Tableros\TableroResource;

class EditTablero extends EditRecord
{
    protected static string $resource = TableroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kanban')
                ->label(__('inspeccion.tarea.kanban.title'))
                ->icon(Heroicon::OutlinedViewColumns)
                ->url(fn (): string => TableroResource::getUrl('kanban', ['record' => $this->record])),
            DeleteAction::make(),
        ];
    }
}
