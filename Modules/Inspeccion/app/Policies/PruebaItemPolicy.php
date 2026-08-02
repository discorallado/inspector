<?php

namespace Modules\Inspeccion\Policies;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Modules\Inspeccion\Models\PruebaItem;

// TODO: reemplazar por policy real al integrar a axon.
class PruebaItemPolicy
{
    public function viewAny(User $user): bool
    {
        return Gate::allows('tablero.ver');
    }

    public function view(User $user, PruebaItem $item): bool
    {
        return Gate::allows('tablero.ver');
    }

    public function create(User $user): bool
    {
        return Gate::allows('prueba.completar');
    }

    public function update(User $user, PruebaItem $item): bool
    {
        return Gate::allows('prueba.completar');
    }

    public function delete(User $user, PruebaItem $item): bool
    {
        return Gate::allows('prueba.completar');
    }

    public function deleteAny(User $user): bool
    {
        return Gate::allows('prueba.completar');
    }
}
