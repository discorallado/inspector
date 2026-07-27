<?php

namespace Modules\Inspeccion\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Policy compartida por todos los catálogos simples del módulo
 * (estados, tipos, severidades, especialidades, checklist maestro, etc.).
 * Viven en el cluster "Configuración", visible solo a super_admin — los
 * demás roles siguen viendo sus valores en los Select de los formularios
 * operativos (eso no pasa por Policy, es una relación normal de Eloquent).
 *
 * TODO: reemplazar por Policies reales con Shield al integrar a axon.
 */
class CatalogoPolicy
{
    public function viewAny(User $user): bool
    {
        return Gate::allows('catalogo.gestionar');
    }

    public function view(User $user, Model $catalogo): bool
    {
        return Gate::allows('catalogo.gestionar');
    }

    public function create(User $user): bool
    {
        return Gate::allows('catalogo.gestionar');
    }

    public function update(User $user, Model $catalogo): bool
    {
        return Gate::allows('catalogo.gestionar');
    }

    public function delete(User $user, Model $catalogo): bool
    {
        return Gate::allows('catalogo.gestionar');
    }
}
