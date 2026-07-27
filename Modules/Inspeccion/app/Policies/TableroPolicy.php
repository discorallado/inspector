<?php

namespace Modules\Inspeccion\Policies;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Modules\Inspeccion\Models\Tablero;

// TODO: reemplazar por policy real al integrar a axon.
class TableroPolicy
{
    public function viewAny(User $user): bool
    {
        return Gate::allows('tablero.ver');
    }

    public function view(User $user, Tablero $tablero): bool
    {
        return Gate::allows('tablero.ver');
    }

    public function create(User $user): bool
    {
        return Gate::allows('tablero.gestionar');
    }

    public function update(User $user, Tablero $tablero): bool
    {
        return Gate::allows('tablero.gestionar');
    }

    public function delete(User $user, Tablero $tablero): bool
    {
        return Gate::allows('tablero.gestionar');
    }
}
