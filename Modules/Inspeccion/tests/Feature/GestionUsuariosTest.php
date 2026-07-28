<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\Inspeccion\Filament\Resources\Users\Pages\CreateUser;

it('super_admin puede crear un usuario nuevo y asignarle un rol', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);

    Livewire::actingAs($admin)
        ->test(CreateUser::class)
        ->fillForm([
            'name' => 'Nuevo Inspector',
            'email' => 'nuevo.inspector@example.com',
            'password' => 'password',
            'role' => 'calidad',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $creado = User::query()->where('email', 'nuevo.inspector@example.com')->first();

    expect($creado)->not->toBeNull()
        ->and($creado->role)->toBe('calidad');
});

it('un usuario sin permiso de gestión no puede ver el listado de Usuarios', function () {
    $sinPermiso = User::factory()->create(['role' => 'calidad']);

    $this->actingAs($sinPermiso)->get('/admin/configuracion/users')->assertForbidden();
});

it('un usuario recién creado sin rol asignado no puede hacer nada en el panel', function () {
    $sinRol = User::factory()->create(['role' => null]);

    $this->actingAs($sinRol)->get('/admin/tableros')->assertForbidden();
});
