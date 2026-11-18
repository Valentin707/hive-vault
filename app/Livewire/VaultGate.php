<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\VaultAuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
}
