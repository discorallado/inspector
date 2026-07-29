<?php

namespace Modules\Inspeccion\Policies;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Modules\Inspeccion\Models\Actividad;

// TODO: reemplazar por policy real al integrar a axon.
class ActividadPolicy
{
    public function viewAny(User $user): bool
    {
        return Gate::allows('tablero.ver');
    }

    public function view(User $user, Actividad $actividad): bool
    {
        return Gate::allows('tablero.ver');
    }

    public function create(User $user): bool
    {
        return Gate::allows('tablero_actividad.gestionar');
    }

    public function update(User $user, Actividad $actividad): bool
    {
        return Gate::allows('tablero_actividad.gestionar');
    }

    public function delete(User $user, Actividad $actividad): bool
    {
        return Gate::allows('tablero_actividad.gestionar');
    }

    public function restore(User $user, Actividad $actividad): bool
    {
        return Gate::allows('tablero_actividad.gestionar');
    }

    public function forceDelete(User $user, Actividad $actividad): bool
    {
        return Gate::allows('auditoria.purgar');
    }

    public function deleteAny(User $user): bool
    {
        return Gate::allows('tablero_actividad.gestionar');
    }

    public function restoreAny(User $user): bool
    {
        return Gate::allows('tablero_actividad.gestionar');
    }

    public function forceDeleteAny(User $user): bool
    {
        return Gate::allows('auditoria.purgar');
    }
}
