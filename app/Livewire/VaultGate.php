<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\VaultAuditLog;
use App\Models\VaultFolder;
use App\Models\VaultItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use JsonException;
use Livewire\Component;

class VaultGate extends Component
{
    public function initializeVault(string $salt, string $verifier): void
    {
        $userId = Auth::id();

        abort_unless(is_string($userId), 403);

        $this->validateVaultMaterial($salt, $verifier);

        DB::transaction(function () use ($userId, $salt, $verifier): void {
            $user = User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();

            if ($user->vault_salt !== null || $user->vault_verifier !== null) {
                abort_unless(
                    is_string($user->vault_salt)
                        && is_string($user->vault_verifier)
                        && hash_equals($user->vault_salt, $salt)
                        && hash_equals($user->vault_verifier, $verifier),
                    409,
                );

                return;
            }

            $user->forceFill([
                'vault_salt' => $salt,
                'vault_verifier' => $verifier,
            ])->save();

            $this->writeAuditLog($user, VaultAuditLog::VAULT_INITIALIZED);
        });
    }

    public function recordVaultUnlocked(): void
    {
        $this->writeAuditLog($this->initializedVaultUser(), VaultAuditLog::VAULT_UNLOCKED);
    }

    public function recordVaultLocked(): void
    {
        $this->writeAuditLog($this->initializedVaultUser(), VaultAuditLog::VAULT_LOCKED);
    }

    /**
     * Return only ciphertext and structural vault metadata for the authenticated user.
     *
     * @return array{items: list<array<string, mixed>>, folders: list<array<string, mixed>>}
     */
    public function loadWorkspace(): array
    {
        $user = $this->initializedVaultUser();

        Gate::authorize('viewAny', VaultItem::class);
        Gate::authorize('viewAny', VaultFolder::class);

        $items = $user->vaultItems()
            ->withTrashed()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $folders = $user->vaultFolders()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return [
            'items' => $items->map(fn (VaultItem $item): array => $this->serializeItem($item))->all(),
            'folders' => $folders->map(fn (VaultFolder $folder): array => $this->serializeFolder($folder))->all(),
        ];
    }

    /**
     * Save client-encrypted item data without receiving any decrypted title or payload.
     *
     * @return array<string, mixed>
     */
    public function saveItem(
        ?string $itemId,
        string $type,
        ?string $folderId,
        mixed $isFavorite,
        string $encryptedTitle,
        string $titleIv,
        string $encryptedPayload,
        string $payloadIv,
    ): array {
        $user = $this->initializedVaultUser();

        $validated = Validator::make([
            'itemId' => $itemId,
            'type' => $type,
            'folderId' => $folderId,
            'isFavorite' => $isFavorite,
        ], [
            'itemId' => ['nullable', 'uuid'],
            'type' => ['required', Rule::in(['login', 'ssh', 'database', 'note'])],
            'folderId' => ['nullable', 'uuid'],
            'isFavorite' => ['required', 'boolean'],
        ])->validate();

        $isFavorite = (bool) $validated['isFavorite'];

        $this->validateEncryptedValue($encryptedTitle, $titleIv, 'encryptedTitle', 8192);
        $this->validateEncryptedValue($encryptedPayload, $payloadIv, 'encryptedPayload', 5_592_408);

        $item = $itemId === null
            ? new VaultItem(['user_id' => $user->id])
            : $user->vaultItems()->whereKey($itemId)->firstOrFail();

        $ability = $item->exists ? 'update' : 'create';

        $item->fill([
            'user_id' => $user->id,
            'folder_id' => $folderId,
            'type' => $type,
            'encrypted_title' => $encryptedTitle,
            'title_iv' => $titleIv,
            'encrypted_payload' => $encryptedPayload,
            'payload_iv' => $payloadIv,
            'is_favorite' => $isFavorite,
        ]);

        Gate::authorize($ability, $item);

        if ($ability === 'update' && $item->getRawOriginal('type') !== $type) {
            throw ValidationException::withMessages([
                'type' => 'An item type cannot be changed after it has been created.',
            ]);
        }

        DB::transaction(function () use ($item, $user, $ability): void {
            $item->save();
            $this->writeItemAuditLog(
                $user,
                $item,
                $ability === 'create' ? VaultAuditLog::ITEM_CREATED : VaultAuditLog::ITEM_UPDATED,
            );
        });

        return $this->serializeItem($item);
    }

