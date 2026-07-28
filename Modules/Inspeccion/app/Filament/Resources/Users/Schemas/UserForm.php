<?php

namespace Modules\Inspeccion\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('inspeccion.usuario.campos.name'))
                ->required(),
            TextInput::make('email')
                ->label(__('inspeccion.usuario.campos.email'))
                ->email()
                ->required()
                ->unique(ignoreRecord: true),
            TextInput::make('password')
                ->label(__('inspeccion.usuario.campos.password'))
                ->password()
                ->revealable()
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrated(fn (?string $state) => filled($state))
                ->helperText(fn (string $operation) => $operation === 'edit'
                    ? __('inspeccion.usuario.ayuda.password_opcional')
                    : null),
            Select::make('role')
                ->label(__('inspeccion.usuario.campos.role'))
                ->options(collect(config('inspeccion.roles'))->mapWithKeys(
                    fn (string $rol) => [$rol => Str::headline($rol)]
                ))
                ->required()
                ->native(false),
        ]);
    }
}
