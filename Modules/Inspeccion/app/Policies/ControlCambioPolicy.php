<?php

namespace Modules\Inspeccion\Policies;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Modules\Inspeccion\Models\ControlCambio;

// TODO: reemplazar por policy real al integrar a axon.
class ControlCambioPolicy
{
    public function viewAny(User $user): bool
    {
        return Gate::allows('tablero.ver');
    }

    public function view(User $user, ControlCambio $controlCambio): bool
    {
        return Gate::allows('tablero.ver');
    }

    public function create(User $user): bool
    {
        return Gate::allows('control_cambio.proponer');
    }

    public function update(User $user, ControlCambio $controlCambio): bool
    {
        return Gate::any(['control_cambio.proponer', 'control_cambio.decidir', 'control_cambio.implementar']);
    }

    public function delete(User $user, ControlCambio $controlCambio): bool
    {
        return Gate::allows('control_cambio.proponer');
    }

    public function restore(User $user, ControlCambio $controlCambio): bool
    {
        return Gate::allows('control_cambio.proponer');
    }

    public function forceDelete(User $user, ControlCambio $controlCambio): bool
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
        return Gate::allows('control_cambio.proponer');
    }

    public function restoreAny(User $user): bool
    {
        return Gate::allows('control_cambio.proponer');
    }

    public function forceDeleteAny(User $user): bool
    {
        return Gate::allows('auditoria.purgar');
    }
}
