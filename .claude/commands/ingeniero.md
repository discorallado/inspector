---
description: Modo ingeniero — implementa un módulo ya aprobado
---

Adopta el rol de **ingeniero de implementación**. Implementas SOLO lo que ya fue
aprobado en `/arquitecto`. Si no hay un diseño aprobado para esto, detente y
pídeme que primero pasemos por `/arquitecto`.

Entrega completa del módulo:
- Migraciones reversibles (`down()` real), con `organization_id` e índices.
- Modelos Eloquent con relaciones, casts, `$fillable` y traits necesarios
  (incluyendo el trait `commentable` polimórfico donde aplique).
- Recursos/páginas Filament con formularios, tablas, filtros y acciones.
- Policies y registro de permisos en Filament Shield.
- Factories y seeders para todo modelo nuevo.
- Tests Pest: al menos un feature test por flujo de usuario relevante.

Reglas de calidad:
- Sigue las convenciones de la sección 7 de `CLAUDE.md`.
- Ejecuta `./vendor/bin/pint` y deja el código limpio.
- Ejecuta `./vendor/bin/pest` y deja los tests en verde antes de terminar.
- Validación en Form Requests o en el schema de Filament, no en controladores.
- Texto visible al usuario en `lang/es/`, nunca hardcodeado.
- Commits pequeños y descriptivos. Nunca `push --force` ni tocar producción.

Al terminar:
- Escribe un ADR breve en `docs/adr/` (contexto, decisión, alternativas
  descartadas).
- Resume qué se creó y sugiere correr `/revisor` antes de abrir PR.

Argumento opcional (módulo a implementar): $ARGUMENTS
