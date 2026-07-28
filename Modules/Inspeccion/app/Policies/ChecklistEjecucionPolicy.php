<?php

namespace Modules\Inspeccion\Policies;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Modules\Inspeccion\Models\ChecklistEjecucion;

// TODO: reemplazar por policy real al integrar a axon.
class ChecklistEjecucionPolicy
{
    public function viewAny(User $user): bool
    {
        return Gate::allows('tablero.ver');
    }

    public function view(User $user, ChecklistEjecucion $ejecucion): bool
    {
        return Gate::allows('tablero.ver');
    }

    public function create(User $user): bool
    {
        return Gate::allows('checklist_ejecucion.completar');
    }

    public function update(User $user, ChecklistEjecucion $ejecucion): bool
    {
        return Gate::allows('checklist_ejecucion.completar');
    }

    public function delete(User $user, ChecklistEjecucion $ejecucion): bool
    {
        return Gate::allows('checklist_ejecucion.completar');
    }

    public function restore(User $user, ChecklistEjecucion $ejecucion): bool
    {
        return Gate::allows('checklist_ejecucion.completar');
    }

    public function forceDelete(User $user, ChecklistEjecucion $ejecucion): bool
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
        return Gate::allows('checklist_ejecucion.completar');
    }

    public function restoreAny(User $user): bool
    {
        return Gate::allows('checklist_ejecucion.completar');
    }

    public function forceDeleteAny(User $user): bool
    {
        return Gate::allows('auditoria.purgar');
    }
}
