@if ($generatorContext === 'inline')
    <section class="hive-vault-generator-panel" x-cloak x-show="generatorOpen" aria-label="Password generator">
@else
    <section class="hive-vault-generator-panel is-modal-panel" x-cloak x-show="keygenOpen" aria-label="Key generator">
@endif
        <div class="hive-vault-generator-heading">
            <div>
                <p class="hive-eyebrow">Secure generator / <span x-text="generatorMode === 'password' ? 'Password' : 'Passphrase'">Password</span></p>
                <span class="hive-vault-generator-entropy" x-text="generatorEntropyLabel">0-BIT // FAIR</span>
            </div>
            <button class="hive-vault-icon-button" type="button" aria-label="Regenerate secret" title="Regenerate secret" x-on:click="regenerateGeneratedSecret">↻</button>
        </div>

        <div class="hive-vault-generator-output">
            <code x-text="generatedSecret || 'Generating secure preview…'">Generating secure preview…</code>
        </div>

        <div class="hive-vault-generator-actions">
            @if ($generatorContext === 'inline')
                <button class="hive-vault-mini-button is-muted" type="button" x-on:click="closeInlineGenerator">Close</button>
                <button class="hive-vault-button hive-vault-button--primary" type="button" x-bind:disabled="!generatedSecret" x-on:click="useGeneratedPassword">Use password</button>
            @else
                <span class="hive-vault-copied" role="status" x-cloak x-show="generatorCopied">Copied</span>
                <button class="hive-vault-button hive-vault-button--primary" type="button" x-bind:disabled="!generatedSecret" x-on:click="copyGeneratedSecret" x-text="generatorCopied ? 'Copied' : 'Copy'">Copy</button>
            @endif
        </div>

        <div class="hive-vault-generator-mode" role="tablist" aria-label="Secret type">
            <button class="hive-vault-generator-tab" type="button" role="tab" x-bind:aria-selected="generatorMode === 'password'" x-bind:class="generatorMode === 'password' ? 'is-active' : ''" x-on:click="setGeneratorMode('password')">Password</button>
            <button class="hive-vault-generator-tab" type="button" role="tab" x-bind:aria-selected="generatorMode === 'passphrase'" x-bind:class="generatorMode === 'passphrase' ? 'is-active' : ''" x-on:click="setGeneratorMode('passphrase')">Passphrase</button>
        </div>

        <div class="hive-vault-generator-controls" x-show="generatorMode === 'password'">
            <div class="hive-vault-generator-length">
                <label for="vault-generator-length-{{ $generatorId }}">Length</label>
                <input id="vault-generator-length-{{ $generatorId }}" type="range" min="12" max="64" step="1" x-bind:value="passwordGeneratorOptions.length" x-on:input="updatePasswordGeneratorLength($event.target.value)">
                <output x-text="passwordGeneratorOptions.length">24</output>
            </div>

            <div class="hive-vault-generator-pills" aria-label="Password character sets">
                <button class="hive-vault-generator-pill" type="button" x-bind:aria-pressed="passwordGeneratorOptions.uppercase" x-bind:class="passwordGeneratorOptions.uppercase ? 'is-active' : ''" x-on:click="togglePasswordCharacterSet('uppercase')">A-Z</button>
                <button class="hive-vault-generator-pill" type="button" x-bind:aria-pressed="passwordGeneratorOptions.lowercase" x-bind:class="passwordGeneratorOptions.lowercase ? 'is-active' : ''" x-on:click="togglePasswordCharacterSet('lowercase')">a-z</button>
                <button class="hive-vault-generator-pill" type="button" x-bind:aria-pressed="passwordGeneratorOptions.numbers" x-bind:class="passwordGeneratorOptions.numbers ? 'is-active' : ''" x-on:click="togglePasswordCharacterSet('numbers')">0-9</button>
                <button class="hive-vault-generator-pill" type="button" x-bind:aria-pressed="passwordGeneratorOptions.symbols" x-bind:class="passwordGeneratorOptions.symbols ? 'is-active' : ''" x-on:click="togglePasswordCharacterSet('symbols')">!@#</button>
                <button class="hive-vault-generator-pill" type="button" x-bind:aria-pressed="passwordGeneratorOptions.excludeAmbiguous" x-bind:class="passwordGeneratorOptions.excludeAmbiguous ? 'is-active' : ''" x-on:click="toggleExcludeAmbiguous">No ambiguous (0O1l)</button>
            </div>
        </div>

        <div class="hive-vault-generator-controls hive-vault-generator-passphrase" x-show="generatorMode === 'passphrase'">
            <label class="hive-vault-generator-select">
                <span>Word count</span>
                <select x-bind:value="passphraseGeneratorOptions.wordCount" x-on:change="updatePassphraseOption('wordCount', $event.target.value)">
                    @for ($wordCount = 3; $wordCount <= 8; $wordCount++)
                        <option value="{{ $wordCount }}">{{ $wordCount }} words</option>
                    @endfor
                </select>
            </label>
            <label class="hive-vault-generator-select">
                <span>Separator</span>
                <select x-bind:value="passphraseGeneratorOptions.separator === ' ' ? 'space' : passphraseGeneratorOptions.separator" x-on:change="updatePassphraseOption('separator', $event.target.value)">
                    <option value="-">Hyphen</option>
                    <option value="_">Underscore</option>
                    <option value=".">Period</option>
                    <option value="space">Space</option>
                </select>
            </label>
            <div class="hive-vault-generator-pills">
                <button class="hive-vault-generator-pill" type="button" x-bind:aria-pressed="passphraseGeneratorOptions.capitalize" x-bind:class="passphraseGeneratorOptions.capitalize ? 'is-active' : ''" x-on:click="togglePassphraseOption('capitalize')">Capitalize</button>
                <button class="hive-vault-generator-pill" type="button" x-bind:aria-pressed="passphraseGeneratorOptions.includeNumber" x-bind:class="passphraseGeneratorOptions.includeNumber ? 'is-active' : ''" x-on:click="togglePassphraseOption('includeNumber')">Add number</button>
            </div>
        </div>

        <p class="hive-vault-generator-error" role="alert" x-cloak x-show="generatorError" x-text="generatorError"></p>
    </section>
