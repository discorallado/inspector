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
}
