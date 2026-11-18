<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="hive">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <title>{{ $code }} // {{ $title }} | Hive Vault</title>
    @vite(['resources/css/app.css'])
</head>
<body class="hive-error-page">
    <a class="hive-skip-link" href="#error-content">Skip to error details</a>
    <div class="hive-theme-grid" aria-hidden="true"></div>

    <div class="hive-shell">
        <header class="hive-error-header">
            <a class="hive-brand" href="{{ route('dashboard') }}" aria-label="Hive Vault dashboard">
                <span class="hive-brand__mark" aria-hidden="true">H</span>
                <span class="hive-brand__copy">
                    <span class="hive-eyebrow">Hive satellite node</span>
                    <span class="hive-brand__name">Hive Vault</span>
                </span>
            </a>
            <a class="hive-core-link" href="{{ config('services.hive.base_url') }}">
                <span class="hive-core-link__indicator" aria-hidden="true"></span>
                Hive Hub <span aria-hidden="true">//</span> Core
            </a>
        </header>

        <main class="hive-error-main" id="error-content" aria-labelledby="error-title">
            <section class="hive-error-panel">
                <div class="hive-error-panel__meta">
                    <p class="hive-eyebrow">Hive Command / {{ $category }}</p>
                    <span class="hive-error-status">HTTP {{ $code }}</span>
                </div>

                <p class="hive-error-code" aria-hidden="true">{{ $code }}</p>
                <h1 id="error-title">{{ $title }}</h1>
                <p class="hive-error-message">{{ $message }}</p>

                <div class="hive-error-actions">
                    <a class="hive-vault-button hive-vault-button--primary" href="{{ $actionUrl }}">{{ $actionLabel }}</a>
                    <a class="hive-error-link" href="{{ config('services.hive.base_url') }}">Open Hive Hub</a>
                </div>

                <div class="hive-error-readout" aria-label="Error status">
                    <span class="hive-status-dot" aria-hidden="true"></span>
                    <span>Vault data remains encrypted at rest</span>
                    <span class="hive-error-readout__node">NODE / HIVE-VAULT</span>
                </div>
            </section>

            <footer class="hive-error-footer">
                <span>Hive Command</span>
                <span>Response {{ $code }} <span aria-hidden="true">/</span> {{ $category }}</span>
            </footer>
        </main>
    </div>
</body>
</html>
