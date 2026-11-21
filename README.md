# HiveVault

HiveVault is a passwordless, zero-knowledge vault for operators in **The Hive Ecosystem**. It is a satellite application that uses the Hive Hub for identity and stores vault data encrypted by the user's Master Password.

## Features

- **Hive Hub SSO:** Sign in through the Central Identity Provider using OAuth2. Hub user UUIDs are reused as HiveVault user IDs. The first Hub account to enter a new HiveVault installation becomes its local `admin`; later accounts are `operator`. These roles belong to this app.
- **Client-side encryption:** The browser derives an AES-256-GCM key from the Master Password using PBKDF2-SHA-256 with 600,000 iterations. Titles, folder names, and item payloads are encrypted before they are sent to the server.
- **Vault entries:** Store logins, SSH connections, database credentials, and secure notes. Organize entries into encrypted folders, search decrypted titles locally, mark favorites, and restore soft-deleted entries.
- **Developer quick actions:** Generate SSH commands and database connection URIs or CLI commands in the browser. Export an SSH private key as a PEM file.
- **Password and passphrase generator:** Create temporary or entry passwords using the browser's cryptographic random number generator.
- **Security controls:** Choose an inactivity auto-lock period, review recent security audit events, and automatically clear copied clipboard content after 30 seconds or when the vault locks.
- **Hive Command interface:** Responsive dark HUD styling, plus security headers and custom error pages.

## Requirements

- PHP 8.3 or newer and Composer
- Node.js 20.19+ or 22.12+ and npm
- SQLite (the default) or another database supported by Laravel
- A configured Hive Hub OAuth client
- HTTPS for the deployed app. Modern browsers require a secure context for Web Crypto and clipboard features; `localhost` is also treated as secure by browsers.

## Local setup

1. Install PHP dependencies and create the local environment file:

   ```sh
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

2. Configure `.env` before migrating. Set `APP_NAME`, `APP_URL`, your database connection, and the Hive Hub values shown below. The default database driver is SQLite. If using SQLite, create its database file:

   ```sh
   touch database/database.sqlite
   ```

3. Apply migrations and install frontend dependencies:

   ```sh
   php artisan migrate
   npm install
   ```

4. Start the app using your local PHP server and run Vite in development mode:

   ```sh
   php artisan serve
   npm run dev
   ```

   Run the commands in separate terminals. If you use a local HTTPS domain such as `https://vault.hive.test`, serve the app through your local HTTPS-capable PHP environment and make `APP_URL` and the Hub callback URL match that domain.

For a production asset build, run `npm run build` instead of `npm run dev`.

## Hive Hub configuration

Register HiveVault as an OAuth client in the Hub. The registered redirect URI must exactly match `HIVE_REDIRECT_URI` in HiveVault. For a local `vault.hive.test` setup, the relevant `.env` values look like this:

```dotenv
APP_NAME=HiveVault
APP_URL=https://vault.hive.test

HIVE_BASE_URL=https://hub.hive.test
HIVE_CLIENT_ID=your-client-id
HIVE_CLIENT_SECRET=your-client-secret
HIVE_REDIRECT_URI=https://vault.hive.test/auth/callback
HIVE_VERIFY_SSL=true
HIVE_CA_BUNDLE=/absolute/path/to/mkcert/rootCA.pem
```

`HIVE_CA_BUNDLE` is optional. Set it to a trusted CA PEM when PHP/Guzzle does not trust your local certificate authority, such as mkcert's root CA. Keep `HIVE_VERIFY_SSL=true`; do not disable certificate verification to work around a local trust issue. Keep client credentials in `.env` and out of source control.

The sign-in flow starts at `/login`. HiveVault creates a local user with the Hub UUID on first sign-in, then updates only that user's name and email on later sign-ins. The app does not provide local password sign-in.

## Using the vault

1. Sign in through the Hive Hub.
2. On first use, create and confirm a Master Password. Use a strong, unique password and keep a secure copy somewhere safe.
3. Create entries and folders from the three-pane workspace. Login, SSH, database, and note content is encrypted in your browser before it reaches HiveVault.
4. Use the entry inspector for copy actions, SSH/database connection builders, and private-key export. Generated passwords can be inserted into an entry or copied from the top-bar key generator.
5. Open **Security** to change auto-lock timing or review audit events. Use **Lock Vault** to clear the unlocked browser session while staying signed in to HiveVault. **Disconnect** ends the local session and redirects to the Hub.

## Security and recovery notes

- **The Master Password cannot be reset or recovered.** HiveVault does not receive it or the derived encryption key. Losing it means the encrypted vault contents cannot be decrypted.
- While unlocked, the derived key is kept in tab-scoped `sessionStorage` and the browser's active memory. Locking the vault, inactivity timeout, or closing the tab clears the session key. Do not leave an unlocked vault on a shared or untrusted device.
- Encryption protects item titles, folder names, and sensitive payload fields. The server still sees account identity and structural vault metadata such as entry type, folder association, favorite state, and timestamps. Audit records also contain event metadata, request IP address, and user agent.
- Clipboard clearing is best-effort: browser permissions or focus changes can prevent the browser from overwriting the system clipboard. The HUD reports when a wipe attempt fails.
- Use HTTPS and keep TLS verification enabled for both HiveVault and the Hub.

## Useful commands

```sh
php artisan migrate       # apply database migrations
php artisan test          # run the PHPUnit test suite
npm run dev               # run Vite in development mode
npm run build             # build production frontend assets
```

## Technology

HiveVault is built with Laravel 13, Livewire 4, Alpine.js, Tailwind CSS 4, and Vite. The browser cryptography uses the native Web Crypto API.
