<?php

namespace Modules\Inspeccion\Filament\Resources\Users;

use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Concerns\PerteneceAConfiguracion;
use Modules\Inspeccion\Filament\Resources\Users\Pages\CreateUser;
use Modules\Inspeccion\Filament\Resources\Users\Pages\EditUser;
use Modules\Inspeccion\Filament\Resources\Users\Pages\ListUsers;
use Modules\Inspeccion\Filament\Resources\Users\Schemas\UserForm;
use Modules\Inspeccion\Filament\Resources\Users\Tables\UsersTable;

/**
 * Cierra el gap documentado en SESSION.md: sin esto, un usuario nuevo queda
 * con role = null (sin acceso a nada) hasta que alguien se lo asigna por
 * tinker a mano. Solo super_admin puede crear usuarios y asignar rol.
 */
class UserResource extends Resource
{
    use PerteneceAConfiguracion;

    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return __('inspeccion.navigation.grupo_usuarios');
    }

    public static function getModelLabel(): string
    {
        return __('inspeccion.usuario.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('inspeccion.usuario.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
