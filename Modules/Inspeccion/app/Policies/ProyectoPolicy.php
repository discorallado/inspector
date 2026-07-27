<?php

namespace Modules\Inspeccion\Policies;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Modules\Inspeccion\Models\Proyecto;

// TODO: reemplazar por policy real al integrar a axon (Proyecto deja de ser un stub).
class ProyectoPolicy
{
    public function viewAny(User $user): bool
    {
        return Gate::allows('tablero.ver');
    }

    public function view(User $user, Proyecto $proyecto): bool
    {
        return Gate::allows('tablero.ver');
    }

    public function create(User $user): bool
    {
        return Gate::allows('tablero.gestionar');
    }

    public function update(User $user, Proyecto $proyecto): bool
    {
        return Gate::allows('tablero.gestionar');
    }

    public function delete(User $user, Proyecto $proyecto): bool
    {
        return Gate::allows('tablero.gestionar');
    }
}
