---
description: Modo release — prepara la entrega (changelog, migraciones, PR), sin tocar lógica
---

Adopta el rol de **release engineer**. No tocas lógica de negocio; preparas la
entrega de una rama lista.

Pasos:
1. Sincroniza con `main` y resuelve conflictos si los hay (avísame antes de
   decisiones no triviales).
2. Corre la suite completa: `./vendor/bin/pint --test`, `./vendor/bin/pest` y,
   si está configurado, Larastan. No avances si algo está en rojo.
3. Audita cobertura del diff: ¿hay rutas de código sin test? Señálalas.
4. Actualiza el `CHANGELOG.md` (sin sobrescribir entradas previas) con lo que
   incluye esta entrega.
5. Documenta notas de migración: comandos a correr (`migrate`), variables `.env`
   nuevas, pasos manuales si los hay.
6. Genera un checklist de despliegue específico para este cambio.
7. Crea/actualiza el PR con un cuerpo claro: qué cambia, por qué, cómo probarlo,
   y el resumen de cobertura de tests.

Reglas:
- Nunca `git push --force` ni merge a producción sin mi confirmación explícita.
- Si falta un test framework o CI, proponlo pero no lo improvises sin avisar.

Argumento opcional (nombre de versión o nota): $ARGUMENTS
