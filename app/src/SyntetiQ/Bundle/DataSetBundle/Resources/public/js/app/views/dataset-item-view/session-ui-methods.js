export default {
    getLastUsedTitleStorageKey: function (options = null) {
        const resolvedOptions = options || this.options || {};
        const dataSetId = Number.parseInt(resolvedOptions.dataSetId, 10);

        if (Number.isInteger(dataSetId) && dataSetId > 0) {
            return `syntetiq.dataset-item-editor.last-used-title.${dataSetId}`;
        }

        return 'syntetiq.dataset-item-editor.last-used-title';
    },

    readStoredLastUsedTitle: function (options = null) {
        try {
            const value = window.sessionStorage.getItem(this.getLastUsedTitleStorageKey(options));
            return typeof value === 'string' ? value.trim() : '';
        } catch (error) {
            return '';
        }
    },

    persistLastUsedTitle: function (value) {
        const normalizedValue = typeof value === 'string' ? value.trim() : '';

        this.lastUsedTitle = normalizedValue;

        try {
            const storageKey = this.getLastUsedTitleStorageKey();
            if (normalizedValue) {
                window.sessionStorage.setItem(storageKey, normalizedValue);
            } else {
                window.sessionStorage.removeItem(storageKey);
            }
        } catch (error) {
            // Ignore sessionStorage failures and keep the in-memory fallback.
        }
    },

    handleLastUsedTitleChange: function (value) {
        const normalizedValue = typeof value === 'string' ? value.trim() : '';
        if (!normalizedValue || normalizedValue === this.lastUsedTitle) {
            return;
        }

        this.persistLastUsedTitle(normalizedValue);
    },

    syncPageHeader: function (payload) {
        if (!payload || !payload.itemId) {
            return;
        }

        document.querySelectorAll('.page-title__entity-title').forEach(element => {
            element.textContent = `#${payload.itemId}`;
        });
    },

    updateEditorHeight: function () {
        const container = this.el.querySelector('.svelte-container');
        if (!container) {
            return;
        }

        const rect = this.el.getBoundingClientRect();
        const bottomGap = window.innerWidth <= 1024 ? 16 : 24;
        const minHeight = window.innerWidth <= 1024 ? 420 : 520;
        const availableHeight = Math.floor(window.innerHeight - rect.top - bottomGap);

        container.style.height = Math.max(minHeight, availableHeight) + 'px';
    }
};
