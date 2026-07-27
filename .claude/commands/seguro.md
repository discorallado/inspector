---
description: Modo seguro — avisa antes de comandos destructivos y limita ediciones
---

Activa **modo seguro** para el resto de la sesión. Es prevención de accidentes,
no control de acceso: puedo anular cualquier advertencia explícitamente.

Antes de ejecutar CUALQUIER comando de bash, verifica si coincide con un patrón
peligroso y, si coincide, DETENTE y pídeme confirmación explícita antes de
correrlo:

- `rm -rf` / `rm -r` — borrado recursivo
- `php artisan migrate:fresh` / `migrate:reset` / `db:wipe` — pérdida de datos
- `DROP TABLE` / `DROP DATABASE` / `TRUNCATE` — pérdida de datos
- `git push --force` / `git push -f` — reescritura de historia
- `git reset --hard` — descarta commits
- `git checkout .` / `git restore .` — descarta cambios sin commitear
- comandos que apunten a bases de datos o entornos de PRODUCCIÓN

Limpiezas de artefactos de build NO requieren confirmación (no son peligrosas):
`rm -rf vendor`, `node_modules`, `dist`, `public/build`, `bootstrap/cache/*`,
`storage/framework/cache/*`.

Adicionalmente, si te paso una ruta como argumento, restringe TODAS las
ediciones (Edit/Write) a ese directorio y bloquea cambios fuera de él, avisándome
cuando un cambio quede bloqueado. Para liberar la restricción, te pediré
explícitamente quitar el modo seguro.

Argumento opcional (directorio al que limitar las ediciones): $ARGUMENTS
