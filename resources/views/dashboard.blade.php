<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="hive">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <title>Hive Vault // Hive Command</title>
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a class="hive-skip-link" href="#workspace">Skip to workspace</a>
    <div class="hive-theme-grid" aria-hidden="true"></div>

    <div class="hive-shell">
        <header class="hive-header">
            <a class="hive-brand" href="{{ route('dashboard') }}" aria-label="Hive Vault dashboard">
                <span class="hive-brand__mark" aria-hidden="true">H</span>
                <span class="hive-brand__copy">
                    <span class="hive-eyebrow">Hive satellite node</span>
                    <span class="hive-brand__name">Hive Vault</span>
                </span>
            </a>

            <nav class="hive-nav" aria-label="Operator navigation">
                <a class="hive-core-link" href="{{ config('services.hive.base_url') }}">
                    <span class="hive-core-link__indicator" aria-hidden="true"></span>
                    HIVE HUB <span aria-hidden="true">//</span> CORE
                </a>

                <div class="hive-operator">
                    <span class="hive-operator__name">{{ auth()->user()->name }}</span>
                    <span class="hive-role-badge hive-role-badge--{{ auth()->user()->role }}">
                        {{ auth()->user()->role }}
                    </span>
                </div>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="hive-disconnect" type="submit">Disconnect</button>
                </form>
            </nav>
        </header>

        <livewire:vault-gate />
    </div>

    @livewireScriptConfig
</body>
</html>
