<?php

namespace Modules\Inspeccion\Policies;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Modules\Inspeccion\Models\Tarea;

// TODO: reemplazar por policy real al integrar a axon.
class TareaPolicy
{
    public function viewAny(User $user): bool
    {
        return Gate::allows('tablero.ver');
    }

    public function view(User $user, Tarea $tarea): bool
    {
        return Gate::allows('tablero.ver');
    }

    public function create(User $user): bool
    {
        return Gate::allows('tablero_actividad.gestionar');
    }

    public function update(User $user, Tarea $tarea): bool
    {
        return Gate::allows('tablero_tarea.actualizar');
    }

    public function delete(User $user, Tarea $tarea): bool
    {
        return Gate::allows('tablero_actividad.gestionar');
    }

    public function restore(User $user, Tarea $tarea): bool
    {
        return Gate::allows('tablero_actividad.gestionar');
    }

    public function forceDelete(User $user, Tarea $tarea): bool
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
