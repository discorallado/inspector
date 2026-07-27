<?php

use App\Models\User;

it('un rol sin catalogo.gestionar no puede ver el listado de un catálogo de Configuración', function () {
    $user = User::factory()->create(['role' => 'calidad']);

    $this->actingAs($user)->get('/admin/configuracion/estado-avances')->assertForbidden();
});

it('super_admin sí puede ver el listado de un catálogo de Configuración', function () {
    $user = User::factory()->create(['role' => 'super_admin']);

    $this->actingAs($user)->get('/admin/configuracion/estado-avances')->assertSuccessful();
});
