<?php

return [
    'name' => 'Inspeccion',

    /*
     * Roles reconocidos por el módulo mientras este repo es standalone.
     * TODO: reemplazar por roles/permisos reales (Shield) al integrar a axon.
     */
    'roles' => [
        'super_admin',
        'ingeniero',
        'supervisor',
        'tecnico',
        'calidad',
    ],

    /*
     * Matriz de permisos por rol. Cada clave se registra como Gate
     * en InspeccionServiceProvider::registerGates().
     */
    'permisos' => [
        'tablero.ver' => ['super_admin', 'ingeniero', 'supervisor', 'tecnico', 'calidad'],
        'tablero.gestionar' => ['super_admin', 'ingeniero'],
        'tablero_hito.actualizar' => ['super_admin', 'ingeniero', 'tecnico'],
        'tablero_actividad.gestionar' => ['super_admin', 'ingeniero'],
        'tablero_tarea.actualizar' => ['super_admin', 'ingeniero', 'tecnico'],
        'tablero_tarea.asignar' => ['super_admin', 'ingeniero', 'supervisor'],
        'visita_inspeccion.gestionar' => ['super_admin', 'supervisor', 'calidad'],
        'observacion.crear' => ['super_admin', 'supervisor', 'calidad'],
        'observacion.cerrar' => ['super_admin', 'ingeniero', 'supervisor', 'calidad'],
        'checklist_ejecucion.completar' => ['super_admin', 'supervisor', 'calidad'],
        'control_cambio.proponer' => ['super_admin', 'ingeniero', 'tecnico'],
        'control_cambio.decidir' => ['super_admin', 'supervisor'],
        'control_cambio.implementar' => ['super_admin', 'ingeniero'],
        'catalogo.gestionar' => ['super_admin'],
        'auditoria.purgar' => ['super_admin'],
        'usuario.gestionar' => ['super_admin'],
    ],
];
