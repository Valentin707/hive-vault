<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="hive">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <title>Hive Vault // Hive Command</title>
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

        <main class="hive-main" id="workspace">
            <section class="hive-page-heading" aria-labelledby="page-title">
                <div>
                    <p class="hive-eyebrow">Operations / Overview</p>
                    <h1 id="page-title">Command workspace</h1>
                    <p class="hive-page-heading__summary">Your satellite node is ready for its next module.</p>
                </div>
                <p class="hive-link-status">
                    <span class="hive-status-dot" aria-hidden="true"></span>
                    Identity link verified
                </p>
            </section>

            <div class="hive-workspace-grid">
                <section class="hive-panel hive-panel--feature" aria-labelledby="node-status-title">
                    <div class="hive-panel__meta">
                        <p class="hive-eyebrow">Workspace / 01</p>
                        <span class="hive-state">Standby</span>
                    </div>
                    <h2 id="node-status-title">Satellite node online</h2>
                    <p class="hive-panel__description">The local workspace is connected to Hive Core and ready for feature modules.</p>
                    <div class="hive-panel__readout">
                        <span>Identity alignment</span>
                        <strong>UUID / 1:1</strong>
                    </div>
                </section>

                <section class="hive-panel" aria-labelledby="identity-title">
                    <div class="hive-panel__meta">
                        <p class="hive-eyebrow">Connection / 02</p>
                        <span class="hive-state hive-state--active">Linked</span>
                    </div>
                    <h2 id="identity-title">Central identity</h2>
                    <p class="hive-panel__description">Authentication is delegated to the Hive Hub identity provider.</p>
                    <a class="hive-panel__link" href="{{ config('services.hive.base_url') }}">Open Hive Hub <span aria-hidden="true">↗</span></a>
                </section>

                <section class="hive-panel hive-panel--empty" aria-labelledby="module-one-title">
                    <p class="hive-eyebrow">Module bay / 03</p>
                    <h2 id="module-one-title">Ready for integration</h2>
                    <p class="hive-panel__description">A clear workspace for your next feature module.</p>
                </section>

                <section class="hive-panel hive-panel--empty" aria-labelledby="module-two-title">
                    <p class="hive-eyebrow">Module bay / 04</p>
                    <h2 id="module-two-title">Awaiting assignment</h2>
                    <p class="hive-panel__description">Additional operator tools can be connected here.</p>
                </section>
            </div>

            <footer class="hive-footer">
                <span>Hive Command</span>
                <span>Satellite interface <span aria-hidden="true">/</span> Secure session</span>
            </footer>
        </main>
    </div>
</body>
</html>
