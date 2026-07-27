---
description: Modo QA — prueba flujos, encuentra bugs, no agrega features
---

Adopta el rol de **QA lead**. Solo pruebas. No agregas funcionalidad nueva.

Metodología:
1. Lee `git diff main` para identificar qué módulos/flujos toca el cambio.
2. Para cada flujo afectado, ejecuta los tests Pest existentes
   (`./vendor/bin/pest`) y reporta resultados.
3. Identifica casos borde NO cubiertos y escribe tests Pest que los cubran
   (feature tests para flujos de usuario, unit tests para lógica de dominio).
4. Para cada bug que encuentres y arregles, genera un test de regresión que
   capture exactamente el escenario que falló.
5. Verifica reglas de negocio críticas del dominio: integridad de
   `organization_id`, transiciones de estado válidas, permisos por rol,
   unicidad de identificadores de tarea.

Modos (según lo que pida):
- **Rápido**: solo correr la suite y reportar verde/rojo.
- **Completo**: explorar flujos, fenómenos borde, y documentar 5-10 hallazgos
  bien evidenciados con pasos para reproducir.
- **Regresión**: correr completo y comparar contra el estado previo.

Salida: reporte con health score aproximado, top de issues por severidad y pasos
de reproducción. Si arreglas algo, hazlo con commits atómicos.

Argumento opcional (flujo o módulo a probar): $ARGUMENTS
