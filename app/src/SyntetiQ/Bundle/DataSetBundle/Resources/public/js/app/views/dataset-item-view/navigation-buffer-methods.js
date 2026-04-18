const routing = require('routing');

export default {
    isDeletedItemId: function (itemId) {
        return Boolean(itemId && this.deletedItemIds && this.deletedItemIds.has(itemId));
    },

    getActionItemId: function (action) {
        if (action && action.params && action.params.id) {
            return Number.parseInt(action.params.id, 10);
        }

        if (action && action.url) {
            return this.extractItemIdFromUrl(action.url);
        }

        return null;
    },

    getActionUrl: function (action) {
        if (!action) {
            return null;
        }

        if (action.url) {
            return this.normalizeUrl(action.url);
        }

        if (!action.route) {
            return null;
        }

        return this.normalizeUrl(routing.generate(action.route, action.params || {}));
    },

    getEditorStateUrl: function (itemId, returnUrl) {
        if (!itemId) {
            return null;
        }

        return this.normalizeUrl(routing.generate('syntetiq_model_data_set_item_editor_state', {
            id: itemId,
            returnUrl
        }));
    },

    sanitizeNavigationAction: function (action, key, visitedItemIds = null) {
        const itemId = this.getActionItemId(action);
        if (!itemId) {
            return action || null;
        }

        if (!this.isDeletedItemId(itemId)) {
            return action;
        }

        const visited = visitedItemIds || new Set();
        if (visited.has(itemId)) {
            return null;
        }

        visited.add(itemId);

        const entry = this.itemCache.get(itemId);
        const fallbackAction = entry && entry.payload ? entry.payload[key] : null;

        return fallbackAction ? this.sanitizeNavigationAction(fallbackAction, key, visited) : null;
    },

    sanitizePayloadNavigation: function (payload) {
        if (!payload) {
            return payload;
        }

        return {
            ...payload,
            previousItemAction: this.sanitizeNavigationAction(payload.previousItemAction, 'previousItemAction'),
            nextItemAction: this.sanitizeNavigationAction(payload.nextItemAction, 'nextItemAction')
        };
    },

    markItemDeletedInBuffer: function (deletedPayload) {
        if (!deletedPayload || !deletedPayload.itemId) {
            return;
        }

        const deletedItemId = deletedPayload.itemId;
        this.deletedItemIds.add(deletedItemId);

        const replacementPreviousAction = this.sanitizeNavigationAction(
            deletedPayload.previousItemAction,
            'previousItemAction'
        );
        const replacementNextAction = this.sanitizeNavigationAction(
            deletedPayload.nextItemAction,
            'nextItemAction'
        );

        this.itemCache.forEach((entry, itemId) => {
            if (!entry || entry.status !== 'ready' || !entry.payload || itemId === deletedItemId) {
                return;
            }

            const cachedPayload = entry.payload;
            let updatedPayload = null;

            if (this.getActionItemId(cachedPayload.previousItemAction) === deletedItemId) {
                updatedPayload = {
                    ...(updatedPayload || cachedPayload),
                    previousItemAction: replacementPreviousAction
                };
            }

            if (this.getActionItemId(cachedPayload.nextItemAction) === deletedItemId) {
                updatedPayload = {
                    ...(updatedPayload || cachedPayload),
                    nextItemAction: replacementNextAction
                };
            }

            if (updatedPayload) {
                this.storePayload(updatedPayload);
            }
        });

        this.itemCache.delete(deletedItemId);
        this.pendingReadyUpdates.delete(deletedItemId);
        this.pendingSaveJobs.delete(deletedItemId);
        this.failedSaveJobs.delete(deletedItemId);
        this.pendingSaveOrder = this.pendingSaveOrder.filter(itemId => itemId !== deletedItemId);
    },

    getEditUrlForItem: function (itemId, baseUrl = null, returnUrl = null) {
        if (!itemId) {
            return null;
        }

        if (baseUrl) {
            try {
                const resolvedUrl = new URL(baseUrl, window.location.href);
                resolvedUrl.pathname = resolvedUrl.pathname.replace(/\/\d+\/edit(?:\/)?$/, `/${itemId}/edit`);

                if (returnUrl) {
                    resolvedUrl.searchParams.set('returnUrl', returnUrl);
                }

                return resolvedUrl.toString();
            } catch (error) {
                // Fall back to a deterministic URL below.
            }
        }

        const fallbackUrl = new URL(`/dataset-item/${itemId}/edit`, window.location.origin);
        if (returnUrl) {
            fallbackUrl.searchParams.set('returnUrl', returnUrl);
        }

        return fallbackUrl.toString();
    },

    getSegmentClickUrl: function (itemId) {
        if (!itemId) {
            return null;
        }

        return this.normalizeUrl(routing.generate('syntetiq_model_data_set_item_segment_click', {
            id: itemId
        }));
    },

    getReadyActionUrl: function (itemId, readyValue) {
        if (!itemId) {
            return null;
        }

        try {
            return this.normalizeUrl(routing.generate(
                readyValue ? 'syntetiq_model_data_set_item_mark_ready' : 'syntetiq_model_data_set_item_mark_not_ready',
                { id: itemId }
            ));
        } catch (error) {
            return this.normalizeUrl(`/dataset-item/${itemId}/${readyValue ? 'mark-ready' : 'mark-not-ready'}`);
        }
    },

    getEditorDeleteUrl: function (itemId) {
        if (!itemId) {
            return null;
        }

        try {
            return this.normalizeUrl(routing.generate('syntetiq_model_data_set_item_editor_delete', {
                id: itemId
            }));
        } catch (error) {
            return this.normalizeUrl(`/dataset-item/${itemId}/editor-delete`);
        }
    },

    preloadImage: function (imageSrc) {
        if (!imageSrc) {
            return Promise.resolve(null);
        }

        const existingEntry = this.imagePreloadCache.get(imageSrc);
        if (existingEntry && existingEntry.status === 'ready') {
            return Promise.resolve(existingEntry);
        }

        if (existingEntry && existingEntry.status === 'loading' && existingEntry.promise) {
            return existingEntry.promise;
        }

        const loadImage = (src, objectUrl = null) => new Promise(resolve => {
            const image = new Image();
            let isSettled = false;

            image.decoding = 'sync';

            const finalize = () => {
                if (isSettled) {
                    return;
                }

                isSettled = true;

                const entry = {
                    status: 'ready',
                    image,
                    objectUrl,
                    width: image.naturalWidth || 0,
                    height: image.naturalHeight || 0
                };

                this.imagePreloadCache.set(imageSrc, entry);
                resolve(entry);
            };

            const fail = () => {
                if (isSettled) {
                    return;
                }

                isSettled = true;

                if (objectUrl) {
                    URL.revokeObjectURL(objectUrl);
                }

                this.imagePreloadCache.delete(imageSrc);
                resolve(null);
            };

            const prepare = () => {
                if (typeof image.decode === 'function') {
                    image.decode().catch(() => undefined).finally(finalize);
                    return;
                }

                finalize();
            };

            image.onload = prepare;
            image.onerror = fail;
            image.src = src;

            if (image.complete && image.naturalWidth > 0) {
                prepare();
            } else if (image.complete) {
                fail();
            }
        });

        const promise = (/^(data:|blob:)/.test(imageSrc)
            ? loadImage(imageSrc)
            : fetch(imageSrc, { credentials: 'same-origin' })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Failed to preload image: ${response.status}`);
                    }

                    return response.blob();
                })
                .then(blob => {
                    const objectUrl = URL.createObjectURL(blob);
                    return loadImage(objectUrl, objectUrl);
                })
                .catch(() => loadImage(imageSrc)));

        this.imagePreloadCache.set(imageSrc, {
            status: 'loading',
            promise
        });

        return promise;
    },

    fetchPayloadByItemId: async function (itemId, returnUrl, force = false) {
        if (!itemId || this.isDeletedItemId(itemId)) {
            return null;
        }

        const existingEntry = itemId ? this.itemCache.get(itemId) : null;

        if (!force && existingEntry && existingEntry.status === 'ready' && existingEntry.payload) {
            return existingEntry.payload;
        }

        if (!force && existingEntry && existingEntry.status === 'loading' && existingEntry.promise) {
            return existingEntry.promise;
        }

        const url = this.getEditorStateUrl(itemId, returnUrl);
        if (!url || !itemId) {
            return null;
        }

        const promise = fetch(url, {
            credentials: 'same-origin'
        })
            .then(async response => {
                if (!response.ok) {
                    throw new Error(`Failed to fetch item ${itemId}: ${response.status}`);
                }

                const payload = await response.json();
                if (!payload || payload.itemId !== itemId) {
                    throw new Error(`Editor state payload for item ${itemId} is invalid`);
                }

                payload.serverGroupValue = payload.groupValue || null;
                payload.serverReadyValue = Boolean(payload.readyValue);
                payload.serverAnnotations = Array.isArray(payload.annotations)
                    ? payload.annotations.map(a => ({ ...a }))
                    : [];
                payload.isDirty = false;
                payload.readyDirty = false;

                const imageState = await this.preloadImage(payload.imageSrc);
                if (imageState) {
                    payload.bufferedImageSrc = imageState.objectUrl || payload.imageSrc;

                    if (!payload.imageDimensions) {
                        payload.imageDimensions = {
                            width: imageState.width,
                            height: imageState.height
                        };
                    }
                }

                return this.storePayload(payload);
            })
            .catch(error => {
                this.itemCache.delete(itemId);
                throw error;
            });

        this.itemCache.set(itemId, {
            status: 'loading',
            promise
        });

        return promise;
    },

    fetchPayloadForAction: async function (action) {
        const itemId = this.getActionItemId(action);
        const returnUrl = action && action.params && action.params.returnUrl
            ? action.params.returnUrl
            : this.resolveReturnUrl(this.options || {});

        return this.fetchPayloadByItemId(itemId, returnUrl);
    },

    preloadDirection: async function (action, steps, key) {
        let nextAction = this.sanitizeNavigationAction(action, key);

        for (let step = 0; step < steps && nextAction; step += 1) {
            const payload = await this.fetchPayloadForAction(nextAction).catch(() => null);
            if (!payload) {
                return;
            }

            nextAction = this.sanitizeNavigationAction(payload[key], key);
        }
    },

    preloadAroundPayload: function (payload) {
        if (!payload) {
            return Promise.resolve();
        }

        return Promise.all([
            this.preloadDirection(payload.previousItemAction, this.preloadRadius, 'previousItemAction'),
            this.preloadDirection(payload.nextItemAction, this.preloadRadius, 'nextItemAction')
        ]).finally(() => {
            this.trimBufferedCache(payload);
        });
    },

    collectRetainedActionItemIds: function (action, key, retainedItemIds) {
        let nextAction = this.sanitizeNavigationAction(action, key);

        for (let step = 0; step < this.preloadRadius && nextAction; step += 1) {
            const itemId = this.getActionItemId(nextAction);
            if (!itemId) {
                return;
            }

            retainedItemIds.add(itemId);

            const entry = this.itemCache.get(itemId);
            if (!entry || entry.status !== 'ready' || !entry.payload) {
                return;
            }

            nextAction = this.sanitizeNavigationAction(entry.payload[key], key);
        }
    },

    buildRetainedItemIds: function (centerPayload) {
        const retainedItemIds = new Set();

        if (centerPayload && centerPayload.itemId) {
            retainedItemIds.add(centerPayload.itemId);
            this.collectRetainedActionItemIds(centerPayload.previousItemAction, 'previousItemAction', retainedItemIds);
            this.collectRetainedActionItemIds(centerPayload.nextItemAction, 'nextItemAction', retainedItemIds);
        }

        if (this.currentItemId) {
            retainedItemIds.add(this.currentItemId);
        }

        this.pendingSaveOrder.forEach(itemId => retainedItemIds.add(itemId));
        this.pendingReadyUpdates.forEach((promise, itemId) => retainedItemIds.add(itemId));
        this.pendingSaveJobs.forEach((job, itemId) => retainedItemIds.add(itemId));
        this.failedSaveJobs.forEach((job, itemId) => retainedItemIds.add(itemId));

        return retainedItemIds;
    },

    trimBufferedCache: function (centerPayload) {
        const retainedItemIds = this.buildRetainedItemIds(centerPayload);

        this.itemCache.forEach((entry, itemId) => {
            if (!retainedItemIds.has(itemId)) {
                this.itemCache.delete(itemId);
                if (this.noSaveChangesMap) {
                    this.noSaveChangesMap.delete(itemId);
                }
            }
        });

        const retainedImageSrcs = new Set();
        retainedItemIds.forEach(itemId => {
            const entry = this.itemCache.get(itemId);
            const imageSrc = entry && entry.payload && entry.payload.imageSrc;
            if (imageSrc) {
                retainedImageSrcs.add(imageSrc);
            }
        });

        this.imagePreloadCache.forEach((entry, imageSrc) => {
            if (retainedImageSrcs.has(imageSrc)) {
                return;
            }

            if (entry && entry.image) {
                entry.image.onload = null;
                entry.image.onerror = null;
                entry.image.src = '';
            }

            if (entry && entry.objectUrl) {
                URL.revokeObjectURL(entry.objectUrl);
            }

            this.imagePreloadCache.delete(imageSrc);
        });
    }
};
