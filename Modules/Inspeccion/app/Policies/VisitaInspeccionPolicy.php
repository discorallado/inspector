<?php

namespace Modules\Inspeccion\Policies;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Modules\Inspeccion\Models\VisitaInspeccion;

// TODO: reemplazar por policy real al integrar a axon.
class VisitaInspeccionPolicy
{
    public function viewAny(User $user): bool
    {
        return Gate::allows('tablero.ver');
    }

    public function view(User $user, VisitaInspeccion $visita): bool
    {
        return Gate::allows('tablero.ver');
    }

    public function create(User $user): bool
    {
        return Gate::allows('visita_inspeccion.gestionar');
    }

    public function update(User $user, VisitaInspeccion $visita): bool
    {
        return Gate::allows('visita_inspeccion.gestionar');
    }

    public function delete(User $user, VisitaInspeccion $visita): bool
    {
        return Gate::allows('visita_inspeccion.gestionar');
    }
}
