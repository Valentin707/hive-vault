<div class="hive-vault-command" aria-label="Encrypted vault workspace">
    <aside class="hive-panel hive-vault-rail" aria-label="Vault navigation">
        <div class="hive-vault-pane-heading">
            <p class="hive-eyebrow">Command / Index</p>
            <span class="hive-vault-pane-mark" aria-hidden="true">01</span>
        </div>

        <nav class="hive-vault-filter-list" aria-label="System filters">
            <button class="hive-vault-filter" type="button" x-on:click="selectFilter('all')" x-bind:class="isSelectedFilter('all') ? 'is-active' : ''">
                <span class="hive-vault-filter__label"><span class="hive-vault-filter__icon" aria-hidden="true">▦</span>All items</span>
                <span class="hive-vault-count" x-text="activeItemCount">0</span>
            </button>
            <button class="hive-vault-filter" type="button" x-on:click="selectFilter('favorites')" x-bind:class="isSelectedFilter('favorites') ? 'is-active' : ''">
                <span class="hive-vault-filter__label"><span class="hive-vault-filter__icon" aria-hidden="true">★</span>Favorites</span>
                <span class="hive-vault-count" x-text="favoriteCount">0</span>
            </button>
            <button class="hive-vault-filter" type="button" x-on:click="selectFilter('trash')" x-bind:class="isSelectedFilter('trash') ? 'is-active' : ''">
                <span class="hive-vault-filter__label"><span class="hive-vault-filter__icon" aria-hidden="true">⌑</span>Trash</span>
                <span class="hive-vault-count" x-text="trashCount">0</span>
            </button>
        </nav>

        <div class="hive-vault-rail-divider"></div>

        <p class="hive-eyebrow hive-vault-section-label">Entry types</p>
        <nav class="hive-vault-filter-list hive-vault-type-list" aria-label="Item type filters">
            <button class="hive-vault-filter" type="button" x-on:click="selectFilter('login')" x-bind:class="isSelectedFilter('login') ? 'is-active' : ''">
                <span class="hive-vault-filter__label"><span class="hive-vault-filter__icon" aria-hidden="true">◈</span>Logins</span>
                <span class="hive-vault-count" x-text="countForType('login')">0</span>
            </button>
            <button class="hive-vault-filter" type="button" x-on:click="selectFilter('ssh')" x-bind:class="isSelectedFilter('ssh') ? 'is-active' : ''">
                <span class="hive-vault-filter__label"><span class="hive-vault-filter__icon" aria-hidden="true">⌘</span>SSH keys</span>
                <span class="hive-vault-count" x-text="countForType('ssh')">0</span>
            </button>
            <button class="hive-vault-filter" type="button" x-on:click="selectFilter('database')" x-bind:class="isSelectedFilter('database') ? 'is-active' : ''">
                <span class="hive-vault-filter__label"><span class="hive-vault-filter__icon" aria-hidden="true">▤</span>Databases</span>
                <span class="hive-vault-count" x-text="countForType('database')">0</span>
            </button>
            <button class="hive-vault-filter" type="button" x-on:click="selectFilter('note')" x-bind:class="isSelectedFilter('note') ? 'is-active' : ''">
                <span class="hive-vault-filter__label"><span class="hive-vault-filter__icon" aria-hidden="true">◇</span>Secure notes</span>
                <span class="hive-vault-count" x-text="countForType('note')">0</span>
            </button>
        </nav>

        <div class="hive-vault-rail-divider"></div>

        <div class="hive-vault-folder-heading">
            <p class="hive-eyebrow">Encrypted folders</p>
            <button class="hive-vault-icon-button hive-vault-add-folder" type="button" aria-label="Create folder" title="Create folder" x-on:click="beginNewFolder">+</button>
        </div>

        <div class="hive-vault-folder-editor" x-cloak x-show="folderEditorOpen">
            <label class="hive-vault-sr-only" for="vault-folder-name">Folder name</label>
            <input id="vault-folder-name" type="text" maxlength="120" autocomplete="off" placeholder="Folder name" x-model="folderNameInput" x-on:keydown.enter.prevent="saveFolder" x-on:keydown.escape="cancelFolderEdit">
            <div class="hive-vault-inline-actions">
                <button class="hive-vault-mini-button" type="button" x-on:click="saveFolder" x-bind:disabled="isMutating">Save</button>
                <button class="hive-vault-mini-button is-muted" type="button" x-on:click="cancelFolderEdit">Cancel</button>
            </div>
            <p class="hive-vault-inline-error" x-cloak x-show="folderError" x-text="folderError"></p>
        </div>

        <div class="hive-vault-folder-list" aria-label="Folders">
            <template x-for="folder in folders" :key="folder.id">
                <div class="hive-vault-folder-row" x-bind:class="isSelectedFolder(folder.id) ? 'is-active' : ''">
                    <button class="hive-vault-folder-select" type="button" x-on:click="selectFolder(folder.id)">
                        <span class="hive-vault-folder-glyph" aria-hidden="true">▰</span>
                        <span class="hive-vault-folder-name" x-text="folder.name"></span>
                        <span class="hive-vault-count" x-text="countForFolder(folder.id)">0</span>
                    </button>
                    <div class="hive-vault-folder-actions">
                        <button class="hive-vault-icon-button" type="button" aria-label="Rename folder" title="Rename folder" x-on:click.stop="beginFolderRename(folder)">⌁</button>
                        <button class="hive-vault-icon-button is-danger" type="button" aria-label="Delete folder" title="Delete folder" x-on:click.stop="deleteFolder(folder)">×</button>
                    </div>
                </div>
            </template>
            <p class="hive-vault-folder-empty" x-show="folders.length === 0">No folders configured.</p>
        </div>

        <div class="hive-vault-rail-footer">
            <span class="hive-status-dot" aria-hidden="true"></span>
            <span>Client-side encrypted</span>
        </div>
    </aside>

    <section class="hive-panel hive-vault-stream" aria-label="Vault item stream">
        <div class="hive-vault-pane-heading">
            <div>
                <p class="hive-eyebrow">Encrypted index / <span x-text="activeFilterLabel()">All items</span></p>
                <h2 class="hive-vault-stream-title">Vault stream</h2>
            </div>
            <span class="hive-vault-pane-mark" aria-hidden="true">02</span>
        </div>

        <div class="hive-vault-stream-tools">
            <label class="hive-vault-search-wrap" for="vault-item-search">
                <span aria-hidden="true">⌕</span>
                <input id="vault-item-search" type="search" autocomplete="off" placeholder="Search decrypted titles..." x-model="searchQuery">
            </label>
            <button class="hive-vault-button hive-vault-button--primary hive-vault-new-entry" type="button" x-on:click="startNewEntry">
                <span aria-hidden="true">+</span> New entry
            </button>
        </div>

        <div class="hive-vault-stream-status" x-cloak x-show="isLoading">
            <span class="hive-status-dot" aria-hidden="true"></span>
            Decrypting local index
        </div>

        <p class="hive-vault-inline-error" role="alert" x-cloak x-show="workspaceError" x-text="workspaceError"></p>

        <div class="hive-vault-item-list" aria-label="Vault items">
            <template x-for="item in visibleItems" :key="item.id">
                <article class="hive-vault-item-card" x-bind:class="isSelectedItem(item.id) ? 'is-selected' : ''">
                    <button class="hive-vault-item-open" type="button" x-on:click="selectItem(item.id)">
                        <span class="hive-vault-item-icon" aria-hidden="true" x-text="typeIcon(item.type)">◈</span>
                        <span class="hive-vault-item-copy">
                            <span class="hive-vault-type-badge" x-text="typeLabel(item.type)">LOGIN</span>
                            <span class="hive-vault-item-title" x-text="item.title"></span>
                            <span class="hive-vault-item-folder" x-text="folderName(item.folder_id)"></span>
                        </span>
                    </button>
                    <button class="hive-vault-icon-button hive-vault-star" type="button" x-bind:class="item.is_favorite ? 'is-favorite' : ''" x-bind:aria-label="item.is_favorite ? 'Remove from favorites' : 'Add to favorites'" x-bind:title="item.is_favorite ? 'Remove favorite' : 'Add favorite'" x-bind:disabled="isMutating" x-on:click.stop="toggleFavorite(item.id)">
                        <span aria-hidden="true" x-text="item.is_favorite ? '★' : '☆'">☆</span>
                    </button>
                </article>
            </template>

            <div class="hive-vault-stream-empty" x-cloak x-show="!isLoading && visibleItems.length === 0">
                <span class="hive-vault-empty-glyph" aria-hidden="true">◇</span>
                <p x-text="searchQuery ? 'No decrypted titles match this search.' : 'No entries in this view.'">No entries in this view.</p>
            </div>
        </div>

        <div class="hive-vault-stream-footer">
            <span><span x-text="visibleItems.length">0</span> visible</span>
            <span x-text="isLoading ? 'SYNCING' : 'LOCAL SEARCH'">LOCAL SEARCH</span>
        </div>
    </section>

    <section class="hive-panel hive-vault-inspector" role="region" aria-labelledby="vault-workspace-title">
        <template x-if="inspectorMode === 'empty'">
            <div class="hive-vault-inspector-empty">
                <span class="hive-vault-empty-glyph" aria-hidden="true">⌖</span>
                <p class="hive-eyebrow">Inspector / Standby</p>
                <h2 id="vault-workspace-title">Select an entry or create new</h2>
                <p>Encrypted records decrypt only in this browser session.</p>
                <span class="hive-vault-inspector-coordinate" aria-hidden="true">NODE / VAULT / 03</span>
            </div>
        </template>

        <template x-if="inspectorMode === 'view' && selectedItem">
            <div class="hive-vault-inspector-content">
                <div class="hive-vault-inspector-heading">
                    <div>
                        <p class="hive-eyebrow">Inspector / <span x-text="typeLabel(selectedItem.type)">LOGIN</span></p>
                        <h2 id="vault-workspace-title" x-text="selectedItem.title"></h2>
                        <p class="hive-vault-inspector-subline">
                            <span x-text="folderName(selectedItem.folder_id)">Unfiled</span>
                            <span aria-hidden="true">/</span>
                            <span x-text="selectedItem.deleted_at ? 'In trash' : 'Encrypted at rest'">Encrypted at rest</span>
                        </p>
                    </div>
                    <span class="hive-vault-pane-mark" aria-hidden="true">03</span>
                </div>

                <div class="hive-vault-inspector-toolbar">
                    <template x-if="!selectedItem.deleted_at">
                        <div class="hive-vault-toolbar-group">
                            <button class="hive-vault-mini-button" type="button" x-bind:disabled="!selectedItem.payload || selectedItem.title === 'UNREADABLE ITEM'" x-on:click="startEditing">Edit</button>
                            <button class="hive-vault-mini-button" type="button" x-bind:disabled="isMutating" x-on:click="toggleFavorite(selectedItem.id)" x-text="selectedItem.is_favorite ? '★ Favorite' : '☆ Favorite'">☆ Favorite</button>
                            <button class="hive-vault-mini-button is-danger" type="button" x-bind:disabled="isMutating" x-on:click="moveSelectedItemToTrash">Delete</button>
                        </div>
                    </template>
                    <template x-if="selectedItem.deleted_at">
                        <div class="hive-vault-toolbar-group">
                            <button class="hive-vault-mini-button" type="button" x-bind:disabled="isMutating" x-on:click="restoreSelectedItem">Restore</button>
                            <button class="hive-vault-mini-button is-danger" type="button" x-bind:disabled="isMutating" x-on:click="permanentlyDeleteSelectedItem">Delete permanently</button>
                        </div>
                    </template>
                </div>

                <p class="hive-vault-inline-error" role="alert" x-cloak x-show="workspaceError" x-text="workspaceError"></p>

                <div class="hive-vault-fields-loading" x-cloak x-show="!selectedItem.payload">
                    <span class="hive-status-dot" aria-hidden="true"></span>
                    Verifying encrypted payload
                </div>

                <section class="hive-vault-builder-stack" x-cloak x-show="selectedItem.payload && selectedItem.type === 'ssh'" aria-label="SSH quick connect">
                    <div class="hive-vault-terminal-block">
                        <div class="hive-vault-terminal-heading">
                            <p class="hive-eyebrow">Quick connect // SSH</p>
                            <span class="hive-vault-terminal-state">Client generated</span>
                        </div>
                        <div class="hive-vault-terminal-row">
                            <code class="hive-vault-terminal-code" x-text="sshCommand"></code>
                            <div class="hive-vault-terminal-actions">
                                <span class="hive-vault-copied" x-cloak x-show="copiedField === 'ssh-command'">Copied</span>
                                <button class="hive-vault-mini-button" type="button" x-bind:disabled="!sshCommand" x-on:click="copyField('ssh-command', sshCommand)">Copy cmd</button>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="hive-vault-builder-stack" x-cloak x-show="selectedItem.payload && selectedItem.type === 'database'" aria-label="Database connection strings">
                    <div class="hive-vault-terminal-block">
                        <div class="hive-vault-terminal-heading">
                            <p class="hive-eyebrow">Connection URI // Generated</p>
                            <span class="hive-vault-terminal-state" x-text="isFieldRevealed('database-uri') ? 'Credentials revealed' : 'Credentials masked'">Credentials masked</span>
                        </div>
                        <div class="hive-vault-terminal-row is-stacked">
                            <code class="hive-vault-terminal-code" x-text="isFieldRevealed('database-uri') ? databaseConnectionStrings.uri : databaseConnectionStrings.maskedUri"></code>
                            <div class="hive-vault-terminal-actions">
                                <span class="hive-vault-copied" x-cloak x-show="copiedField === 'database-uri'">Copied</span>
                                <button class="hive-vault-mini-button" type="button" x-bind:aria-pressed="isFieldRevealed('database-uri')" x-on:click="toggleFieldReveal('database-uri')" x-text="isFieldRevealed('database-uri') ? 'Hide' : 'Reveal'">Reveal</button>
                                <button class="hive-vault-mini-button" type="button" x-on:click="copyField('database-uri', databaseConnectionStrings.uri)">Copy URI</button>
                            </div>
                        </div>
                    </div>

                    <div class="hive-vault-terminal-block" x-show="databaseConnectionStrings.supportsCli">
                        <div class="hive-vault-terminal-heading">
                            <p class="hive-eyebrow">Native CLI // Generated</p>
                            <span class="hive-vault-terminal-state" x-text="isFieldRevealed('database-cli') ? 'Credentials revealed' : 'Credentials masked'">Credentials masked</span>
                        </div>
                        <div class="hive-vault-terminal-row is-stacked">
                            <code class="hive-vault-terminal-code" x-text="isFieldRevealed('database-cli') ? databaseConnectionStrings.cli : databaseConnectionStrings.maskedCli"></code>
                            <div class="hive-vault-terminal-actions">
                                <span class="hive-vault-copied" x-cloak x-show="copiedField === 'database-cli'">Copied</span>
                                <button class="hive-vault-mini-button" type="button" x-bind:aria-pressed="isFieldRevealed('database-cli')" x-on:click="toggleFieldReveal('database-cli')" x-text="isFieldRevealed('database-cli') ? 'Hide' : 'Reveal'">Reveal</button>
                                <button class="hive-vault-mini-button" type="button" x-on:click="copyField('database-cli', databaseConnectionStrings.cli)">Copy CLI</button>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="hive-vault-field-list" x-show="selectedItem.payload">
                    <template x-for="field in inspectorFields" :key="field.key">
                        <article class="hive-vault-read-field">
                            <div class="hive-vault-read-field-heading">
                                <p class="hive-eyebrow" x-text="field.label">Field</p>
                                <span class="hive-vault-copied" x-cloak x-show="copiedField === field.key">Copied</span>
                            </div>
                            <div class="hive-vault-read-field-body">
                                <pre x-bind:class="field.monospace ? 'is-monospace' : ''" x-text="field.secret && !isFieldRevealed(field.key) ? '••••••••••••' : field.value"></pre>
                                <div class="hive-vault-read-field-actions">
                                    <button class="hive-vault-mini-button" type="button" x-show="field.secret" x-bind:aria-pressed="isFieldRevealed(field.key)" x-on:click="toggleFieldReveal(field.key)" x-text="isFieldRevealed(field.key) ? 'Hide' : 'Reveal'">Reveal</button>
                                    <button class="hive-vault-mini-button" type="button" x-cloak x-show="field.key === 'private_key' && canDownloadPrivateKey" x-on:click="downloadPrivateKey">Download .PEM</button>
                                    <a class="hive-vault-mini-button hive-vault-launch" x-cloak x-show="field.href" x-bind:href="field.href" target="_blank" rel="noopener noreferrer" aria-label="Open URL in a new tab" title="Open URL in a new tab">↗</a>
                                    <button class="hive-vault-mini-button" type="button" x-on:click="copyField(field.key, field.value)">Copy</button>
                                </div>
                            </div>
                        </article>
                    </template>
                </div>

                <div class="hive-vault-inspector-meta" x-show="selectedItem.payload">
                    <span>Last accessed</span>
                    <time x-text="formatTimestamp(selectedItem.last_accessed_at)">Never</time>
                </div>
            </div>
        </template>

        <template x-if="inspectorMode === 'create' || inspectorMode === 'edit'">
            <div class="hive-vault-inspector-content hive-vault-editor">
                <div class="hive-vault-inspector-heading">
                    <div>
                        <p class="hive-eyebrow">Inspector / <span x-text="inspectorMode === 'create' ? 'New entry' : 'Edit entry'">New entry</span></p>
                        <h2 id="vault-workspace-title" x-text="inspectorMode === 'create' ? 'Create encrypted entry' : 'Update encrypted entry'">Create encrypted entry</h2>
                    </div>
                    <span class="hive-vault-pane-mark" aria-hidden="true">03</span>
                </div>

                <div class="hive-vault-editor-fields">
                    <label class="hive-vault-field">
                        <span>Item type</span>
                        <select x-model="form.type" x-bind:disabled="form.id !== null" x-on:change="changeItemType(form.type)">
                            <option value="login">Login</option>
                            <option value="ssh">SSH connection</option>
                            <option value="database">Database</option>
                            <option value="note">Secure note</option>
                        </select>
                    </label>

                    <label class="hive-vault-field">
                        <span>Title <b aria-hidden="true">*</b></span>
                        <input type="text" maxlength="160" autocomplete="off" required x-model="form.title">
                    </label>

                    <label class="hive-vault-field">
                        <span>Folder</span>
                        <select x-model="form.folderId">
                            <option value="">Unfiled</option>
                            <template x-for="folder in folders" :key="folder.id">
                                <option x-bind:value="folder.id" x-text="folder.name"></option>
                            </template>
                        </select>
                    </label>

                    <div class="hive-vault-field hive-vault-favorite-field">
                        <span>Favorite</span>
                        <button class="hive-vault-toggle" type="button" x-bind:class="form.isFavorite ? 'is-active' : ''" x-bind:aria-pressed="form.isFavorite" x-on:click="form.isFavorite = !form.isFavorite">
                            <span aria-hidden="true" x-text="form.isFavorite ? '★' : '☆'">☆</span>
                            <span x-text="form.isFavorite ? 'Pinned' : 'Not pinned'">Not pinned</span>
                        </button>
                    </div>

                    <div class="hive-vault-editor-divider"></div>

                    <div class="hive-vault-type-fields" x-show="form.type === 'login'">
                        <label class="hive-vault-field">
                            <span>Username <b aria-hidden="true">*</b></span>
                            <input type="text" autocomplete="off" required x-model="form.username">
                        </label>
                        <div class="hive-vault-field">
                            <label for="login-password">Password <b aria-hidden="true">*</b></label>
                            <span class="hive-vault-input-action">
                                <input id="login-password" x-bind:type="showFormPassword ? 'text' : 'password'" autocomplete="new-password" required x-model="form.password">
                                <button class="hive-vault-input-toggle" type="button" x-bind:aria-label="showFormPassword ? 'Hide password' : 'Show password'" x-on:click="showFormPassword = !showFormPassword"><span aria-hidden="true">◉</span><span x-text="showFormPassword ? 'Hide' : 'Show'">Show</span></button>
                                <button class="hive-vault-generator-trigger" type="button" x-bind:aria-expanded="generatorOpen" x-on:click="togglePasswordGenerator">Generate</button>
                            </span>
                            @include('livewire.vault-generator-panel', ['generatorContext' => 'inline', 'generatorId' => 'login'])
                        </div>
                        <label class="hive-vault-field">
                            <span>URL <b aria-hidden="true">*</b></span>
                            <input type="url" placeholder="https://example.com" autocomplete="url" required x-model="form.url">
                        </label>
                    </div>

                    <div class="hive-vault-type-fields" x-show="form.type === 'ssh'">
                        <label class="hive-vault-field">
                            <span>Host <b aria-hidden="true">*</b></span>
                            <input type="text" placeholder="192.0.2.10" autocomplete="off" required x-model="form.host">
                        </label>
                        <label class="hive-vault-field">
                            <span>Port <b aria-hidden="true">*</b></span>
                            <input type="number" min="1" max="65535" step="1" required x-model="form.port">
                        </label>
                        <label class="hive-vault-field">
                            <span>Username <b aria-hidden="true">*</b></span>
                            <input type="text" placeholder="root" autocomplete="off" required x-model="form.username">
                        </label>
                        <fieldset class="hive-vault-field hive-vault-auth-field">
                            <legend>Authentication method <b aria-hidden="true">*</b></legend>
                            <div class="hive-vault-choice-group">
                                <button class="hive-vault-choice" type="button" x-bind:class="form.auth_method === 'private_key' ? 'is-active' : ''" x-bind:aria-pressed="form.auth_method === 'private_key'" x-on:click="setSshAuthMethod('private_key')">Private key</button>
                                <button class="hive-vault-choice" type="button" x-bind:class="form.auth_method === 'password' ? 'is-active' : ''" x-bind:aria-pressed="form.auth_method === 'password'" x-on:click="setSshAuthMethod('password')">Password</button>
                            </div>
                        </fieldset>
                        <label class="hive-vault-field hive-vault-field--wide" x-show="form.auth_method === 'private_key'">
                            <span>Private key <b aria-hidden="true">*</b></span>
                            <textarea class="is-monospace" rows="7" spellcheck="false" autocomplete="off" required x-model="form.private_key"></textarea>
                        </label>
                        <div class="hive-vault-field" x-show="form.auth_method === 'password'">
                            <label for="ssh-password">Password <b aria-hidden="true">*</b></label>
                            <span class="hive-vault-input-action">
                                <input id="ssh-password" x-bind:type="showFormPassword ? 'text' : 'password'" autocomplete="new-password" required x-model="form.password">
                                <button class="hive-vault-input-toggle" type="button" x-bind:aria-label="showFormPassword ? 'Hide password' : 'Show password'" x-on:click="showFormPassword = !showFormPassword"><span aria-hidden="true">◉</span><span x-text="showFormPassword ? 'Hide' : 'Show'">Show</span></button>
                                <button class="hive-vault-generator-trigger" type="button" x-bind:aria-expanded="generatorOpen" x-on:click="togglePasswordGenerator">Generate</button>
                            </span>
                            @include('livewire.vault-generator-panel', ['generatorContext' => 'inline', 'generatorId' => 'ssh'])
                        </div>
                    </div>

                    <div class="hive-vault-type-fields" x-show="form.type === 'database'">
                        <label class="hive-vault-field">
                            <span>Engine <b aria-hidden="true">*</b></span>
                            <select x-model="form.engine" required x-on:change="applyDatabasePort">
                                <option value="postgresql">PostgreSQL</option>
                                <option value="mysql">MySQL</option>
                                <option value="mariadb">MariaDB</option>
                                <option value="sqlite">SQLite</option>
                                <option value="redis">Redis</option>
                                <option value="mongodb">MongoDB</option>
                            </select>
                        </label>
                        <label class="hive-vault-field">
                            <span>Host <b aria-hidden="true">*</b></span>
                            <input type="text" autocomplete="off" required x-model="form.host">
                        </label>
                        <label class="hive-vault-field">
                            <span>Port <b aria-hidden="true">*</b></span>
                            <input type="number" min="0" max="65535" step="1" required x-model="form.port">
                        </label>
                        <label class="hive-vault-field">
                            <span>Database name <b aria-hidden="true">*</b></span>
                            <input type="text" autocomplete="off" required x-model="form.database_name">
                        </label>
                        <label class="hive-vault-field">
                            <span>Username <b aria-hidden="true">*</b></span>
                            <input type="text" autocomplete="off" required x-model="form.username">
                        </label>
                        <div class="hive-vault-field">
                            <label for="database-password">Password <b aria-hidden="true">*</b></label>
                            <span class="hive-vault-input-action">
                                <input id="database-password" x-bind:type="showFormPassword ? 'text' : 'password'" autocomplete="new-password" required x-model="form.password">
                                <button class="hive-vault-input-toggle" type="button" x-bind:aria-label="showFormPassword ? 'Hide password' : 'Show password'" x-on:click="showFormPassword = !showFormPassword"><span aria-hidden="true">◉</span><span x-text="showFormPassword ? 'Hide' : 'Show'">Show</span></button>
                                <button class="hive-vault-generator-trigger" type="button" x-bind:aria-expanded="generatorOpen" x-on:click="togglePasswordGenerator">Generate</button>
                            </span>
                            @include('livewire.vault-generator-panel', ['generatorContext' => 'inline', 'generatorId' => 'database'])
                        </div>
                    </div>

                    <div class="hive-vault-type-fields" x-show="form.type === 'note'">
                        <label class="hive-vault-field hive-vault-field--wide">
                            <span>Secure note content <b aria-hidden="true">*</b></span>
                            <textarea class="is-monospace" rows="9" spellcheck="false" required x-model="form.content"></textarea>
                        </label>
                    </div>

                    <label class="hive-vault-field hive-vault-field--wide">
                        <span>Additional note</span>
                        <textarea rows="3" x-model="form.notes"></textarea>
                    </label>
                </div>

                <ul class="hive-vault-validation-errors" role="alert" x-cloak x-show="formErrors.length">
                    <template x-for="error in formErrors" :key="error">
                        <li x-text="error"></li>
                    </template>
                </ul>
                <p class="hive-vault-inline-error" role="alert" x-cloak x-show="workspaceError" x-text="workspaceError"></p>

                <div class="hive-vault-editor-actions">
                    <button class="hive-vault-mini-button is-muted" type="button" x-on:click="cancelItemForm">Cancel</button>
                    <button class="hive-vault-button hive-vault-button--primary" type="button" x-bind:disabled="isSaving" x-on:click="saveCurrentItem">
                        <span x-text="isSaving ? 'Encrypting / Saving...' : 'Encrypt and save'">Encrypt and save</span>
                    </button>
                </div>
            </div>
        </template>
    </section>
</div>
