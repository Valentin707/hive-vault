import { decryptData, encryptData } from './vault-crypto';

const DATABASE_PORTS = {
    postgresql: '5432',
    mysql: '3306',
    mariadb: '3306',
    sqlite: '0',
    redis: '6379',
    mongodb: '27017',
};

function createEmptyItemForm(type = 'login') {
    return {
        id: null,
        type,
        title: '',
        folderId: '',
        isFavorite: false,
        username: '',
        password: '',
        url: '',
        host: '',
        port: type === 'ssh' ? '22' : (type === 'database' ? '5432' : ''),
        auth_method: 'private_key',
        private_key: '',
        engine: 'postgresql',
        database_name: '',
        content: '',
        notes: '',
    };
}

function safeExternalUrl(value) {
    try {
        const url = new URL(value);

        return ['http:', 'https:'].includes(url.protocol) ? url.href : '';
    } catch {
        return '';
    }
}

export function registerVaultWorkspace(Alpine) {
    Alpine.data('vaultWorkspace', () => ({
        cryptoKey: null,
        encryptedItems: [],
        items: [],
        folders: [],
        activeFilter: 'all',
        activeFolderId: null,
        searchQuery: '',
        selectedItem: null,
        inspectorMode: 'empty',
        form: createEmptyItemForm(),
        formErrors: [],
        workspaceError: '',
        isLoading: false,
        isSaving: false,
        isMutating: false,
        showFormPassword: false,
        revealedFields: {},
        copiedField: '',
        copiedTimeout: null,
        selectionVersion: 0,
        workspaceVersion: 0,
        folderEditorOpen: false,
        folderEditorId: null,
        folderNameInput: '',
        folderError: '',
        unlockedHandler: null,
        lockedHandler: null,

        init() {
            this.unlockedHandler = (event) => {
                this.handleVaultUnlocked(event.detail.cryptoKey);
            };
            this.lockedHandler = () => this.clearWorkspace();

            window.addEventListener('hive-vault-unlocked', this.unlockedHandler);
            window.addEventListener('hive-vault-locked', this.lockedHandler);
        },

        destroy() {
            window.removeEventListener('hive-vault-unlocked', this.unlockedHandler);
            window.removeEventListener('hive-vault-locked', this.lockedHandler);
            this.clearWorkspace();
        },

        get activeItemCount() {
            return this.items.filter((item) => !item.deleted_at).length;
        },

        get favoriteCount() {
            return this.items.filter((item) => !item.deleted_at && item.is_favorite).length;
        },

        get trashCount() {
            return this.items.filter((item) => Boolean(item.deleted_at)).length;
        },

        get visibleItems() {
            let results = this.items;

            if (this.activeFilter === 'trash') {
                results = results.filter((item) => Boolean(item.deleted_at));
            } else {
                results = results.filter((item) => !item.deleted_at);

                if (this.activeFilter === 'favorites') {
                    results = results.filter((item) => item.is_favorite);
                } else if (['login', 'ssh', 'database', 'note'].includes(this.activeFilter)) {
                    results = results.filter((item) => item.type === this.activeFilter);
                } else if (this.activeFilter === 'folder') {
                    results = results.filter((item) => item.folder_id === this.activeFolderId);
                }
            }

            const query = this.searchQuery.trim().toLocaleLowerCase();

            if (query) {
                results = results.filter((item) => item.title.toLocaleLowerCase().includes(query));
            }

            return [...results].sort((first, second) => first.title.localeCompare(second.title, undefined, { sensitivity: 'base' }));
        },

        get inspectorFields() {
            const payload = this.selectedItem?.payload;

            if (!payload || !this.selectedItem) {
                return [];
            }

            const fields = [];
            const addField = (key, label, value, options = {}) => {
                if (value === null || value === undefined || value === '') {
                    return;
                }

                fields.push({
                    key,
                    label,
                    value: String(value),
                    secret: Boolean(options.secret),
                    href: options.href ?? '',
                    monospace: Boolean(options.monospace),
                });
            };

            if (this.selectedItem.type === 'login') {
                addField('username', 'Username', payload.username);
                addField('password', 'Password', payload.password, { secret: true });
                addField('url', 'URL', payload.url, { href: safeExternalUrl(payload.url) });
            } else if (this.selectedItem.type === 'ssh') {
                addField('host', 'Host', payload.host);
                addField('port', 'Port', payload.port);
                addField('username', 'Username', payload.username);
                addField('auth_method', 'Authentication', payload.auth_method === 'private_key' ? 'Private key' : 'Password');

                if (payload.auth_method === 'private_key') {
                    addField('private_key', 'Private key', payload.private_key, { secret: true, monospace: true });
                } else {
                    addField('password', 'Password', payload.password, { secret: true });
                }
            } else if (this.selectedItem.type === 'database') {
                addField('engine', 'Engine', payload.engine);
                addField('host', 'Host', payload.host);
                addField('port', 'Port', payload.port);
                addField('database_name', 'Database', payload.database_name);
                addField('username', 'Username', payload.username);
                addField('password', 'Password', payload.password, { secret: true });
            } else if (this.selectedItem.type === 'note') {
                addField('content', 'Secure note', payload.content, { secret: true, monospace: true });
            }

            addField('notes', 'Additional note', payload.notes, { monospace: true });

            return fields;
        },

        handleVaultUnlocked(cryptoKey) {
            this.cryptoKey = cryptoKey;
            this.clearWorkspaceData();
            this.workspaceError = '';
            this.loadWorkspace();
        },

        clearWorkspace() {
            this.workspaceVersion += 1;
            this.cryptoKey = null;
            this.clearWorkspaceData();
            this.isLoading = false;
            this.isSaving = false;
            this.isMutating = false;
            this.workspaceError = '';
        },

        clearWorkspaceData() {
            this.selectionVersion += 1;
            window.clearTimeout(this.copiedTimeout);
            this.copiedTimeout = null;
            this.encryptedItems = [];
            this.items = [];
            this.folders = [];
            this.activeFilter = 'all';
            this.activeFolderId = null;
            this.searchQuery = '';
            this.selectedItem = null;
            this.inspectorMode = 'empty';
            this.form = createEmptyItemForm();
            this.formErrors = [];
            this.showFormPassword = false;
            this.revealedFields = {};
            this.copiedField = '';
            this.folderEditorOpen = false;
            this.folderEditorId = null;
            this.folderNameInput = '';
            this.folderError = '';
        },

        async loadWorkspace() {
            if (!this.cryptoKey) {
                return;
            }

            const cryptoKey = this.cryptoKey;
            const version = ++this.workspaceVersion;
            this.isLoading = true;

            try {
                const response = await this.$wire.loadWorkspace();

                if (version !== this.workspaceVersion || cryptoKey !== this.cryptoKey) {
                    return;
                }

                const encryptedFolders = Array.isArray(response?.folders) ? response.folders : [];
                const encryptedItems = Array.isArray(response?.items) ? response.items : [];
                let [folders, items] = await Promise.all([
                    Promise.all(encryptedFolders.map((folder) => this.decryptFolder(cryptoKey, folder))),
                    Promise.all(encryptedItems.map((item) => this.decryptItemTitle(cryptoKey, item))),
                ]);

                if (version !== this.workspaceVersion || cryptoKey !== this.cryptoKey) {
                    folders = [];
                    items = [];

                    return;
                }

                this.folders = folders;
                this.encryptedItems = encryptedItems;
                this.items = items;
            } catch {
                if (version === this.workspaceVersion) {
                    this.workspaceError = 'VAULT DATA COULD NOT BE LOADED. RECONNECT AND TRY AGAIN.';
                }
            } finally {
                if (version === this.workspaceVersion) {
                    this.isLoading = false;
                }
            }
        },

        async decryptFolder(cryptoKey, encryptedFolder) {
            try {
                const name = await decryptData(cryptoKey, encryptedFolder.encrypted_name, encryptedFolder.iv);

                return { ...encryptedFolder, name };
            } catch {
                return { ...encryptedFolder, name: 'UNREADABLE FOLDER' };
            }
        },

        async decryptItemTitle(cryptoKey, encryptedItem) {
            try {
                const title = await decryptData(cryptoKey, encryptedItem.encrypted_title, encryptedItem.title_iv);

                return { ...encryptedItem, title };
            } catch {
                return { ...encryptedItem, title: 'UNREADABLE ITEM' };
            }
        },

        selectFilter(filter) {
            this.activeFilter = filter;
            this.activeFolderId = null;
        },

        selectFolder(folderId) {
            this.activeFilter = 'folder';
            this.activeFolderId = folderId;
        },

        countForType(type) {
            return this.items.filter((item) => !item.deleted_at && item.type === type).length;
        },

        countForFolder(folderId) {
            return this.items.filter((item) => !item.deleted_at && item.folder_id === folderId).length;
        },

        isSelectedFilter(filter) {
            return this.activeFilter === filter && this.activeFolderId === null;
        },

        isSelectedFolder(folderId) {
            return this.activeFilter === 'folder' && this.activeFolderId === folderId;
        },

        isSelectedItem(itemId) {
            return this.selectedItem?.id === itemId;
        },

        typeLabel(type) {
            return {
                login: 'LOGIN',
                ssh: 'SSH',
                database: 'DB',
                note: 'NOTE',
            }[type] ?? 'ITEM';
        },

        typeIcon(type) {
            return {
                login: '◈',
                ssh: '⌘',
                database: '▤',
                note: '◇',
            }[type] ?? '◇';
        },

        folderName(folderId) {
            return this.folders.find((folder) => folder.id === folderId)?.name ?? 'Unfiled';
        },

        activeFilterLabel() {
            if (this.activeFilter === 'folder') {
                return this.folderName(this.activeFolderId);
            }

            return {
                all: 'All items',
                favorites: 'Favorites',
                trash: 'Trash',
                login: 'Logins',
                ssh: 'SSH keys',
                database: 'Databases',
                note: 'Secure notes',
            }[this.activeFilter] ?? 'All items';
        },

        async selectItem(itemId) {
            const item = this.items.find((candidate) => candidate.id === itemId);

            if (!item || !this.cryptoKey) {
                return;
            }

            const version = ++this.selectionVersion;
            const workspaceVersion = this.workspaceVersion;
            const cryptoKey = this.cryptoKey;
            this.form = createEmptyItemForm();
            this.formErrors = [];
            this.showFormPassword = false;
            this.selectedItem = { ...item, payload: null };
            this.inspectorMode = 'view';
            this.workspaceError = '';
            this.revealedFields = {};

            try {
                let plaintext = await decryptData(cryptoKey, item.encrypted_payload, item.payload_iv);

                if (version !== this.selectionVersion || cryptoKey !== this.cryptoKey) {
                    plaintext = '';

                    return;
                }

                let payload = JSON.parse(plaintext);
                plaintext = '';
                const selectedItem = { ...item, payload };
                this.selectedItem = selectedItem;

                this.$wire.logAccess(itemId, 'item_viewed')
                    .then((updatedItem) => this.updateItemMetadata(updatedItem, workspaceVersion, cryptoKey))
                    .catch(() => {});

                payload = null;
            } catch {
                if (version === this.selectionVersion) {
                    this.workspaceError = 'ITEM DECRYPTION FAILED // DATA MAY BE DAMAGED';
                }
            }
        },

        updateItemMetadata(encryptedItem, version, cryptoKey) {
            if (!encryptedItem || !this.isCurrentSession(version, cryptoKey)) {
                return;
            }

            this.encryptedItems = this.encryptedItems.map((item) => item.id === encryptedItem.id ? encryptedItem : item);
            this.items = this.items.map((item) => item.id === encryptedItem.id
                ? { ...item, ...encryptedItem, title: item.title }
                : item);

            if (this.selectedItem?.id === encryptedItem.id) {
                this.selectedItem = { ...this.selectedItem, ...encryptedItem, title: this.selectedItem.title, payload: this.selectedItem.payload };
            }
        },

        startNewEntry() {
            this.activeFilter = 'all';
            this.activeFolderId = null;
            this.selectedItem = null;
            this.inspectorMode = 'create';
            this.form = createEmptyItemForm();
            this.formErrors = [];
            this.workspaceError = '';
            this.showFormPassword = false;
        },

        changeItemType(type) {
            const current = this.form;
            this.form = {
                ...createEmptyItemForm(type),
                id: current.id,
                title: current.title,
                folderId: current.folderId,
                isFavorite: current.isFavorite,
            };
            this.formErrors = [];
        },

        setSshAuthMethod(method) {
            if (!['private_key', 'password'].includes(method)) {
                return;
            }

            this.form.auth_method = method;
            this.form.password = '';
            this.form.private_key = '';
            this.showFormPassword = false;
        },

        applyDatabasePort() {
            this.form.port = DATABASE_PORTS[this.form.engine] ?? '';
        },

        startEditing() {
            if (!this.selectedItem?.payload) {
                return;
            }

            const payload = this.selectedItem.payload;
            this.form = {
                ...createEmptyItemForm(this.selectedItem.type),
                id: this.selectedItem.id,
                title: this.selectedItem.title === 'UNREADABLE ITEM' ? '' : this.selectedItem.title,
                folderId: this.selectedItem.folder_id ?? '',
                isFavorite: this.selectedItem.is_favorite,
                ...payload,
            };
            this.inspectorMode = 'edit';
            this.formErrors = [];
            this.workspaceError = '';
            this.showFormPassword = false;
        },

        cancelItemForm() {
            this.formErrors = [];
            this.workspaceError = '';
            this.form = createEmptyItemForm();
            this.showFormPassword = false;
            this.inspectorMode = this.selectedItem ? 'view' : 'empty';
        },

        isCurrentSession(version, cryptoKey) {
            return version === this.workspaceVersion && cryptoKey === this.cryptoKey;
        },

        validateItemForm() {
            const errors = [];
            const form = this.form;
            const required = (value) => String(value ?? '').trim().length > 0;
            const requiredField = (value, label) => {
                if (!required(value)) {
                    errors.push(`${label} IS REQUIRED.`);
                }
            };
            const validPort = (value, allowZero = false) => {
                const port = Number(value);

                return required(value)
                    && Number.isInteger(port)
                    && port >= (allowZero ? 0 : 1)
                    && port <= 65535;
            };

            requiredField(form.title, 'TITLE');

            if (form.type === 'login') {
                requiredField(form.username, 'USERNAME');
                requiredField(form.password, 'PASSWORD');
                requiredField(form.url, 'URL');

                if (required(form.url)) {
                    const url = safeExternalUrl(form.url.trim());

                    if (!url) {
                        errors.push('ENTER A VALID HTTP OR HTTPS URL.');
                    }
                }
            } else if (form.type === 'ssh') {
                requiredField(form.host, 'HOST');
                requiredField(form.username, 'USERNAME');

                if (!validPort(form.port)) {
                    errors.push('PORT MUST BE BETWEEN 1 AND 65535.');
                }

                if (form.auth_method === 'private_key') {
                    requiredField(form.private_key, 'PRIVATE KEY');
                } else if (form.auth_method === 'password') {
                    requiredField(form.password, 'PASSWORD');
                } else {
                    errors.push('SELECT AN AUTHENTICATION METHOD.');
                }
            } else if (form.type === 'database') {
                requiredField(form.host, 'HOST');
                requiredField(form.database_name, 'DATABASE NAME');
                requiredField(form.username, 'USERNAME');
                requiredField(form.password, 'PASSWORD');

                if (!Object.hasOwn(DATABASE_PORTS, form.engine)) {
                    errors.push('SELECT A DATABASE ENGINE.');
                }

                if (!validPort(form.port, form.engine === 'sqlite')) {
                    errors.push('PORT MUST BE BETWEEN 1 AND 65535.');
                }
            } else if (form.type === 'note') {
                requiredField(form.content, 'SECURE NOTE CONTENT');
            } else {
                errors.push('SELECT A VALID ITEM TYPE.');
            }

            this.formErrors = errors;

            return errors.length === 0;
        },

        createItemPayload() {
            const form = this.form;
            const notes = form.notes;

            if (form.type === 'login') {
                return { username: form.username, password: form.password, url: form.url, notes };
            }

            if (form.type === 'ssh') {
                return {
                    host: form.host,
                    port: Number(form.port),
                    username: form.username,
                    auth_method: form.auth_method,
                    ...(form.auth_method === 'private_key' ? { private_key: form.private_key } : { password: form.password }),
                    notes,
                };
            }

            if (form.type === 'database') {
                return {
                    engine: form.engine,
                    host: form.host,
                    port: Number(form.port),
                    database_name: form.database_name,
                    username: form.username,
                    password: form.password,
                    notes,
                };
            }

            return { content: form.content, notes };
        },

        async saveCurrentItem() {
            if (this.isSaving || !this.cryptoKey || !this.validateItemForm()) {
                return;
            }

            this.isSaving = true;
            this.workspaceError = '';
            const version = this.workspaceVersion;
            const cryptoKey = this.cryptoKey;
            const itemId = this.form.id;
            const type = this.form.type;
            const folderId = this.form.folderId || null;
            const isFavorite = Boolean(this.form.isFavorite);
            let title = this.form.title.trim();
            let payload = null;
            let payloadJson = '';

            try {
                const encryptedTitle = await encryptData(cryptoKey, title);
                title = '';

                if (!this.isCurrentSession(version, cryptoKey)) {
                    payload = null;
                    payloadJson = '';

                    return;
                }

                payload = this.createItemPayload();
                payloadJson = JSON.stringify(payload);
                const encryptedPayload = await encryptData(cryptoKey, payloadJson);
                payload = null;
                payloadJson = '';

                if (!this.isCurrentSession(version, cryptoKey)) {
                    return;
                }

                const encryptedItem = await this.$wire.saveItem(
                    itemId,
                    type,
                    folderId,
                    isFavorite,
                    encryptedTitle.ciphertext,
                    encryptedTitle.iv,
                    encryptedPayload.ciphertext,
                    encryptedPayload.iv,
                );

                if (!this.isCurrentSession(version, cryptoKey)) {
                    return;
                }

                let decryptedTitle = await decryptData(cryptoKey, encryptedItem.encrypted_title, encryptedItem.title_iv);

                if (!this.isCurrentSession(version, cryptoKey)) {
                    decryptedTitle = '';
                    return;
                }

                let decryptedPayload = await decryptData(cryptoKey, encryptedItem.encrypted_payload, encryptedItem.payload_iv);

                if (!this.isCurrentSession(version, cryptoKey)) {
                    decryptedTitle = '';
                    decryptedPayload = '';
                    return;
                }

                let decryptedPayloadObject = JSON.parse(decryptedPayload);
                decryptedPayload = '';

                if (!this.isCurrentSession(version, cryptoKey)) {
                    decryptedTitle = '';
                    decryptedPayloadObject = null;
                    return;
                }

                const item = { ...encryptedItem, title: decryptedTitle };
                this.upsertItem(item);
                this.activeFilter = 'all';
                this.activeFolderId = null;
                this.selectedItem = { ...item, payload: decryptedPayloadObject };
                this.inspectorMode = 'view';
                this.revealedFields = {};
                this.form = createEmptyItemForm();
                decryptedTitle = '';
                decryptedPayloadObject = null;
            } catch {
                if (this.isCurrentSession(version, cryptoKey)) {
                    this.workspaceError = 'ITEM COULD NOT BE SAVED. CHECK YOUR CONNECTION AND TRY AGAIN.';
                }
            } finally {
                title = '';
                payload = null;
                payloadJson = '';

                if (this.isCurrentSession(version, cryptoKey)) {
                    this.isSaving = false;
                }
            }
        },

        upsertItem(item) {
            const index = this.items.findIndex((candidate) => candidate.id === item.id);

            if (index === -1) {
                this.items = [item, ...this.items];
                this.encryptedItems = [this.encryptedRecord(item), ...this.encryptedItems];

                return;
            }

            this.items = this.items.map((candidate) => candidate.id === item.id ? item : candidate);
            this.encryptedItems = this.encryptedItems.map((candidate) => candidate.id === item.id ? this.encryptedRecord(item) : candidate);
        },

        encryptedRecord(item) {
            const { title, payload, ...encryptedRecord } = item;

            return encryptedRecord;
        },

        syncItemRecord(encryptedItem) {
            const existing = this.items.find((item) => item.id === encryptedItem.id);

            if (!existing) {
                return;
            }

            const updatedItem = { ...existing, ...encryptedItem, title: existing.title };
            this.items = this.items.map((item) => item.id === encryptedItem.id ? updatedItem : item);
            this.encryptedItems = this.encryptedItems.map((item) => item.id === encryptedItem.id ? encryptedItem : item);

            if (this.selectedItem?.id === encryptedItem.id) {
                this.selectedItem = { ...this.selectedItem, ...encryptedItem, title: this.selectedItem.title, payload: this.selectedItem.payload };
            }
        },

        async toggleFavorite(itemId) {
            if (this.isMutating || !this.cryptoKey) {
                return;
            }

            this.isMutating = true;
            const version = this.workspaceVersion;
            const cryptoKey = this.cryptoKey;

            try {
                const updatedItem = await this.$wire.toggleFavorite(itemId);
                if (this.isCurrentSession(version, cryptoKey)) {
                    this.syncItemRecord(updatedItem);
                }
            } catch {
                if (this.isCurrentSession(version, cryptoKey)) {
                    this.workspaceError = 'FAVORITE STATUS COULD NOT BE UPDATED.';
                }
            } finally {
                if (this.isCurrentSession(version, cryptoKey)) {
                    this.isMutating = false;
                }
            }
        },

        async moveSelectedItemToTrash() {
            if (!this.selectedItem || this.isMutating) {
                return;
            }

            this.isMutating = true;
            this.workspaceError = '';
            const version = this.workspaceVersion;
            const cryptoKey = this.cryptoKey;
            const itemId = this.selectedItem.id;

            try {
                const updatedItem = await this.$wire.deleteItem(itemId);

                if (!this.isCurrentSession(version, cryptoKey)) {
                    return;
                }

                this.syncItemRecord(updatedItem);
                this.activeFilter = 'trash';
                this.activeFolderId = null;
                this.revealedFields = {};
            } catch {
                if (this.isCurrentSession(version, cryptoKey)) {
                    this.workspaceError = 'ITEM COULD NOT BE MOVED TO TRASH.';
                }
            } finally {
                if (this.isCurrentSession(version, cryptoKey)) {
                    this.isMutating = false;
                }
            }
        },

        async restoreSelectedItem() {
            if (!this.selectedItem || this.isMutating) {
                return;
            }

            this.isMutating = true;
            this.workspaceError = '';
            const version = this.workspaceVersion;
            const cryptoKey = this.cryptoKey;
            const itemId = this.selectedItem.id;

            try {
                const updatedItem = await this.$wire.restoreItem(itemId);

                if (!this.isCurrentSession(version, cryptoKey)) {
                    return;
                }

                this.syncItemRecord(updatedItem);
                this.activeFilter = 'all';
                this.activeFolderId = null;
            } catch {
                if (this.isCurrentSession(version, cryptoKey)) {
                    this.workspaceError = 'ITEM COULD NOT BE RESTORED.';
                }
            } finally {
                if (this.isCurrentSession(version, cryptoKey)) {
                    this.isMutating = false;
                }
            }
        },

        async permanentlyDeleteSelectedItem() {
            if (!this.selectedItem || this.isMutating) {
                return;
            }

            if (!window.confirm('PERMANENTLY DELETE THIS ITEM? THIS ACTION CANNOT BE UNDONE.')) {
                return;
            }

            const itemId = this.selectedItem.id;
            this.isMutating = true;
            this.workspaceError = '';
            const version = this.workspaceVersion;
            const cryptoKey = this.cryptoKey;

            try {
                await this.$wire.forceDeleteItem(itemId);

                if (!this.isCurrentSession(version, cryptoKey)) {
                    return;
                }

                this.items = this.items.filter((item) => item.id !== itemId);
                this.encryptedItems = this.encryptedItems.filter((item) => item.id !== itemId);
                this.selectedItem = null;
                this.inspectorMode = 'empty';
            } catch {
                if (this.isCurrentSession(version, cryptoKey)) {
                    this.workspaceError = 'ITEM COULD NOT BE PERMANENTLY DELETED.';
                }
            } finally {
                if (this.isCurrentSession(version, cryptoKey)) {
                    this.isMutating = false;
                }
            }
        },

        beginNewFolder() {
            this.folderEditorId = null;
            this.folderNameInput = '';
            this.folderError = '';
            this.folderEditorOpen = true;
        },

        beginFolderRename(folder) {
            this.folderEditorId = folder.id;
            this.folderNameInput = folder.name === 'UNREADABLE FOLDER' ? '' : folder.name;
            this.folderError = '';
            this.folderEditorOpen = true;
        },

        cancelFolderEdit() {
            this.folderEditorOpen = false;
            this.folderEditorId = null;
            this.folderNameInput = '';
            this.folderError = '';
        },

        async saveFolder() {
            let name = this.folderNameInput.trim();

            if (!name) {
                this.folderError = 'FOLDER NAME IS REQUIRED.';

                return;
            }

            if (!this.cryptoKey || this.isMutating) {
                return;
            }

            this.isMutating = true;
            this.folderError = '';
            const version = this.workspaceVersion;
            const cryptoKey = this.cryptoKey;
            const folderId = this.folderEditorId;
            let decryptedName = '';

            try {
                const encryptedName = await encryptData(cryptoKey, name);
                name = '';

                if (!this.isCurrentSession(version, cryptoKey)) {
                    return;
                }

                const encryptedFolder = await this.$wire.saveFolder(
                    folderId,
                    encryptedName.ciphertext,
                    encryptedName.iv,
                );

                if (!this.isCurrentSession(version, cryptoKey)) {
                    return;
                }

                decryptedName = await decryptData(cryptoKey, encryptedFolder.encrypted_name, encryptedFolder.iv);

                if (!this.isCurrentSession(version, cryptoKey)) {
                    return;
                }

                const folder = { ...encryptedFolder, name: decryptedName };
                const existingIndex = this.folders.findIndex((candidate) => candidate.id === folder.id);

                this.folders = existingIndex === -1
                    ? [...this.folders, folder]
                    : this.folders.map((candidate) => candidate.id === folder.id ? folder : candidate);
                this.folders.sort((first, second) => first.sort_order - second.sort_order || first.id.localeCompare(second.id));
                this.cancelFolderEdit();
            } catch {
                if (this.isCurrentSession(version, cryptoKey)) {
                    this.folderError = 'FOLDER COULD NOT BE SAVED.';
                }
            } finally {
                name = '';
                decryptedName = '';

                if (this.isCurrentSession(version, cryptoKey)) {
                    this.isMutating = false;
                }
            }
        },

        async deleteFolder(folder) {
            if (this.isMutating || !window.confirm('DELETE THIS FOLDER? ITEMS WILL BE MOVED TO UNFILED.')) {
                return;
            }

            const folderId = folder.id;
            folder = null;
            this.isMutating = true;
            this.folderError = '';
            const version = this.workspaceVersion;
            const cryptoKey = this.cryptoKey;

            try {
                await this.$wire.deleteFolder(folderId);

                if (!this.isCurrentSession(version, cryptoKey)) {
                    return;
                }

                this.folders = this.folders.filter((candidate) => candidate.id !== folderId);
                this.items = this.items.map((item) => item.folder_id === folderId ? { ...item, folder_id: null } : item);
                this.encryptedItems = this.encryptedItems.map((item) => item.folder_id === folderId ? { ...item, folder_id: null } : item);

                if (this.selectedItem?.folder_id === folderId) {
                    this.selectedItem = { ...this.selectedItem, folder_id: null };
                }

                if (this.activeFolderId === folderId) {
                    this.activeFilter = 'all';
                    this.activeFolderId = null;
                }

                if (this.folderEditorId === folderId) {
                    this.cancelFolderEdit();
                }
            } catch {
                if (this.isCurrentSession(version, cryptoKey)) {
                    this.folderError = 'FOLDER COULD NOT BE DELETED.';
                }
            } finally {
                if (this.isCurrentSession(version, cryptoKey)) {
                    this.isMutating = false;
                }
            }
        },

        async copyField(fieldKey, value) {
            if (!this.selectedItem || value === null || value === undefined || value === '') {
                return;
            }

            const itemId = this.selectedItem.id;
            const version = this.workspaceVersion;
            const cryptoKey = this.cryptoKey;
            let clipboardValue = String(value);

            try {
                await navigator.clipboard.writeText(clipboardValue);
                clipboardValue = '';

                this.$wire.logAccess(itemId, 'item_copied').catch(() => {});

                if (!this.isCurrentSession(version, cryptoKey)) {
                    return;
                }

                this.copiedField = fieldKey;
                window.clearTimeout(this.copiedTimeout);
                this.copiedTimeout = window.setTimeout(() => {
                    this.copiedField = '';
                }, 2000);
            } catch {
                if (this.isCurrentSession(version, cryptoKey)) {
                    this.workspaceError = 'CLIPBOARD ACCESS WAS DENIED BY THE BROWSER.';
                }
            } finally {
                clipboardValue = '';
            }
        },

        isFieldRevealed(fieldKey) {
            return this.revealedFields[fieldKey] === true;
        },

        toggleFieldReveal(fieldKey) {
            this.revealedFields = {
                ...this.revealedFields,
                [fieldKey]: !this.isFieldRevealed(fieldKey),
            };
        },

        formatTimestamp(value) {
            if (!value) {
                return 'NEVER';
            }

            const date = new Date(value);

            return Number.isNaN(date.getTime()) ? 'UNKNOWN' : date.toLocaleString();
        },
    }));
}
