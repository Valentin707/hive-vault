import {
    clearSessionKey,
    discardDerivedKey,
    decryptData,
    deriveKey,
    encryptData,
    generateVaultSalt,
    importSessionKey,
    persistSessionKey,
    startAutoLock,
} from './vault-crypto';

const VERIFICATION_PAYLOAD = JSON.stringify({ status: 'HIVE_VAULT_VERIFIED' });

export function registerVaultGate(Alpine) {
    Alpine.data('vaultGate', () => ({
        stage: 'booting',
        masterPassword: '',
        passwordConfirmation: '',
        errorMessage: '',
        vaultSalt: '',
        vaultVerifier: '',
        cryptoKey: null,
        pendingCryptoKey: null,
        sessionKeyName: '',
        autoLockMinutes: 15,
        countdownSeconds: 0,
        isProcessing: false,
        stopAutoLockTimer: null,
        pageHideHandler: null,

        init() {
            this.sessionKeyName = `hive-vault:session-key:${this.$el.dataset.vaultUserId}`;
            this.vaultSalt = this.$el.dataset.vaultSalt;
            this.vaultVerifier = this.$el.dataset.vaultVerifier;
            this.autoLockMinutes = Math.max(1, Number.parseInt(this.$el.dataset.autoLockMinutes, 10) || 15);
            this.pageHideHandler = () => this.clearUnlockedSession();

            window.addEventListener('pagehide', this.pageHideHandler);

            if (!this.vaultSalt) {
                this.stage = 'setup';

                return;
            }

            this.stage = 'locked';
            this.restoreUnlockedSession();
        },

        destroy() {
            this.clearUnlockedSession();
            window.removeEventListener('pagehide', this.pageHideHandler);
        },

        get passwordStrength() {
            const password = this.masterPassword;

            if (!password) {
                return 0;
            }

            const length = [...password].length;
            const characterClasses = [/[a-z]/, /[A-Z]/, /[0-9]/, /[^a-zA-Z0-9]/]
                .filter((pattern) => pattern.test(password)).length;

            return Math.min(4, Math.floor(length / 6) + Math.max(0, characterClasses - 1));
        },

        get passwordStrengthLabel() {
            return ['Not set', 'Weak', 'Fair', 'Strong', 'Excellent'][this.passwordStrength];
        },

        get canInitialize() {
            return !this.isProcessing
                && this.masterPassword.length >= 12
                && this.passwordConfirmation === this.masterPassword;
        },

        get countdownLabel() {
            const minutes = Math.floor(this.countdownSeconds / 60).toString().padStart(2, '0');
            const seconds = (this.countdownSeconds % 60).toString().padStart(2, '0');

            return `${minutes}:${seconds}`;
        },

        async initializeVault() {
            if (this.isProcessing) {
                return;
            }

            this.errorMessage = '';

            if (!this.canInitialize) {
                this.errorMessage = 'USE AT LEAST 12 CHARACTERS AND CONFIRM THE MASTER PASSWORD.';

                return;
            }

            this.isProcessing = true;
            let cryptoKey = null;

            try {
                const salt = generateVaultSalt();
                cryptoKey = await deriveKey(this.masterPassword, salt);
                this.pendingCryptoKey = cryptoKey;
                this.clearPasswordInputs();

                const verifier = await encryptData(cryptoKey, VERIFICATION_PAYLOAD);
                const serializedVerifier = JSON.stringify(verifier);

                await this.$wire.initializeVault(salt, serializedVerifier);

                this.vaultSalt = salt;
                this.vaultVerifier = serializedVerifier;
                cryptoKey = await persistSessionKey(cryptoKey, this.sessionKeyName);
                this.pendingCryptoKey = null;
                this.activateVault(cryptoKey, false);
            } catch {
                if (cryptoKey) {
                    discardDerivedKey(cryptoKey);
                }

                this.pendingCryptoKey = null;
                clearSessionKey(this.sessionKeyName);
                this.cryptoKey = null;
                this.stage = this.vaultSalt ? 'locked' : 'setup';

                this.errorMessage = this.vaultSalt
                    ? 'VAULT SAVED BUT SESSION COULD NOT UNLOCK. ENTER YOUR MASTER PASSWORD.'
                    : 'VAULT SETUP FAILED. CHECK THE CONNECTION AND TRY AGAIN.';
            } finally {
                this.clearPasswordInputs();
                this.isProcessing = false;
            }
        },

        async unlockVault() {
            if (this.isProcessing) {
                return;
            }

            this.errorMessage = '';

            if (!this.masterPassword) {
                this.errorMessage = 'ENTER YOUR MASTER PASSWORD TO UNLOCK THE VAULT.';

                return;
            }

            this.isProcessing = true;
            let cryptoKey = null;

            try {
                cryptoKey = await deriveKey(this.masterPassword, this.vaultSalt);
                this.pendingCryptoKey = cryptoKey;
                this.clearPasswordInputs();
                await this.verifyCryptoKey(cryptoKey);
                cryptoKey = await persistSessionKey(cryptoKey, this.sessionKeyName);
                this.pendingCryptoKey = null;
                this.activateVault(cryptoKey, true);
            } catch {
                if (cryptoKey) {
                    discardDerivedKey(cryptoKey);
                }

                this.pendingCryptoKey = null;
                clearSessionKey(this.sessionKeyName);
                this.cryptoKey = null;
                this.stage = 'locked';
                this.errorMessage = 'ACCESS DENIED // MASTER PASSWORD OR VAULT DATA INVALID';
            } finally {
                this.clearPasswordInputs();
                this.isProcessing = false;
            }
        },

        async restoreUnlockedSession() {
            this.isProcessing = true;

            try {
                const cryptoKey = await importSessionKey(this.sessionKeyName);

                if (!cryptoKey) {
                    return;
                }

                await this.verifyCryptoKey(cryptoKey);
                this.activateVault(cryptoKey, true);
            } catch {
                clearSessionKey(this.sessionKeyName);
                this.errorMessage = 'SESSION KEY INVALID // ENTER YOUR MASTER PASSWORD';
            } finally {
                this.isProcessing = false;
            }
        },

        async verifyCryptoKey(cryptoKey) {
            const verifier = JSON.parse(this.vaultVerifier);
            const plaintext = await decryptData(cryptoKey, verifier.ciphertext, verifier.iv);
            const payload = JSON.parse(plaintext);

            if (payload.status !== 'HIVE_VAULT_VERIFIED') {
                throw new Error('Vault verification failed.');
            }
        },

        activateVault(cryptoKey, shouldRecordUnlock) {
            this.cryptoKey = cryptoKey;
            this.stage = 'unlocked';
            this.errorMessage = '';
            this.stopAutoLockTimer?.();
            this.stopAutoLockTimer = startAutoLock(
                this.autoLockMinutes,
                () => this.lockVault(true),
                (seconds) => {
                    this.countdownSeconds = seconds;
                },
            );

            if (shouldRecordUnlock) {
                this.$wire.recordVaultUnlocked().catch(() => {});
            }
        },

        lockVault(shouldRecordLock = true) {
            this.stopAutoLockTimer?.();
            this.stopAutoLockTimer = null;
            clearSessionKey(this.sessionKeyName);
            this.cryptoKey = null;
            this.countdownSeconds = 0;
            this.stage = 'locked';
            this.clearPasswordInputs();

            if (shouldRecordLock) {
                this.$wire.recordVaultLocked().catch(() => {});
            }
        },

        clearUnlockedSession() {
            this.stopAutoLockTimer?.();
            this.stopAutoLockTimer = null;
            if (this.pendingCryptoKey) {
                discardDerivedKey(this.pendingCryptoKey);
                this.pendingCryptoKey = null;
            }

            clearSessionKey(this.sessionKeyName);
            this.cryptoKey = null;
            this.clearPasswordInputs();

            if (this.vaultSalt) {
                this.stage = 'locked';
            }
        },

        clearPasswordInputs() {
            this.masterPassword = '';
            this.passwordConfirmation = '';
        },
    }));
}
