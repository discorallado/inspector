<?php

namespace Modules\Inspeccion\Policies;

use App\Models\User;
use Illuminate\Support\Facades\Gate;

// TODO: reemplazar por policy real al integrar a axon.
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return Gate::allows('usuario.gestionar');
    }

    public function view(User $user, User $modelo): bool
    {
        return Gate::allows('usuario.gestionar');
    }

    public function create(User $user): bool
    {
        return Gate::allows('usuario.gestionar');
    }

    public function update(User $user, User $modelo): bool
    {
        return Gate::allows('usuario.gestionar');
    }

    public function delete(User $user, User $modelo): bool
    {
        return Gate::allows('usuario.gestionar');
    }

    public function deleteAny(User $user): bool
    {
        return Gate::allows('usuario.gestionar');
    }
}
