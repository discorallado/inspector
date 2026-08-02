<?php

namespace Modules\Inspeccion\Policies;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Modules\Inspeccion\Models\HitoLegado;

// TODO: reemplazar por policy real al integrar a axon.
class HitoLegadoPolicy
{
    public function viewAny(User $user): bool
    {
        return Gate::allows('tablero.ver');
    }

    public function view(User $user, HitoLegado $hito): bool
    {
        return Gate::allows('tablero.ver');
    }

    public function create(User $user): bool
    {
        return Gate::allows('tablero.gestionar');
    }

    public function update(User $user, HitoLegado $hito): bool
    {
        return Gate::allows('hito_legado.actualizar');
    }

    public function delete(User $user, HitoLegado $hito): bool
    {
        return Gate::allows('tablero.gestionar');
    }

    public function deleteAny(User $user): bool
    {
        return Gate::allows('tablero.gestionar');
    }
}
