<?php

namespace Modules\Inspeccion\Policies;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Parallax\FilamentComments\Models\FilamentComment;

/**
 * Policy custom para parallax/filament-comments (ver config/filament-comments.php
 * model_policy) — reemplaza la del paquete porque ahí delete() es "solo el
 * dueño", y acá además super_admin puede moderar comentarios ajenos (mismo
 * criterio que auditoria.purgar en el resto del módulo).
 *
 * TODO: reemplazar por policy real al integrar a axon.
 */
class ComentarioPolicy
{
    public function viewAny(User $user): bool
    {
        return Gate::allows('tablero.ver');
    }

    public function view(User $user, FilamentComment $comentario): bool
    {
        return Gate::allows('tablero.ver');
    }

    public function create(User $user): bool
    {
        return Gate::allows('tablero.ver');
    }

    public function update(User $user, FilamentComment $comentario): bool
    {
        return false;
    }

    public function delete(User $user, FilamentComment $comentario): bool
    {
        return $user->id === $comentario->user_id || Gate::allows('auditoria.purgar');
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, FilamentComment $comentario): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, FilamentComment $comentario): bool
    {
        return Gate::allows('auditoria.purgar');
    }

    public function forceDeleteAny(User $user): bool
    {
        return Gate::allows('auditoria.purgar');
    }
}
