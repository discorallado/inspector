<?php

return [
    'navigation' => [
        'cluster_configuracion' => 'Configuración',
        'grupo_inspeccion' => 'Inspección',
        'grupo_seguimiento_tableros' => 'Seguimiento de Tableros',
        'grupo_inspeccion_calidad' => 'Inspección de Calidad',
        'grupo_control_cambios' => 'Control de Cambios',
        'grupo_maquina_estados' => 'Máquina de Estados',
        'grupo_usuarios' => 'Usuarios y Accesos',
    ],

    'usuario' => [
        'singular' => 'Usuario',
        'plural' => 'Usuarios',
        'sin_rol' => 'Sin rol asignado',
        'campos' => [
            'name' => 'Nombre',
            'email' => 'Correo electrónico',
            'password' => 'Contraseña',
            'role' => 'Rol',
        ],
        'ayuda' => [
            'password_opcional' => 'Dejar en blanco para no cambiar la contraseña actual.',
        ],
    ],

    'proyecto' => [
        'singular' => 'Proyecto',
        'plural' => 'Proyectos',
        'campos' => [
            'nombre' => 'Nombre',
        ],
    ],

    'tablero' => [
        'singular' => 'Tablero',
        'plural' => 'Tableros',
        'campos' => [
            'proyecto' => 'Proyecto',
            'tag' => 'Tag',
            'nombre' => 'Nombre',
            'fabricante' => 'Fabricante',
            'oc_contrato' => 'OC / Contrato',
            'avance_global' => 'Avance global',
            'avance_calculado_at' => 'Avance calculado el',
        ],
        'secciones' => [
            'datos' => 'Datos del tablero',
            'avance' => 'Avance',
        ],
    ],

    'hito_legado' => [
        'singular' => 'Hito (legado)',
        'plural' => 'Hitos (legado)',
        'campos' => [
            'grupo_hito' => 'Grupo',
            'estado_avance' => 'Estado',
            'item' => 'Ítem',
            'nombre' => 'Nombre',
            'peso' => 'Peso',
            'plan_inicio' => 'Inicio planificado',
            'plan_fin' => 'Fin planificado',
            'real_inicio' => 'Inicio real',
            'real_fin' => 'Fin real',
            'responsable' => 'Responsable',
            'observaciones' => 'Observaciones',
        ],
    ],

    'actividad' => [
        'singular' => 'Actividad',
        'plural' => 'Actividades',
        'campos' => [
            'nombre' => 'Nombre',
            'descripcion' => 'Descripción',
            'orden' => 'Orden',
            'start_date' => 'Inicio planificado',
            'end_date' => 'Fin planificado',
            'cantidad_tareas' => 'Tareas',
            'avance' => 'Avance',
        ],
    ],

    'tarea' => [
        'singular' => 'Tarea',
        'plural' => 'Tareas',
        'campos' => [
            'actividad' => 'Actividad',
            'code' => 'Código',
            'nombre' => 'Nombre',
            'descripcion' => 'Descripción',
            'status' => 'Estado',
            'priority' => 'Prioridad',
            'peso' => 'Peso',
            'excluye_calculo' => 'Excluye del cálculo',
            'start_date' => 'Inicio planificado',
            'due_date' => 'Fin planificado',
            'real_inicio' => 'Inicio real',
            'real_fin' => 'Fin real',
        ],
        'status' => [
            'pendiente' => 'Pendiente',
            'en_progreso' => 'En Progreso',
            'en_revision' => 'En Revisión',
            'completada' => 'Completada',
            'bloqueada' => 'Bloqueada',
        ],
        'priority' => [
            'baja' => 'Baja',
            'media' => 'Media',
            'alta' => 'Alta',
            'critica' => 'Crítica',
        ],
        'kanban' => [
            'title' => 'Ver Kanban',
            'todas_las_actividades' => 'Todas las actividades',
            'todas_las_prioridades' => 'Todas las prioridades',
            'columna_vacia' => 'Sin tareas',
        ],
        'gantt' => [
            'title' => 'Ver Gantt',
            'sin_tareas' => 'Este tablero todavía no tiene tareas.',
            'escala_dia' => 'Día',
            'escala_semana' => 'Semana',
            'escala_mes' => 'Mes',
            'escala_anio' => 'Año',
            'ajustar' => 'Ajustar a la ventana',
            'actualizar' => 'Actualizar',
            'modal_editar' => 'Editar tarea',
            'modal_cancelar' => 'Cancelar',
            'modal_guardar' => 'Guardar',
            'columna_nombre' => 'Nombre',
            'columna_duracion' => 'Días',
            'columna_inicio' => 'Inicio',
            'columna_fin' => 'Fin',
            'link_solo_tareas' => 'Solo se pueden crear dependencias entre tareas, no sobre una actividad completa.',
        ],
    ],

    'visita_inspeccion' => [
        'singular' => 'Visita de Inspección',
        'plural' => 'Visitas de Inspección',
        'campos' => [
            'proyecto' => 'Proyecto',
            'inspector' => 'Inspector',
            'fecha' => 'Fecha',
            'tableros' => 'Tableros visitados',
            'observaciones_generales' => 'Observaciones generales',
            'estado_general' => 'Estado general',
        ],
        'estado_general' => [
            'sin_observaciones' => 'Sin Observaciones',
            'todo_cerrado' => 'Todo Cerrado',
            'con_pendientes' => 'Con Pendientes',
            'pendientes_criticos' => 'Pendientes Críticos',
        ],
    ],

    'observacion' => [
        'singular' => 'Observación',
        'plural' => 'Observaciones',
        'campos' => [
            'visita_inspeccion' => 'Visita',
            'tablero' => 'Tablero',
            'hito_legado' => 'Hito',
            'especialidad' => 'Especialidad',
            'tipo_observacion' => 'Tipo',
            'severidad' => 'Severidad',
            'descripcion' => 'Descripción',
            'responsable' => 'Responsable',
            'fecha_compromiso' => 'Fecha compromiso',
            'estado_observacion' => 'Estado',
            'fecha_cierre' => 'Fecha cierre',
            'observacion_cierre' => 'Observación de cierre',
            'dias_abierta' => 'Días abierta',
        ],
        'acciones' => [
            'cerrar' => 'Cerrar observación',
        ],
        'vencida' => 'Vencida',
    ],

    'control_cambio' => [
        'singular' => 'Control de Cambio',
        'plural' => 'Control de Cambios',
        'campos' => [
            'tablero' => 'Tablero',
            'estado_cambio' => 'Estado',
            'descripcion' => 'Descripción',
            'responsable' => 'Responsable',
            'fecha' => 'Fecha',
        ],
        'acciones' => [
            'aprobar' => 'Aprobar',
            'rechazar' => 'Rechazar',
            'implementar' => 'Marcar implementado',
            'desimplementar' => 'Revertir implementación',
        ],
    ],

    'prueba' => [
        'item_library' => [
            'singular' => 'Ítem de Prueba',
            'plural' => 'Ítems de Prueba',
        ],
        'template' => [
            'singular' => 'Plantilla de Prueba',
            'plural' => 'Plantillas de Prueba',
        ],
        'ejecucion' => [
            'singular' => 'Prueba',
            'plural' => 'Pruebas',
        ],
        'campos' => [
            'categoria' => 'Categoría',
            'item' => 'Ítem',
            'referencia_normativa' => 'Referencia normativa',
            'resultado' => 'Resultado',
            'observacion' => 'Observación',
        ],
    ],

    'catalogos' => [
        'grupo_hito_legado' => 'Grupos de Hito (legado)',
        'estado_avance' => 'Estados de Avance',
        'especialidad' => 'Especialidades',
        'tipo_observacion' => 'Tipos de Observación',
        'severidad' => 'Severidades',
        'estado_observacion' => 'Estados de Observación',
        'estado_cambio' => 'Estados de Cambio',
        'resultado_checklist' => 'Resultados de Checklist',
        'transicion_estado_permitida' => 'Transiciones de Estado Permitidas',
        'campos' => [
            'nombre' => 'Nombre',
            'codigo' => 'Código',
            'orden' => 'Orden',
            'activo' => 'Activo',
            'valor' => 'Valor',
            'excluye_calculo' => 'Excluye del cálculo',
            'requiere_severidad' => 'Requiere severidad',
            'es_terminal' => 'Es terminal',
        ],
    ],

    'errores' => [
        'transicion_no_permitida' => 'La transición de :origen a :destino no está permitida.',
        'severidad_requerida' => 'Este tipo de observación requiere severidad.',
    ],
];
