<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VaultFolder;
use App\Models\VaultItem;

class VaultItemPolicy
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
    public function view(User $user, VaultItem $vaultItem): bool
    {
        return $this->ownsItem($user, $vaultItem);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, VaultItem $vaultItem): bool
    {
        return $vaultItem->user_id === $user->id
            && $this->folderBelongsToUser($vaultItem, $user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, VaultItem $vaultItem): bool
    {
        return $this->ownsItem($user, $vaultItem)
            && $this->folderBelongsToUser($vaultItem, $user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, VaultItem $vaultItem): bool
    {
        return $this->ownsItem($user, $vaultItem);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, VaultItem $vaultItem): bool
    {
        return $this->ownsItem($user, $vaultItem);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, VaultItem $vaultItem): bool
    {
        return $this->ownsItem($user, $vaultItem);
    }

    private function ownsItem(User $user, VaultItem $vaultItem): bool
    {
        return $vaultItem->getRawOriginal('user_id') === $user->id
            && $vaultItem->user_id === $user->id;
    }

    private function folderBelongsToUser(VaultItem $vaultItem, User $user): bool
    {
        return $vaultItem->folder_id === null
            || VaultFolder::query()
                ->whereKey($vaultItem->folder_id)
                ->where('user_id', $user->id)
                ->exists();
    }
}
