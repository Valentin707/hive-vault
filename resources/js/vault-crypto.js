const PBKDF2_ITERATIONS = 600_000;
const AES_KEY_LENGTH = 256;
const AES_GCM_IV_LENGTH = 12;

const textEncoder = new TextEncoder();
const textDecoder = new TextDecoder();
const pendingSessionKeys = new WeakMap();

function encodeBase64(bytes) {
    let binary = '';

    for (let offset = 0; offset < bytes.length; offset += 0x8000) {
        binary += String.fromCharCode(...bytes.subarray(offset, offset + 0x8000));
    }

    return btoa(binary);
}

function decodeBase64(value) {
    const binary = atob(value);
    const bytes = new Uint8Array(binary.length);

    for (let index = 0; index < binary.length; index += 1) {
        bytes[index] = binary.charCodeAt(index);
    }

    return bytes;
}

function requireWebCrypto() {
    if (!globalThis.crypto?.subtle || !globalThis.crypto?.getRandomValues) {
        throw new Error('Web Crypto is unavailable. Open Hive Vault over a secure HTTPS connection.');
    }
}

export function generateVaultSalt() {
    requireWebCrypto();

    return encodeBase64(globalThis.crypto.getRandomValues(new Uint8Array(32)));
}

export async function deriveKey(masterPassword, saltBase64) {
    requireWebCrypto();

    const salt = decodeBase64(saltBase64);

    if (salt.length !== 32) {
        salt.fill(0);
        throw new Error('The vault salt is invalid.');
    }

    try {
        const passwordBytes = textEncoder.encode(masterPassword);
        let passwordKey;

        try {
            passwordKey = await globalThis.crypto.subtle.importKey(
                'raw',
                passwordBytes,
                'PBKDF2',
                false,
                ['deriveBits'],
            );
        } finally {
            passwordBytes.fill(0);
        }

        const keyMaterial = new Uint8Array(await globalThis.crypto.subtle.deriveBits(
            {
                name: 'PBKDF2',
                salt,
                iterations: PBKDF2_ITERATIONS,
                hash: 'SHA-256',
            },
            passwordKey,
            AES_KEY_LENGTH,
        ));

        try {
            const cryptoKey = await globalThis.crypto.subtle.importKey(
                'raw',
                keyMaterial,
                { name: 'AES-GCM', length: AES_KEY_LENGTH },
                false,
                ['encrypt', 'decrypt'],
            );

            pendingSessionKeys.set(cryptoKey, keyMaterial);

            return cryptoKey;
        } catch (error) {
            keyMaterial.fill(0);
            throw error;
        }
    } finally {
        salt.fill(0);
    }
}

export async function persistSessionKey(cryptoKey, sessionKeyName) {
    requireWebCrypto();

    const rawKey = pendingSessionKeys.get(cryptoKey);

    if (!rawKey) {
        throw new Error('This key cannot be persisted for the current session.');
    }

    try {
        sessionStorage.setItem(sessionKeyName, encodeBase64(rawKey));

        return cryptoKey;
    } catch (error) {
        clearSessionKey(sessionKeyName);
        throw error;
    } finally {
        pendingSessionKeys.delete(cryptoKey);
        rawKey.fill(0);
    }
}

export function discardDerivedKey(cryptoKey) {
    const keyMaterial = pendingSessionKeys.get(cryptoKey);

    if (!keyMaterial) {
        return;
    }

    keyMaterial.fill(0);
    pendingSessionKeys.delete(cryptoKey);
}

export async function importSessionKey(sessionKeyName) {
    requireWebCrypto();

    const encodedKey = sessionStorage.getItem(sessionKeyName);

    if (!encodedKey) {
        return null;
    }

    const rawKey = decodeBase64(encodedKey);

    if (rawKey.length !== AES_KEY_LENGTH / 8) {
        rawKey.fill(0);
        clearSessionKey(sessionKeyName);
        return null;
    }

    try {
        return await globalThis.crypto.subtle.importKey(
            'raw',
            rawKey,
            { name: 'AES-GCM', length: AES_KEY_LENGTH },
            false,
            ['encrypt', 'decrypt'],
        );
    } catch {
        clearSessionKey(sessionKeyName);
        return null;
    } finally {
        rawKey.fill(0);
    }
}

export async function encryptData(cryptoKey, plaintextString) {
    requireWebCrypto();

    const iv = globalThis.crypto.getRandomValues(new Uint8Array(AES_GCM_IV_LENGTH));
    const plaintextBytes = textEncoder.encode(plaintextString);

    try {
        const ciphertext = await globalThis.crypto.subtle.encrypt(
            { name: 'AES-GCM', iv },
            cryptoKey,
            plaintextBytes,
        );

        return {
            ciphertext: encodeBase64(new Uint8Array(ciphertext)),
            iv: encodeBase64(iv),
        };
    } finally {
        plaintextBytes.fill(0);
    }
}

export async function decryptData(cryptoKey, ciphertextBase64, ivBase64) {
    requireWebCrypto();

    const ciphertext = decodeBase64(ciphertextBase64);
    const iv = decodeBase64(ivBase64);

    if (iv.length !== AES_GCM_IV_LENGTH) {
        throw new Error('Encrypted data is invalid or has been modified.');
    }

    try {
        const plaintextBuffer = await globalThis.crypto.subtle.decrypt(
            { name: 'AES-GCM', iv },
            cryptoKey,
            ciphertext,
        );

        const plaintextBytes = new Uint8Array(plaintextBuffer);

        try {
            return textDecoder.decode(plaintextBytes);
        } finally {
            plaintextBytes.fill(0);
        }
    } catch {
        throw new Error('Authentication failed. The master password is incorrect or encrypted data was modified.');
    } finally {
        ciphertext.fill(0);
        iv.fill(0);
    }
}

export function clearSessionKey(sessionKeyName) {
    try {
        sessionStorage.removeItem(sessionKeyName);
    } catch {
        // Storage may be unavailable in restricted browser contexts.
    }
}

export function startAutoLock(minutes, onTimeout, onTick) {
    const timeoutMinutes = Number(minutes);

    if (!Number.isFinite(timeoutMinutes) || timeoutMinutes <= 0) {
        onTick(0);

        return () => {};
    }

    const timeoutMilliseconds = timeoutMinutes * 60_000;
    let lastActivityAt = Date.now();
    let active = true;

    const recordActivity = () => {
        lastActivityAt = Date.now();
    };

    const activityEvents = ['pointerdown', 'pointermove', 'keydown', 'touchstart'];

    activityEvents.forEach((eventName) => {
        window.addEventListener(eventName, recordActivity, { passive: true });
    });

    const checkInactivity = () => {
        if (!active) {
            return;
        }

        const remainingMilliseconds = timeoutMilliseconds - (Date.now() - lastActivityAt);
        const remainingSeconds = Math.max(0, Math.ceil(remainingMilliseconds / 1000));

        onTick(remainingSeconds);

        if (remainingMilliseconds <= 0) {
            stop();
            onTimeout();
        }
    };

    const timer = window.setInterval(checkInactivity, 1000);
    document.addEventListener('visibilitychange', checkInactivity);

    function stop() {
        if (!active) {
            return;
        }

        active = false;
        window.clearInterval(timer);
        document.removeEventListener('visibilitychange', checkInactivity);

        activityEvents.forEach((eventName) => {
            window.removeEventListener(eventName, recordActivity);
        });
    }

    onTick(Math.ceil(timeoutMilliseconds / 1000));

    return stop;
}
