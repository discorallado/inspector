<?php

namespace Modules\Inspeccion\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inspeccion\Models\PruebaItemLibrary;
use Modules\Inspeccion\Models\PruebaTemplate;

/**
 * Contenido IEC 61439 heredado del checklist de inspección descartado
 * (ver ADR de rename) — queda tal cual hasta que se arme el catálogo de
 * ítems de prueba real, no es parte de este cambio.
 */
class PruebaItemLibrarySeeder extends Seeder
{
    public function run(): void
    {
        $items = collect([
            ['categoria' => 'Estructura', 'item' => 'Grado de protección IP declarado coincide con el especificado', 'referencia_normativa' => 'IEC 61439-1 §8.4'],
            ['categoria' => 'Estructura', 'item' => 'Resistencia mecánica de la envolvente sin daños visibles', 'referencia_normativa' => 'IEC 61439-1 §10.2.6'],
            ['categoria' => 'Cableado', 'item' => 'Identificación de conductores según plano unifilar', 'referencia_normativa' => 'IEC 61439-1 §8.6'],
            ['categoria' => 'Cableado', 'item' => 'Distancias de aislación y fugas respetadas', 'referencia_normativa' => 'IEC 61439-1 §8.3'],
            ['categoria' => 'Protecciones', 'item' => 'Coordinación de protecciones (selectividad) según diseño', 'referencia_normativa' => 'IEC 61439-1 §8.5'],
            ['categoria' => 'Protecciones', 'item' => 'Puesta a tierra de estructura y componentes verificada', 'referencia_normativa' => 'IEC 61439-1 §8.4.3'],
            ['categoria' => 'Documentación', 'item' => 'Planos as-built entregados y coinciden con lo fabricado', 'referencia_normativa' => 'IEC 61439-1 §5'],
            ['categoria' => 'Documentación', 'item' => 'Certificados de ensayos de rutina (routine tests) disponibles', 'referencia_normativa' => 'IEC 61439-1 §11'],
        ])->map(fn (array $datos, int $indice) => PruebaItemLibrary::query()->firstOrCreate(
            ['item' => $datos['item']],
            [...$datos, 'orden' => $indice + 1, 'activo' => true],
        ));

        $template = PruebaTemplate::query()->firstOrCreate(['nombre' => 'Checklist Estándar IEC 61439']);

        $items->each(fn (PruebaItemLibrary $item, int $indice) => $template->items()->syncWithoutDetaching([
            $item->id => ['orden' => $indice + 1],
        ]));
    }
}
