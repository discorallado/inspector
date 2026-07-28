<?php

namespace Modules\Inspeccion\Policies;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Modules\Inspeccion\Models\Observacion;

// TODO: reemplazar por policy real al integrar a axon.
class ObservacionPolicy
{
    public function viewAny(User $user): bool
    {
        return Gate::allows('tablero.ver');
    }

    public function view(User $user, Observacion $observacion): bool
    {
        return Gate::allows('tablero.ver');
    }

    public function create(User $user): bool
    {
        return Gate::allows('observacion.crear');
    }

    public function update(User $user, Observacion $observacion): bool
    {
        return Gate::allows('observacion.cerrar');
    }

    public function delete(User $user, Observacion $observacion): bool
    {
        return Gate::allows('observacion.crear');
    }

    public function restore(User $user, Observacion $observacion): bool
    {
        return Gate::allows('observacion.crear');
    }

    public function forceDelete(User $user, Observacion $observacion): bool
    {
        return Gate::allows('auditoria.purgar');
    }

    /**
     * Filament autoriza las Bulk*Action contra estas abilities *Any(), no
     * contra sus pares en singular. Sin ellas, Filament falla abierto
     * (permite a cualquier usuario autenticado) porque el método no existe.
     */
    public function deleteAny(User $user): bool
    {
        return Gate::allows('observacion.crear');
    }

    public function restoreAny(User $user): bool
    {
        return Gate::allows('observacion.crear');
    }

    public function forceDeleteAny(User $user): bool
    {
        return Gate::allows('auditoria.purgar');
    }
}