    /**
     * Move an owned item to the trash.
     *
     * @return array<string, mixed>
     */
    public function deleteItem(string $itemId): array
    {
        $this->validateUuid($itemId, 'itemId');

        $user = $this->initializedVaultUser();
        $item = $user->vaultItems()->whereKey($itemId)->firstOrFail();

        Gate::authorize('delete', $item);

        DB::transaction(function () use ($user, $item): void {
            $item->delete();
            $this->writeItemAuditLog($user, $item, VaultAuditLog::ITEM_DELETED);
        });

        return $this->serializeItem($item);
    }

    /**
     * Restore an owned item from the trash.
     *
     * @return array<string, mixed>
     */
    public function restoreItem(string $itemId): array
    {
        $this->validateUuid($itemId, 'itemId');

        $user = $this->initializedVaultUser();
        $item = $user->vaultItems()->withTrashed()->whereKey($itemId)->firstOrFail();

        Gate::authorize('restore', $item);
        abort_unless($item->trashed(), 409);

        DB::transaction(function () use ($user, $item): void {
            $item->restore();
            $this->writeItemAuditLog($user, $item, VaultAuditLog::ITEM_UPDATED);
        });

        return $this->serializeItem($item);
    }

    /**
     * Permanently delete an owned item already in the trash.
     */
    public function forceDeleteItem(string $itemId): void
    {
        $this->validateUuid($itemId, 'itemId');

        $user = $this->initializedVaultUser();
        $item = $user->vaultItems()->withTrashed()->whereKey($itemId)->firstOrFail();

        Gate::authorize('forceDelete', $item);
        abort_unless($item->trashed(), 409);

        DB::transaction(function () use ($user, $item): void {
            $this->writeItemAuditLog($user, $item, VaultAuditLog::ITEM_DELETED);
            $item->forceDelete();
        });
    }

    /**
     * Toggle favorite status for an owned vault item.
     *
     * @return array<string, mixed>
     */
    public function toggleFavorite(string $itemId): array
    {
        $this->validateUuid($itemId, 'itemId');

        $user = $this->initializedVaultUser();
        $item = $user->vaultItems()->withTrashed()->whereKey($itemId)->firstOrFail();

        Gate::authorize('update', $item);

        DB::transaction(function () use ($user, $item): void {
            $item->is_favorite = ! $item->is_favorite;
            $item->save();
            $this->writeItemAuditLog($user, $item, VaultAuditLog::ITEM_UPDATED);
        });

        return $this->serializeItem($item);
    }

    /**
     * Record a view or copy action and update last access time for a viewed item.
     *
     * @return array<string, mixed>
     */
    public function logAccess(string $itemId, string $action): array
    {
        $this->validateUuid($itemId, 'itemId');

        Validator::make(['action' => $action], [
            'action' => ['required', Rule::in([VaultAuditLog::ITEM_VIEWED, VaultAuditLog::ITEM_COPIED])],
        ])->validate();

        $user = $this->initializedVaultUser();
        $item = $user->vaultItems()->withTrashed()->whereKey($itemId)->firstOrFail();

        Gate::authorize('view', $item);

        DB::transaction(function () use ($user, $item, $action): void {
            if ($action === VaultAuditLog::ITEM_VIEWED) {
                $item->last_accessed_at = now();
                $item->save();
            }

            $this->writeItemAuditLog($user, $item, $action);
        });

        return $this->serializeItem($item);
    }

    /**
     * Create or rename an encrypted folder.
     *
     * @return array<string, mixed>
     */
    public function saveFolder(?string $folderId, string $encryptedName, string $iv): array
    {
        $user = $this->initializedVaultUser();

        Validator::make(['folderId' => $folderId], [
            'folderId' => ['nullable', 'uuid'],
        ])->validate();

        $this->validateEncryptedValue($encryptedName, $iv, 'encryptedName', 8192);

        $folder = $folderId === null
            ? new VaultFolder([
                'user_id' => $user->id,
                'sort_order' => $user->vaultFolders()->count(),
            ])
            : $user->vaultFolders()->whereKey($folderId)->firstOrFail();

        $ability = $folder->exists ? 'update' : 'create';

        $folder->fill([
            'user_id' => $user->id,
            'encrypted_name' => $encryptedName,
            'iv' => $iv,
        ]);

        Gate::authorize($ability, $folder);

        $folder->save();

        return $this->serializeFolder($folder);
    }

