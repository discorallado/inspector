<?php

namespace Modules\Inspeccion\Policies;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Modules\Inspeccion\Models\ChecklistEjecucionItem;

// TODO: reemplazar por policy real al integrar a axon.
class ChecklistEjecucionItemPolicy
{
    public function viewAny(User $user): bool
    {
        return Gate::allows('tablero.ver');
    }

    public function view(User $user, ChecklistEjecucionItem $item): bool
    {
        return Gate::allows('tablero.ver');
    }

    public function create(User $user): bool
    {
        return Gate::allows('checklist_ejecucion.completar');
    }

    public function update(User $user, ChecklistEjecucionItem $item): bool
    {
        return Gate::allows('checklist_ejecucion.completar');
    }

    public function delete(User $user, ChecklistEjecucionItem $item): bool
    {
        return Gate::allows('checklist_ejecucion.completar');
    }
}
