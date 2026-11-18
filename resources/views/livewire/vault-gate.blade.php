<main
    class="hive-main hive-vault-main"
    id="workspace"
    x-data="vaultGate"
    wire:ignore
    data-vault-user-id="{{ $vaultUserId }}"
    data-vault-salt="{{ $vaultSalt ?? '' }}"
    data-vault-verifier="{{ $vaultVerifier ?? '' }}"
    data-auto-lock-minutes="{{ $autoLockMinutes }}"
>
    <section class="hive-page-heading" aria-labelledby="vault-page-title">
        <div>
            <p class="hive-eyebrow">Operations / Secure storage</p>
            <h1 id="vault-page-title">Hive Vault</h1>
            <p class="hive-page-heading__summary">Client-side encrypted storage for this satellite node.</p>
        </div>
        <p class="hive-link-status">
            <span class="hive-status-dot" aria-hidden="true"></span>
            Identity link verified
        </p>
    </section>

    <section class="hive-vault-gate" aria-live="polite">
        <div class="hive-panel hive-vault-boot" x-cloak x-show="stage === 'booting'">
            <p class="hive-eyebrow">Vault security / Initializing</p>
            <h2>Checking local session</h2>
        </div>

        <section class="hive-panel hive-vault-panel" aria-labelledby="vault-setup-title" x-cloak x-show="stage === 'setup'">
            <div class="hive-panel__meta">
                <p class="hive-eyebrow">First-time setup / Zero knowledge</p>
                <span class="hive-state">No recovery key</span>
            </div>
            <h2 id="vault-setup-title">Create your Master Password</h2>
            <p class="hive-panel__description">
                Your Master Password encrypts vault data in this browser. Hive Vault never receives it or the derived key.
                If you lose it, your encrypted vault cannot be recovered.
            </p>

            <div class="hive-vault-notice" role="note">
                <span class="hive-vault-notice__marker" aria-hidden="true">!</span>
                <p>Choose a strong password and store it safely. There is no reset or recovery process.</p>
            </div>

            <div class="hive-vault-form">
                <div class="hive-vault-field">
                    <label for="vault-master-password">Master Password</label>
                    <input
                        id="vault-master-password"
                        type="password"
                        minlength="12"
                        autocomplete="new-password"
                        autocapitalize="none"
                        spellcheck="false"
                        required
                        x-bind:disabled="isProcessing"
                        x-model="masterPassword"
                    >
                    <div class="hive-password-strength">
                        <div class="hive-password-strength__meter" role="meter" aria-label="Master Password strength" aria-valuemin="0" aria-valuemax="4" x-bind:aria-valuenow="passwordStrength" x-bind:aria-valuetext="passwordStrengthLabel">
                            <span x-bind:class="passwordStrength >= 1 ? 'is-active' : ''"></span>
                            <span x-bind:class="passwordStrength >= 2 ? 'is-active' : ''"></span>
                            <span x-bind:class="passwordStrength >= 3 ? 'is-active' : ''"></span>
                            <span x-bind:class="passwordStrength >= 4 ? 'is-active' : ''"></span>
                        </div>
                        <span x-text="passwordStrengthLabel">Not set</span>
                        <span class="hive-password-strength__hint">12 characters minimum</span>
                    </div>
                </div>

                <div class="hive-vault-field">
                    <label for="vault-master-password-confirmation">Confirm Master Password</label>
                    <input
                        id="vault-master-password-confirmation"
                        type="password"
                        minlength="12"
                        autocomplete="new-password"
                        autocapitalize="none"
                        spellcheck="false"
                        required
                        x-bind:disabled="isProcessing"
                        x-model="passwordConfirmation"
                    >
                </div>

                <p class="hive-vault-error" role="alert" x-cloak x-show="errorMessage" x-text="errorMessage"></p>

                <button class="hive-vault-button hive-vault-button--primary" type="button" x-on:click="initializeVault" x-bind:disabled="!canInitialize">
                    Initialize encrypted vault
                </button>
            </div>

            <p class="hive-vault-footnote">AES-256-GCM encryption <span aria-hidden="true">/</span> PBKDF2-SHA-256 key derivation</p>
        </section>

        <section class="hive-panel hive-vault-panel" aria-labelledby="vault-unlock-title" x-cloak x-show="stage === 'locked'">
            <div class="hive-panel__meta">
                <p class="hive-eyebrow">Vault access / Encrypted at rest</p>
                <span class="hive-state">Locked</span>
            </div>
            <h2 id="vault-unlock-title">Unlock your vault</h2>
            <p class="hive-panel__description">Enter your Master Password. Verification happens in this browser; failed attempts are not sent to the server.</p>

            <div class="hive-vault-form">
                <div class="hive-vault-field">
                    <label for="vault-unlock-password">Master Password</label>
                    <input
                        id="vault-unlock-password"
                        type="password"
                        autocomplete="current-password"
                        autocapitalize="none"
                        spellcheck="false"
                        required
                        x-bind:disabled="isProcessing"
                        x-model="masterPassword"
                    >
                </div>

                <p class="hive-vault-error" role="alert" x-cloak x-show="errorMessage" x-text="errorMessage"></p>

                <button class="hive-vault-button hive-vault-button--primary" type="button" x-on:click="unlockVault" x-bind:disabled="isProcessing || !masterPassword">
                    Unlock vault
                </button>
            </div>
        </section>

        <section class="hive-vault-unlocked" aria-labelledby="vault-workspace-title" x-cloak x-show="stage === 'unlocked'">
            <div class="hive-vault-statusbar">
                <div class="hive-vault-statusbar__encryption">
                    <span class="hive-status-dot" aria-hidden="true"></span>
                    <span class="hive-eyebrow">Encryption status</span>
                    <strong>AES-256-GCM active</strong>
                </div>
                <div class="hive-vault-statusbar__lock">
                    <span class="hive-eyebrow">Auto-lock</span>
                    <strong class="hive-vault-countdown" role="timer" aria-live="off" x-text="countdownLabel">15:00</strong>
                    <button class="hive-vault-button hive-vault-button--lock" type="button" x-on:click="lockVault">
                        Lock vault
                    </button>
                </div>
            </div>

            <section class="hive-panel hive-vault-workspace" aria-labelledby="vault-workspace-title">
                <p class="hive-eyebrow">Secure workspace / Unlocked</p>
                <h2 id="vault-workspace-title">Your vault is unlocked</h2>
                <p class="hive-panel__description">Your encryption key is active in this browser tab. Lock the vault or leave the tab inactive to clear it from this session.</p>
                <div class="hive-vault-workspace__empty">
                    <span class="hive-vault-workspace__glyph" aria-hidden="true">◇</span>
                    <p>Encrypted vault modules will appear here.</p>
                </div>
            </section>
        </section>
    </section>

    <footer class="hive-footer">
        <span>Hive Command</span>
        <span>Client-side encryption <span aria-hidden="true">/</span> Secure session</span>
    </footer>
</main>