    /**
     * Delete an owned folder and detach its owned items.
     */
    public function deleteFolder(string $folderId): void
    {
        $this->validateUuid($folderId, 'folderId');

        $user = $this->initializedVaultUser();
        $folder = $user->vaultFolders()->whereKey($folderId)->firstOrFail();

        Gate::authorize('delete', $folder);

        DB::transaction(function () use ($folder): void {
            $folder->items()->update(['folder_id' => null]);
            $folder->delete();
        });
    }

    public function render(): View
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return view('livewire.vault-gate', [
            'vaultUserId' => $user->id,
            'vaultSalt' => $user->vault_salt,
            'vaultVerifier' => $user->vault_verifier,
            'autoLockMinutes' => $user->vault_auto_lock_minutes ?? 15,
        ]);
    }

    private function validateVaultMaterial(string $salt, string $verifier): void
    {
        $decodedSalt = base64_decode($salt, true);

        if (! is_string($decodedSalt) || strlen($decodedSalt) !== 32) {
            throw ValidationException::withMessages([
                'vault' => 'The vault salt must encode exactly 32 random bytes.',
            ]);
        }

        if (strlen($verifier) > 2048) {
            throw ValidationException::withMessages([
                'vault' => 'The encrypted vault verifier is invalid.',
            ]);
        }

        try {
            $verifierData = json_decode($verifier, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages([
                'vault' => 'The encrypted vault verifier is invalid.',
            ]);
        }

        if (! is_array($verifierData)
            || count($verifierData) !== 2
            || ! is_string($verifierData['ciphertext'] ?? null)
            || ! is_string($verifierData['iv'] ?? null)
        ) {
            throw ValidationException::withMessages([
                'vault' => 'The encrypted vault verifier is invalid.',
            ]);
        }

        $ciphertext = base64_decode($verifierData['ciphertext'], true);
        $iv = base64_decode($verifierData['iv'], true);

        if (! is_string($ciphertext)
            || strlen($ciphertext) < 16
            || ! is_string($iv)
            || strlen($iv) !== 12
        ) {
            throw ValidationException::withMessages([
                'vault' => 'The encrypted vault verifier is invalid.',
            ]);
        }
    }

    private function initializedVaultUser(): User
    {
        $user = Auth::user();

        abort_unless(
            $user instanceof User
                && $user->vault_salt !== null
                && $user->vault_verifier !== null,
            403,
        );

        return $user;
    }

    private function writeAuditLog(User $user, string $action): void
    {
        $request = request();

        $user->vaultAuditLogs()->create([
            'action' => $action,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    private function writeItemAuditLog(User $user, VaultItem $item, string $action): void
    {
        $request = request();

        $user->vaultAuditLogs()->create([
            'vault_item_id' => $item->id,
            'action' => $action,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    private function validateUuid(string $value, string $field): void
    {
        Validator::make([$field => $value], [
            $field => ['required', 'uuid'],
        ])->validate();
    }

    private function validateEncryptedValue(string $ciphertext, string $iv, string $field, int $maxLength): void
    {
        Validator::make([
            $field => $ciphertext,
            $field.'Iv' => $iv,
        ], [
            $field => ['required', 'string', 'max:'.$maxLength],
            $field.'Iv' => ['required', 'string', 'max:32'],
        ])->validate();

        $decodedCiphertext = base64_decode($ciphertext, true);
        $decodedIv = base64_decode($iv, true);

        if (! is_string($decodedCiphertext)
            || strlen($decodedCiphertext) < 16
            || ! is_string($decodedIv)
            || strlen($decodedIv) !== 12
        ) {
            throw ValidationException::withMessages([
                $field => 'The encrypted vault data is invalid.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeItem(VaultItem $item): array
    {
        return [
            'id' => $item->id,
            'folder_id' => $item->folder_id,
            'type' => $item->type,
            'encrypted_title' => $item->encrypted_title,
            'title_iv' => $item->title_iv,
            'encrypted_payload' => $item->encrypted_payload,
            'payload_iv' => $item->payload_iv,
            'is_favorite' => $item->is_favorite,
            'last_accessed_at' => $item->last_accessed_at?->toIso8601String(),
            'created_at' => $item->created_at?->toIso8601String(),
            'updated_at' => $item->updated_at?->toIso8601String(),
            'deleted_at' => $item->deleted_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeFolder(VaultFolder $folder): array
    {
        return [
            'id' => $folder->id,
            'encrypted_name' => $folder->encrypted_name,
            'iv' => $folder->iv,
            'sort_order' => $folder->sort_order,
            'created_at' => $folder->created_at?->toIso8601String(),
            'updated_at' => $folder->updated_at?->toIso8601String(),
        ];
    }
}
