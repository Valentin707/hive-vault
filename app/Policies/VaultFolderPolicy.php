<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VaultFolder;

class VaultFolderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, VaultFolder $vaultFolder): bool
    {
        return $this->ownsFolder($user, $vaultFolder);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, VaultFolder $vaultFolder): bool
    {
        return $vaultFolder->user_id === $user->id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, VaultFolder $vaultFolder): bool
    {
        return $this->ownsFolder($user, $vaultFolder);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, VaultFolder $vaultFolder): bool
    {
        return $this->ownsFolder($user, $vaultFolder);
    }

    private function ownsFolder(User $user, VaultFolder $vaultFolder): bool
    {
        return $vaultFolder->getRawOriginal('user_id') === $user->id
            && $vaultFolder->user_id === $user->id;
    }
}
