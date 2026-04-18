const DATA_URL_ATTRIBUTE = 'data-page-component-options';

export default {
    readPageComponentOptions: function (shell, fallbackOptions = null) {
        if (shell) {
            const rawOptions = shell.getAttribute(DATA_URL_ATTRIBUTE);
            if (rawOptions) {
                try {
                    return JSON.parse(rawOptions);
                } catch (e) {
                    console.warn('Failed to parse page component options from shell', e);
                }
            }
        }

        return fallbackOptions || {};
    },

    getGroupProps: function (options, root = document) {
        const fallbackOptions = Array.isArray(options.groupOptions)
            ? options.groupOptions
                .map(option => ({
                    value: option && typeof option.value === 'string' ? option.value : '',
                    label: option && typeof option.label === 'string' ? option.label : ''
                }))
                .filter(option => option.value && option.label)
            : [];
        const hasExplicitGroupValue = Object.prototype.hasOwnProperty.call(options || {}, 'groupValue');
        const explicitGroupValue = hasExplicitGroupValue && typeof options.groupValue === 'string' && options.groupValue !== ''
            ? options.groupValue
            : null;
        const select = options.groupInputSelector ? root.querySelector(options.groupInputSelector) : null;

        if (!select) {
            return {
                groupOptions: fallbackOptions,
                groupValue: explicitGroupValue
            };
        }

        if (fallbackOptions.length === 0) {
            return {
                groupOptions: Array.from(select.options)
                    .filter(option => option.value)
                    .map(option => ({
                        value: option.value,
                        label: option.textContent.trim()
                    })),
                groupValue: select.value || explicitGroupValue || null
            };
        }

        return {
            groupOptions: fallbackOptions,
            groupValue: select.value || explicitGroupValue || null
        };
    },

    getReadyProps: function (options, root = document) {
        const hasExplicitReadyValue = Object.prototype.hasOwnProperty.call(options || {}, 'readyValue');
        const explicitReadyValue = hasExplicitReadyValue ? Boolean(options.readyValue) : null;
        const readyInputSelector = options.readyInputSelector;

        if (!readyInputSelector) {
            return {
                readyValue: explicitReadyValue
            };
        }

        const input = root.querySelector(readyInputSelector);
        if (!input) {
            return {
                readyValue: explicitReadyValue
            };
        }

        return {
            readyValue: Boolean(input.checked)
        };
    },

    getTitleProps: function (options) {
        let titleOptions = options.titleOptions;

        if (typeof titleOptions === 'string') {
            try {
                titleOptions = JSON.parse(titleOptions);
            } catch (e) {
                titleOptions = [];
            }
        }

        titleOptions = Array.isArray(titleOptions) ? titleOptions : [];

        return {
            titleOptions: titleOptions
                .map(option => typeof option === 'string' ? option.trim() : '')
                .filter(Boolean)
        };
    },

    getNavigationProps: function (options) {
        const returnUrl = this.resolveReturnUrl(options);

        return {
            previousItemAction: this.parseActionOption(options.previousItemAction, returnUrl),
            nextItemAction: this.parseActionOption(options.nextItemAction, returnUrl)
        };
    },

    parseActionOption: function (value, returnUrl = null) {
        if (typeof value === 'string') {
            try {
                value = JSON.parse(value);
            } catch (e) {
                return null;
            }
        }

        if (!value || typeof value !== 'object') {
            return null;
        }

        const normalized = {
            ...value
        };

        if (normalized.params && typeof normalized.params === 'object' && !Array.isArray(normalized.params)) {
            normalized.params = {
                ...normalized.params
            };

            if (returnUrl && !normalized.params.returnUrl) {
                normalized.params.returnUrl = returnUrl;
            }
        }

        return normalized;
    },

    resolveReturnUrl: function (options) {
        if (options && typeof options.returnUrl === 'string' && options.returnUrl.trim() !== '') {
            return options.returnUrl.trim();
        }

        try {
            const currentUrl = new URL(window.location.href);
            const queryReturnUrl = currentUrl.searchParams.get('returnUrl');
            if (queryReturnUrl && queryReturnUrl.trim() !== '') {
                return queryReturnUrl.trim();
            }
        } catch (e) {
            // Ignore malformed browser URL state and fall back below.
        }

        if (document.referrer && document.referrer.trim() !== '') {
            return document.referrer.trim();
        }

        return null;
    },

    resolveImageSrc: function (root = document, fallback = null) {
        if (fallback) {
            return fallback;
        }

        const attachmentLink = root.querySelector('.attachment-item a');
        return attachmentLink ? attachmentLink.getAttribute('href') : null;
    },

    normalizeImageDimensions: function (dimensions) {
        if (!dimensions || typeof dimensions !== 'object') {
            return null;
        }

        const width = Number.parseInt(dimensions.width, 10);
        const height = Number.parseInt(dimensions.height, 10);

        if (!width || !height || width < 1 || height < 1) {
            return null;
        }

        return { width, height };
    },

    getImageDimensionsFromHiddenObjectInfo: function (hiddenObjectInfo) {
        if (!hiddenObjectInfo || typeof hiddenObjectInfo !== 'object') {
            return null;
        }

        return this.normalizeImageDimensions({
            width: hiddenObjectInfo.imgWidth,
            height: hiddenObjectInfo.imgHeight
        });
    },

    resolvePageFormAction: function (root, hiddenInput, fallbackUrl = null) {
        const form = (hiddenInput && hiddenInput.closest('form')) || root.querySelector('form');
        const action = form ? form.getAttribute('action') : null;

        return this.normalizeUrl(action || fallbackUrl || window.location.href);
    },

    normalizeUrl: function (url, base = window.location.href) {
        try {
            return new URL(url, base).toString();
        } catch (e) {
            return url;
        }
    },

    extractItemIdFromUrl: function (url) {
        if (!url) {
            return null;
        }

        const match = url.match(/\/(\d+)\/edit(?:[/?#]|$)/);
        return match ? Number.parseInt(match[1], 10) : null;
    },

    mapAreasToAnnotations: function (hiddenData) {
        const areas = hiddenData && Array.isArray(hiddenData.areas) ? hiddenData.areas : [];

        return areas.map((area, index) => {
            const mapped = {
                ...area,
                id: area.id || `area-${index}-${Date.now()}`,
                title: area.name || area.title || ''
            };

            if (hiddenData.imgWidth && hiddenData.imgHeight) {
                mapped.x = (area.x / hiddenData.imgWidth) * 100;
                mapped.y = (area.y / hiddenData.imgHeight) * 100;
                mapped.width = (area.width / hiddenData.imgWidth) * 100;
                mapped.height = (area.height / hiddenData.imgHeight) * 100;
            }

            return mapped;
        });
    },

    parseJsonValue: function (raw, fallback = {}) {
        if (typeof raw !== 'string' || raw.trim() === '') {
            return fallback;
        }

        try {
            return JSON.parse(raw);
        } catch (e) {
            return fallback;
        }
    },

    extractPayloadFromPage: function (root, shell, fallbackUrl, fallbackOptions = null) {
        const options = this.readPageComponentOptions(shell, fallbackOptions);
        const hiddenInputSelector = options.hiddenInputSelector || '.hidden-object-info';
        const hiddenInput = root.querySelector(hiddenInputSelector);
        const hiddenObjectInfo = this.parseJsonValue(hiddenInput ? hiddenInput.value : '', {});
        const groupProps = this.getGroupProps(options, root);
        const readyProps = this.getReadyProps(options, root);
        const titleProps = this.getTitleProps(options);
        const navigationProps = this.getNavigationProps(options);
        const editUrl = this.resolvePageFormAction(root, hiddenInput, fallbackUrl);

        const annotations = this.mapAreasToAnnotations(hiddenObjectInfo);

        return {
            itemId: this.extractItemIdFromUrl(editUrl),
            editUrl,
            returnUrl: this.resolveReturnUrl(options),
            canDeleteItem: Boolean(options.canDeleteItem),
            imageSrc: this.resolveImageSrc(root, options.imageSrc || null),
            imageDimensions: this.getImageDimensionsFromHiddenObjectInfo(hiddenObjectInfo),
            hiddenObjectInfo,
            annotations,
            serverAnnotations: annotations.map(a => ({ ...a })),
            groupOptions: groupProps.groupOptions || [],
            groupValue: groupProps.groupValue || null,
            serverGroupValue: groupProps.groupValue || null,
            readyValue: Boolean(readyProps.readyValue),
            serverReadyValue: Boolean(readyProps.readyValue),
            isDirty: false,
            readyDirty: false,
            titleOptions: titleProps.titleOptions || [],
            previousItemAction: navigationProps.previousItemAction,
            nextItemAction: navigationProps.nextItemAction
        };
    },

    parseEditorPageHtml: function (html, requestedUrl) {
        const parser = new DOMParser();
        const parsedDocument = parser.parseFromString(html, 'text/html');
        const shell = parsedDocument.querySelector('.dataset-item-editor-shell');

        if (!shell) {
            throw new Error('Could not find dataset item editor shell in fetched page');
        }

        return {
            document: parsedDocument,
            payload: this.extractPayloadFromPage(parsedDocument, shell, requestedUrl)
        };
    },

    pageHasValidationErrors: function (root) {
        return Boolean(
            root.querySelector(
                '.validation-failed, .has-error, .alert.alert-error, .validation-error, .notification.notification-error'
            )
        );
    },

    getFormElement: function () {
        return this.el.closest('form');
    },

    getCurrentFormAction: function () {
        const form = this.getFormElement();
        return form ? this.normalizeUrl(form.getAttribute('action') || window.location.href) : window.location.href;
    },

    getHiddenInputElement: function () {
        const selector = (this.options && this.options.hiddenInputSelector) || '.hidden-object-info';
        return document.querySelector(selector);
    },

    getGroupInputElement: function () {
        const selector = this.options && this.options.groupInputSelector;
        return selector ? document.querySelector(selector) : null;
    },

    getReadyInputElement: function () {
        const selector = this.options && this.options.readyInputSelector;
        return selector ? document.querySelector(selector) : null;
    },

    getReadyDirtyInputElement: function () {
        const selector = this.options && this.options.readyDirtyInputSelector;
        return selector ? document.querySelector(selector) : null;
    },

    getCurrentPayload: function () {
        const entry = this.currentItemId ? this.itemCache.get(this.currentItemId) : null;
        if (entry && entry.payload) {
            return entry.payload;
        }

        if (this.draftPayload) {
            return this.draftPayload;
        }

        const payload = this.extractPayloadFromPage(document, this.el, this.getCurrentFormAction(), this.options || {});
        if (payload && payload.itemId) {
            return payload;
        }

        if (payload) {
            this.draftPayload = payload;
            return payload;
        }

        return null;
    },

    storePayload: function (payload) {
        if (!payload) {
            return null;
        }

        if (!payload.itemId) {
            this.draftPayload = {
                ...(this.draftPayload || {}),
                ...payload
            };

            return this.draftPayload;
        }

        const entry = this.itemCache.get(payload.itemId) || {};
        const cachedPayload = entry.payload || null;
        const nextPayload = {
            ...(cachedPayload || {}),
            ...payload
        };

        nextPayload.imageDimensions = this.normalizeImageDimensions(payload.imageDimensions)
            || this.getImageDimensionsFromHiddenObjectInfo(payload.hiddenObjectInfo)
            || this.normalizeImageDimensions(cachedPayload && cachedPayload.imageDimensions)
            || this.getImageDimensionsFromHiddenObjectInfo(cachedPayload && cachedPayload.hiddenObjectInfo);
        nextPayload.serverGroupValue = Object.prototype.hasOwnProperty.call(payload, 'serverGroupValue')
            ? payload.serverGroupValue
            : (cachedPayload ? cachedPayload.serverGroupValue : null);
        nextPayload.serverReadyValue = Object.prototype.hasOwnProperty.call(payload, 'serverReadyValue')
            ? Boolean(payload.serverReadyValue)
            : Boolean(cachedPayload && cachedPayload.serverReadyValue);
        nextPayload.isDirty = Object.prototype.hasOwnProperty.call(payload, 'isDirty')
            ? Boolean(payload.isDirty)
            : Boolean(cachedPayload && cachedPayload.isDirty);
        nextPayload.readyDirty = Object.prototype.hasOwnProperty.call(payload, 'readyDirty')
            ? Boolean(payload.readyDirty)
            : Boolean(cachedPayload && cachedPayload.readyDirty);
        nextPayload.previousItemAction = this.sanitizeNavigationAction(
            Object.prototype.hasOwnProperty.call(payload, 'previousItemAction')
                ? payload.previousItemAction
                : (cachedPayload ? cachedPayload.previousItemAction : null),
            'previousItemAction'
        );
        nextPayload.nextItemAction = this.sanitizeNavigationAction(
            Object.prototype.hasOwnProperty.call(payload, 'nextItemAction')
                ? payload.nextItemAction
                : (cachedPayload ? cachedPayload.nextItemAction : null),
            'nextItemAction'
        );

        entry.status = 'ready';
        entry.payload = nextPayload;
        entry.promise = Promise.resolve(nextPayload);
        this.itemCache.set(payload.itemId, entry);
        this.draftPayload = null;

        return nextPayload;
    },

    updateShellOptionsFromPayload: function (payload) {
        payload = this.sanitizePayloadNavigation(payload || {});

        const options = {
            ...(this.options || {}),
            groupOptions: payload.groupOptions || [],
            groupValue: payload.groupValue || null,
            readyValue: Boolean(payload.readyValue),
            canDeleteItem: Boolean(payload.canDeleteItem),
            imageSrc: payload.imageSrc,
            titleOptions: payload.titleOptions,
            previousItemAction: payload.previousItemAction,
            nextItemAction: payload.nextItemAction
        };

        this.el.setAttribute(DATA_URL_ATTRIBUTE, JSON.stringify(options));
    },

    buildSvelteProps: function (payload) {
        payload = this.sanitizePayloadNavigation(payload || {});

        const noSaveState = this.noSaveChangesMap && this.noSaveChangesMap.get(payload.itemId);

        // Always provide server originals so the Svelte component can use them as
        // the undo target. When there are buffered no-save changes the component
        // receives the modified state as normal props and the server state here.
        // payload.serverAnnotations is snapshotted at fetch time; fall back to
        // payload.annotations for items loaded from the initial page HTML which
        // do not go through fetchPayloadByItemId.
        const serverAnnotations = payload.serverAnnotations || payload.annotations || [];
        const serverGroupValue = payload.serverGroupValue || payload.groupValue || null;
        const serverReadyValue = Object.prototype.hasOwnProperty.call(payload, 'serverReadyValue')
            ? Boolean(payload.serverReadyValue)
            : Boolean(payload.readyValue);

        return {
            imageSrc: payload.bufferedImageSrc || payload.imageSrc,
            initialImageDimensions: payload.imageDimensions || null,
            annotations: noSaveState ? noSaveState.annotations : serverAnnotations,
            groupOptions: payload.groupOptions || [],
            groupValue: noSaveState ? noSaveState.groupValue : serverGroupValue,
            readyValue: noSaveState ? noSaveState.readyValue : serverReadyValue,
            serverAnnotations,
            serverGroupValue,
            serverReadyValue,
            titleOptions: payload.titleOptions || [],
            lastUsedTitle: this.lastUsedTitle || null,
            previousItemAction: payload.previousItemAction || null,
            nextItemAction: payload.nextItemAction || null,
            autoDetectRequest: payload && payload.itemId ? this.autoDetectRequestHandler : null,
            canDeleteItem: Boolean(payload && payload.itemId && payload.canDeleteItem)
        };
    }
};
