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

        <section class="hive-vault-unlocked" aria-labelledby="vault-workspace-title" x-cloak x-show="stage === 'unlocked'" x-data="vaultWorkspace" data-auto-lock-minutes="{{ $autoLockMinutes }}">
            <div class="hive-vault-statusbar">
                <div class="hive-vault-statusbar__encryption">
                    <span class="hive-status-dot" aria-hidden="true"></span>
                    <span class="hive-eyebrow">Encryption status</span>
                    <strong>AES-256-GCM active</strong>
                </div>
                <div class="hive-vault-clipboard-status" role="status" aria-live="polite" x-cloak x-show="clipboardCountdown > 0 || clipboardFeedback">
                    <span x-text="clipboardStatusLabel">CLIPBOARD WIPED</span>
                    <button class="hive-vault-button hive-vault-button--wipe" type="button" x-cloak x-show="clipboardCountdown > 0" x-on:click="wipeClipboard">Wipe now</button>
                </div>
                <div class="hive-vault-statusbar__lock">
                    <span class="hive-eyebrow">Auto-lock</span>
                    <strong class="hive-vault-countdown" role="timer" aria-live="off" x-text="countdownLabel">15:00</strong>
                    <button class="hive-vault-button hive-vault-button--keygen" type="button" aria-haspopup="dialog" x-bind:aria-expanded="keygenOpen" x-on:click="openKeygen">Keygen</button>
                    <button class="hive-vault-button hive-vault-button--security" type="button" aria-haspopup="dialog" x-bind:aria-expanded="securityModalOpen" x-on:click="openSecurity">Security</button>
                    <button class="hive-vault-button hive-vault-button--lock" type="button" x-on:click="lockVault">
                        Lock vault
                    </button>
                </div>
            </div>

            <div class="hive-vault-security-modal" x-cloak x-show="securityModalOpen" x-on:keydown.escape.window="closeSecurity">
                <div class="hive-vault-security-backdrop" x-on:click.self="closeSecurity">
                    <section class="hive-vault-security-dialog" role="dialog" aria-modal="true" aria-labelledby="vault-security-title">
                        <div class="hive-vault-security-heading">
                            <div>
                                <p class="hive-eyebrow">Hive Command / Security</p>
                                <h2 id="vault-security-title">Security telemetry</h2>
                            </div>
                            <button class="hive-vault-icon-button" type="button" aria-label="Close security settings" title="Close security settings" x-on:click="closeSecurity">×</button>
                        </div>

                        <div class="hive-vault-security-tabs" role="tablist" aria-label="Security sections">
                            <button class="hive-vault-security-tab" type="button" role="tab" id="vault-security-settings-tab" aria-controls="vault-security-settings" x-bind:aria-selected="securityTab === 'settings'" x-bind:class="securityTab === 'settings' ? 'is-active' : ''" x-on:click="selectSecurityTab('settings')">Vault settings</button>
                            <button class="hive-vault-security-tab" type="button" role="tab" id="vault-security-audit-tab" aria-controls="vault-security-audit" x-bind:aria-selected="securityTab === 'audit'" x-bind:class="securityTab === 'audit' ? 'is-active' : ''" x-on:click="selectSecurityTab('audit')">Audit telemetry</button>
                        </div>

                        <div class="hive-vault-security-content" id="vault-security-settings" role="tabpanel" aria-labelledby="vault-security-settings-tab" x-cloak x-show="securityTab === 'settings'">
                            <section class="hive-vault-security-section" aria-labelledby="vault-autolock-title">
                                <div class="hive-vault-security-section-heading">
                                    <div>
                                        <p class="hive-eyebrow">Session defense / Inactivity</p>
                                        <h3 id="vault-autolock-title">Auto-lock timeout</h3>
                                    </div>
                                    <span class="hive-vault-security-current" x-text="securityAutoLockMinutes === 0 ? 'NEVER' : `${securityAutoLockMinutes} MIN`">15 MIN</span>
                                </div>
                                <div class="hive-vault-security-options" aria-label="Auto-lock inactivity timeout">
                                    <template x-for="option in [{minutes: 1, label: '1 MIN'}, {minutes: 5, label: '5 MIN'}, {minutes: 15, label: '15 MIN'}, {minutes: 30, label: '30 MIN'}, {minutes: 60, label: '60 MIN'}, {minutes: 0, label: 'NEVER (TAB CLOSE)'}]" :key="option.minutes">
                                        <button class="hive-vault-security-option" type="button" x-bind:class="securityAutoLockMinutes === option.minutes ? 'is-active' : ''" x-bind:aria-pressed="securityAutoLockMinutes === option.minutes" x-bind:disabled="securitySettingsSaving" x-on:click="updateSecuritySettings(option.minutes)" x-text="option.label"></button>
                                    </template>
                                </div>
                                <p class="hive-vault-error" role="alert" x-cloak x-show="securitySettingsError" x-text="securitySettingsError"></p>
                            </section>

                            <section class="hive-vault-security-section" aria-labelledby="vault-crypto-status-title">
                                <div class="hive-vault-security-section-heading">
                                    <div>
                                        <p class="hive-eyebrow">Zero-knowledge / Cryptographic profile</p>
                                        <h3 id="vault-crypto-status-title">Active protection</h3>
                                    </div>
                                    <span class="hive-vault-security-current">CLIENT SIDE</span>
                                </div>
                                <dl class="hive-vault-crypto-readout">
                                    <div><dt>ACTIVE CIPHER</dt><dd>AES-256-GCM</dd></div>
                                    <div><dt>KEY DERIVATION</dt><dd>PBKDF2-SHA256 // 600,000 ITERATIONS</dd></div>
                                    <div><dt>SESSION KEY STORAGE</dt><dd>EPHEMERAL SESSION STORAGE</dd></div>
                                </dl>
                            </section>
                        </div>

                        <div class="hive-vault-security-content" id="vault-security-audit" role="tabpanel" aria-labelledby="vault-security-audit-tab" x-cloak x-show="securityTab === 'audit'">
                            <div class="hive-vault-audit-heading">
                                <div>
                                    <p class="hive-eyebrow">Local account / Latest 50 records</p>
                                    <h3>Security audit telemetry</h3>
                                </div>
                                <button class="hive-vault-button hive-vault-button--security" type="button" x-bind:disabled="auditLoading" x-on:click="loadAuditTelemetry">Refresh</button>
                            </div>
                            <p class="hive-vault-security-message" role="status" x-cloak x-show="auditLoading">Loading audit telemetry…</p>
                            <p class="hive-vault-error" role="alert" x-cloak x-show="auditError" x-text="auditError"></p>
                            <p class="hive-vault-security-message" x-cloak x-show="auditLoaded && !auditLoading && auditEntries.length === 0 && !auditError">NO AUDIT EVENTS RECORDED.</p>
                            <div class="hive-vault-audit-table-wrap" x-cloak x-show="auditEntries.length > 0">
                                <table class="hive-vault-audit-table">
                                    <thead><tr><th scope="col">Timestamp</th><th scope="col">Event</th><th scope="col">Target entry</th><th scope="col">Source IP</th></tr></thead>
                                    <tbody>
                                        <template x-for="entry in auditEntries" :key="entry.id">
                                            <tr>
                                                <td class="hive-vault-audit-time" x-text="entry.timestamp"></td>
                                                <td><span class="hive-vault-audit-event" x-bind:class="entry.eventClass" x-text="entry.eventLabel"></span></td>
                                                <td class="hive-vault-audit-target" x-text="entry.target"></td>
                                                <td class="hive-vault-audit-ip" x-text="entry.sourceIp"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <div class="hive-vault-keygen-modal" x-cloak x-show="keygenOpen" x-on:keydown.escape.window="closeKeygen">
                <div class="hive-vault-keygen-backdrop" x-on:click.self="closeKeygen">
                    <section class="hive-vault-keygen-dialog" role="dialog" aria-modal="true" aria-labelledby="vault-keygen-title">
                        <div class="hive-vault-keygen-dialog-heading">
                            <div>
                                <p class="hive-eyebrow">Hive Command / Utility</p>
                                <h2 id="vault-keygen-title">Temporary key generator</h2>
                            </div>
                            <button class="hive-vault-icon-button" type="button" aria-label="Close key generator" title="Close key generator" x-on:click="closeKeygen">×</button>
                        </div>
                        @include('livewire.vault-generator-panel', ['generatorContext' => 'standalone', 'generatorId' => 'keygen'])
                    </section>
                </div>
            </div>

            @include('livewire.vault-workspace')
        </section>
    </section>

    <footer class="hive-footer">
        <span>Hive Command</span>
        <span>Client-side encryption <span aria-hidden="true">/</span> Secure session</span>
    </footer>
</main>
