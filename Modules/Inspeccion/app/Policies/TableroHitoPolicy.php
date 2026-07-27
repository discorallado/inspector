<?php

namespace Modules\Inspeccion\Policies;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Modules\Inspeccion\Models\TableroHito;

// TODO: reemplazar por policy real al integrar a axon.
class TableroHitoPolicy
{
    public function viewAny(User $user): bool
    {
        return Gate::allows('tablero.ver');
    }

    public function view(User $user, TableroHito $hito): bool
    {
        return Gate::allows('tablero.ver');
    }

    public function create(User $user): bool
    {
        return Gate::allows('tablero.gestionar');
    }

    public function update(User $user, TableroHito $hito): bool
    {
        return Gate::allows('tablero_hito.actualizar');
    }

    public function delete(User $user, TableroHito $hito): bool
    {
        return Gate::allows('tablero.gestionar');
    }
}
