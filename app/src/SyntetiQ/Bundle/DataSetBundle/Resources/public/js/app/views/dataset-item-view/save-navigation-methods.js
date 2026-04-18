import $ from 'jquery';
import messenger from 'oroui/js/messenger';

export default {
    handleGroupChange: function (value) {
        if (!value) {
            return;
        }

        const select = this.getGroupInputElement();
        const currentPayload = this.getCurrentPayload();
        this.formHasUnsavedChanges = true;

        if (select && select.value !== value) {
            select.value = value;
            $(select).trigger('change');
        }

        if (currentPayload) {
            currentPayload.groupValue = value;
            if (currentPayload.serverGroupValue !== value) {
                currentPayload.isDirty = true;
            }
            this.storePayload(currentPayload);
            this.updateShellOptionsFromPayload(currentPayload);
        }
    },

    handleReadyChange: function (value) {
        const currentPayload = this.getCurrentPayload();
        if (!currentPayload || !currentPayload.itemId) {
            return;
        }

        const itemId = currentPayload.itemId;
        const nextValue = Boolean(value);
        const previousServerValue = Boolean(currentPayload.serverReadyValue);

        // In no-save mode: update local payload only, skip server PATCH.
        // Still set readyDirty so that if the user later switches back to
        // auto-save and submits the form (or navigates), the backend knows
        // the ready status needs to be persisted.
        if (!this.autoSaveEnabled) {
            currentPayload.readyValue = nextValue;
            currentPayload.readyDirty = nextValue !== previousServerValue;
            this.storePayload(currentPayload);
            this.applyPayloadToForm(currentPayload);
            return;
        }

        const readyActionUrl = this.getReadyActionUrl(itemId, nextValue);
        if (!readyActionUrl) {
            messenger.notificationFlashMessage('error', 'Could not resolve the ready toggle endpoint for this item.');
            return;
        }

        currentPayload.readyValue = nextValue;
        currentPayload.serverReadyValue = previousServerValue;
        currentPayload.readyDirty = false;
        this.storePayload(currentPayload);
        this.applyPayloadToForm(currentPayload);

        const previousPendingUpdate = this.pendingReadyUpdates.get(itemId) || Promise.resolve();
        const readyUpdatePromise = previousPendingUpdate
            .catch(() => undefined)
            .then(() => fetch(readyActionUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }))
            .then(async response => {
                if (!response.ok) {
                    throw new Error(`Failed to update ready state for item ${itemId}: ${response.status}`);
                }

                const cachedEntry = this.itemCache.get(itemId);
                const cachedPayload = cachedEntry && cachedEntry.payload ? cachedEntry.payload : currentPayload;
                const updatedPayload = {
                    ...cachedPayload,
                    readyValue: nextValue,
                    serverReadyValue: nextValue,
                    readyDirty: false
                };

                this.storePayload(updatedPayload);

                if (this.currentItemId === itemId) {
                    this.applyPayloadToForm(updatedPayload);
                }
            })
            .catch(error => {
                console.error('Failed to update ready state', error);

                const cachedEntry = this.itemCache.get(itemId);
                const cachedPayload = cachedEntry && cachedEntry.payload ? cachedEntry.payload : currentPayload;
                const revertedPayload = {
                    ...cachedPayload,
                    readyValue: previousServerValue,
                    serverReadyValue: previousServerValue,
                    readyDirty: false
                };

                this.storePayload(revertedPayload);

                if (this.currentItemId === itemId) {
                    this.applyPayloadToForm(revertedPayload);
                }

                messenger.notificationFlashMessage('error', 'Could not update ready state for this item.');
                throw error;
            })
            .finally(() => {
                if (this.pendingReadyUpdates.get(itemId) === readyUpdatePromise) {
                    this.pendingReadyUpdates.delete(itemId);
                }
            });

        this.pendingReadyUpdates.set(itemId, readyUpdatePromise);
    },

    handleAutoSaveChange: function (enabled) {
        this.autoSaveEnabled = enabled;

        // When switching back to save mode, flush any ready change that was
        // deferred while in no-save mode. We compare readyValue against
        // serverReadyValue – if they differ the user made a change that was
        // never PATCHed to the server.
        if (enabled) {
            const currentPayload = this.getCurrentPayload();
            if (currentPayload && currentPayload.itemId
                && Boolean(currentPayload.readyValue) !== Boolean(currentPayload.serverReadyValue)
                && !this.pendingReadyUpdates.has(currentPayload.itemId)) {
                this.handleReadyChange(currentPayload.readyValue);
            }
        }

        const outerSaveCloseButton = document.querySelector('.dropdown-menu [data-action="save_and_close"]') || document.querySelector('.btn-success.action-btn');
        if (outerSaveCloseButton) {
            outerSaveCloseButton.textContent = enabled ? 'Save and Close' : 'Close';
            
            // Manage custom click interception for 'Close without Saving'
            if (!this.autoSaveCloseHandlerBound) {
                this.autoSaveCloseHandler = (e) => {
                    if (this.autoSaveEnabled === false) {
                        e.preventDefault();
                        e.stopPropagation();
                        // Get return URL and redirect
                        const returnUrlInput = document.querySelector('input[name="returnUrlState"]');
                        if (returnUrlInput && returnUrlInput.value) {
                            window.location.assign(returnUrlInput.value);
                        } else {
                            window.history.back();
                        }
                    }
                };
                outerSaveCloseButton.addEventListener('click', this.autoSaveCloseHandler, true);
                this.autoSaveCloseHandlerBound = true;
            }
        }
    },

    flushPendingReadyUpdate: async function (itemId) {
        const pendingUpdate = itemId ? this.pendingReadyUpdates.get(itemId) : null;
        if (!pendingUpdate) {
            return true;
        }

        try {
            await pendingUpdate;

            return true;
        } catch (error) {
            return false;
        }
    },

    flushPendingReadyUpdates: async function () {
        if (this.pendingReadyUpdates.size === 0) {
            return true;
        }

        const results = await Promise.allSettled(this.pendingReadyUpdates.values());

        return results.every(result => result.status === 'fulfilled');
    },

    captureCurrentSaveJob: function (payload) {
        const form = this.getFormElement();
        if (!form || !payload || !payload.itemId) {
            return null;
        }

        const payloadSnapshot = this.createPayloadSnapshot(payload);
        const groupInput = this.getGroupInputElement();
        const payloadGroupValue = payloadSnapshot.groupValue || '';
        if (groupInput && groupInput.value !== payloadGroupValue) {
            groupInput.value = payloadGroupValue;
        }

        const normalizedGroupValue = payloadGroupValue;
        const serverGroupValue = payloadSnapshot.serverGroupValue || '';

        payloadSnapshot.groupValue = normalizedGroupValue || null;
        if (normalizedGroupValue !== serverGroupValue) {
            payloadSnapshot.isDirty = true;
        }

        if (!payloadSnapshot.isDirty && !this.formHasUnsavedChanges) {
            return null;
        }

        const formData = new FormData(form);
        formData.delete('input_action');
        this.normalizeSaveFormData(formData, payloadSnapshot);

        this.storePayload(payloadSnapshot);

        return {
            itemId: payloadSnapshot.itemId,
            editUrl: this.getEditUrlForItem(payloadSnapshot.itemId, payloadSnapshot.editUrl, payloadSnapshot.returnUrl),
            formData,
            payload: payloadSnapshot
        };
    },

    createPayloadSnapshot: function (payload) {
        return JSON.parse(JSON.stringify(payload));
    },

    normalizeSaveFormData: function (formData, payload) {
        if (!formData || !payload) {
            return;
        }

        const hiddenInput = this.getHiddenInputElement();
        if (hiddenInput && hiddenInput.name) {
            formData.delete(hiddenInput.name);
            formData.append(hiddenInput.name, JSON.stringify(payload.hiddenObjectInfo || {}));
        }

        const groupInput = this.getGroupInputElement();
        if (groupInput && groupInput.name) {
            formData.delete(groupInput.name);
            formData.append(groupInput.name, payload.groupValue || '');
        }

        const readyInput = this.getReadyInputElement();
        if (readyInput && readyInput.name) {
            formData.delete(readyInput.name);
            formData.append(readyInput.name, payload.serverReadyValue ? '1' : '0');
        }

        const readyDirtyInput = this.getReadyDirtyInputElement();
        if (readyDirtyInput && readyDirtyInput.name) {
            formData.delete(readyDirtyInput.name);
            formData.append(readyDirtyInput.name, '0');
        }
    },

    submitNavigationFallback: function (action) {
        if (!action || !action.route) {
            return;
        }

        const returnUrl = this.resolveReturnUrl(this.options || {});
        const normalizedAction = {
            ...action,
            params: {
                ...(action.params || {})
            }
        };

        if (returnUrl && !normalizedAction.params.returnUrl) {
            normalizedAction.params.returnUrl = returnUrl;
        }

        const form = this.getFormElement();
        if (!form) {
            return;
        }

        const actionInput = form.querySelector('input[name="input_action"]');
        if (!actionInput) {
            return;
        }

        actionInput.value = JSON.stringify(normalizedAction);
        this.isResubmittingForm = true;

        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    },

    enqueueBackgroundSave: function (job) {
        if (!job || !job.itemId) {
            return;
        }

        this.failedSaveJobs.delete(job.itemId);

        if (!this.pendingSaveJobs.has(job.itemId)) {
            this.pendingSaveOrder.push(job.itemId);
        }

        this.pendingSaveJobs.set(job.itemId, job);
        this.processBackgroundSaveQueue();
    },

    processBackgroundSaveQueue: function () {
        if (this.saveProcessorPromise) {
            return this.saveProcessorPromise;
        }

        this.saveProcessorPromise = (async () => {
            while (this.pendingSaveOrder.length > 0) {
                const itemId = this.pendingSaveOrder.shift();
                const job = this.pendingSaveJobs.get(itemId);
                this.pendingSaveJobs.delete(itemId);

                if (!job) {
                    continue;
                }

                this.isSavingInBackground = true;

                try {
                    await this.saveItemInBackground(job);
                } catch (error) {
                    console.error(`Background save failed for item ${itemId}`, error);
                    this.failedSaveJobs.set(itemId, job);
                    messenger.notificationFlashMessage(
                        'error',
                        `Could not save item ${itemId} in the background. It will be retried before leaving the page.`
                    );
                } finally {
                    this.isSavingInBackground = false;
                }
            }
        })().finally(() => {
            this.saveProcessorPromise = null;
            this.isSavingInBackground = false;
        });

        return this.saveProcessorPromise;
    },

    saveItemInBackground: async function (job) {
        const response = await fetch(job.editUrl, {
            method: 'POST',
            body: job.formData,
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(`Failed to save item ${job.itemId}: ${response.status}`);
        }

        await this.fetchPayloadByItemId(job.itemId, job.payload.returnUrl || this.resolveReturnUrl(this.options || {}), true);
    },

    flushBackgroundSaves: async function () {
        await this.processBackgroundSaveQueue();

        if (this.failedSaveJobs.size === 0) {
            return true;
        }

        const retryJobs = Array.from(this.failedSaveJobs.values());
        this.failedSaveJobs.clear();

        retryJobs.forEach(job => {
            if (!this.pendingSaveJobs.has(job.itemId)) {
                this.pendingSaveOrder.push(job.itemId);
            }

            this.pendingSaveJobs.set(job.itemId, job);
        });

        await this.processBackgroundSaveQueue();

        return this.failedSaveJobs.size === 0;
    },

    hasPendingSaves: function () {
        if (this.pendingReadyUpdates.size > 0) {
            return true;
        }

        return this.isSavingInBackground
            || this.pendingSaveOrder.length > 0
            || this.pendingSaveJobs.size > 0
            || this.failedSaveJobs.size > 0;
    },

    handleBeforeUnload: function (event) {
        if (!this.hasPendingSaves()) {
            return undefined;
        }

        event.preventDefault();
        event.returnValue = '';

        return '';
    },

    handleFormSubmit: function (event) {
        // In no-save mode: intercept any form submit and redirect without saving
        if (!this.autoSaveEnabled) {
            event.preventDefault();
            const returnUrlInput = document.querySelector('input[name="returnUrlState"]');
            if (returnUrlInput && returnUrlInput.value) {
                window.location.assign(returnUrlInput.value);
            } else {
                window.history.back();
            }
            return;
        }

        const currentPayload = this.getCurrentPayload();
        if (currentPayload) {
            this.applyPayloadToForm(currentPayload);
        }

        if (this.isResubmittingForm || !this.hasPendingSaves()) {
            return;
        }

        event.preventDefault();

        const form = event.target;
        const submitter = event.submitter || null;

        this.flushPendingReadyUpdates().then(success => {
            if (!success) {
                messenger.notificationFlashMessage(
                    'error',
                    'Could not finish ready state updates before submitting the form.'
                );
                return false;
            }

            return this.flushBackgroundSaves();
        }).then(success => {
            if (success === false) {
                return;
            }

            if (!success) {
                messenger.notificationFlashMessage(
                    'error',
                    'Some buffered item saves are still failing. Please retry navigation once more before leaving the page.'
                );
                return;
            }

            this.isResubmittingForm = true;

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit(submitter || undefined);
            } else {
                form.submit();
            }

            window.setTimeout(() => {
                this.isResubmittingForm = false;
            }, 0);
        }).catch(error => {
            console.error('Failed to flush buffered saves before submit', error);
            messenger.notificationFlashMessage('error', 'Could not finish buffered saves before submitting the form.');
        });
    },

    applyPayloadToForm: function (payload) {
        const hiddenInput = this.getHiddenInputElement();
        if (hiddenInput) {
            hiddenInput.value = JSON.stringify(payload.hiddenObjectInfo || {});
        }

        const returnUrlInput = document.querySelector('input[name="returnUrlState"]');
        if (returnUrlInput && payload.returnUrl) {
            returnUrlInput.value = payload.returnUrl;
        }

        const groupInput = this.getGroupInputElement();
        if (groupInput) {
            groupInput.value = payload.groupValue || '';
        }

        const readyInput = this.getReadyInputElement();
        if (readyInput) {
            readyInput.checked = Boolean(payload.readyValue);
        }

        const readyDirtyInput = this.getReadyDirtyInputElement();
        if (readyDirtyInput) {
            readyDirtyInput.value = payload.readyDirty ? '1' : '0';
        }

        const form = this.getFormElement();
        if (form) {
            if (payload.editUrl) {
                form.setAttribute('action', payload.editUrl);
            }
        }

        this.updateShellOptionsFromPayload(payload);
    },

    switchToPayload: function (payload) {
        if (!payload) {
            return;
        }

        this.currentItemId = payload.itemId;
        this.draftPayload = null;
        this.formHasUnsavedChanges = false;
        this.storePayload(payload);
        this.applyPayloadToForm(payload);
        this.syncPageHeader(payload);

        const form = this.getFormElement();
        if (form) {
            form.querySelectorAll('.input-widget-file input[type="file"]').forEach(input => {
                input.value = '';
            });

            const actionInput = form.querySelector('input[name="input_action"]');
            if (actionInput) {
                actionInput.value = '';
            }
        }

        const container = this.el.querySelector('.svelte-container');
        if (container && this.mountApp) {
            this.mountSvelteApp(container, this.buildSvelteProps(payload));
            this.resizeHandler();
        }

        window.history.replaceState(window.history.state, '', payload.editUrl);
    },

    handleDeleteItem: async function () {
        if (this.isDeletingItem || this.isNavigating) {
            return;
        }

        const currentPayload = this.getCurrentPayload();
        if (!currentPayload || !currentPayload.itemId || !currentPayload.canDeleteItem) {
            return;
        }

        if (!window.confirm('Delete this item? This cannot be undone.')) {
            return;
        }

        this.isDeletingItem = true;
        const targetAction = this.sanitizeNavigationAction(currentPayload.nextItemAction, 'nextItemAction')
            || this.sanitizeNavigationAction(currentPayload.previousItemAction, 'previousItemAction')
            || null;
        const targetItemId = targetAction ? this.getActionItemId(targetAction) : null;
        const targetReturnUrl = targetAction && targetAction.params && targetAction.params.returnUrl
            ? targetAction.params.returnUrl
            : (currentPayload.returnUrl || this.resolveReturnUrl(this.options || {}));

        try {
            const readyUpdatesSucceeded = await this.flushPendingReadyUpdates();
            if (!readyUpdatesSucceeded) {
                messenger.notificationFlashMessage('error', 'Could not finish ready state updates before deleting the item.');
                return;
            }

            const bufferedSavesSucceeded = await this.flushBackgroundSaves();
            if (!bufferedSavesSucceeded) {
                messenger.notificationFlashMessage(
                    'error',
                    'Some buffered item saves are still failing. Please retry deleting once more.'
                );
                return;
            }

            const deleteUrl = this.getEditorDeleteUrl(currentPayload.itemId);
            if (!deleteUrl) {
                messenger.notificationFlashMessage('error', 'Could not resolve the delete endpoint for this item.');
                return;
            }

            if (targetItemId) {
                const targetPayload = await this.fetchPayloadByItemId(targetItemId, targetReturnUrl);

                if (!targetPayload) {
                    throw new Error('Could not load the adjacent item before deletion.');
                }

                this.markItemDeletedInBuffer(currentPayload);

                const adjustedTargetPayload = this.sanitizePayloadNavigation(
                    this.itemCache.get(targetPayload.itemId)?.payload || targetPayload
                );

                this.switchToPayload(adjustedTargetPayload);
                this.preloadAroundPayload(adjustedTargetPayload);

                fetch(deleteUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    keepalive: true,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        returnUrl: currentPayload.returnUrl || this.resolveReturnUrl(this.options || {})
                    })
                })
                    .then(async response => {
                        const responseData = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            throw new Error(responseData.message || `Failed to delete item ${currentPayload.itemId}: ${response.status}`);
                        }

                        messenger.notificationFlashMessage(
                            'success',
                            responseData.message || 'Item deleted.'
                        );
                    })
                    .catch(error => {
                        console.error('Failed to delete dataset item in background', error);
                        messenger.notificationFlashMessage(
                            'error',
                            'Delete failed in background. Reload the page to refresh item navigation.'
                        );
                    });

                return;
            }

            const response = await fetch(deleteUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    returnUrl: currentPayload.returnUrl || this.resolveReturnUrl(this.options || {})
                })
            });

            const responseData = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(responseData.message || `Failed to delete item ${currentPayload.itemId}: ${response.status}`);
            }

            if (!responseData.redirectUrl) {
                throw new Error('Delete response did not include a redirect URL.');
            }

            window.location.assign(responseData.redirectUrl);
        } catch (error) {
            console.error('Failed to delete dataset item', error);

            messenger.notificationFlashMessage(
                'error',
                error && error.message ? error.message : 'Could not delete this item.'
            );
        } finally {
            this.isDeletingItem = false;
        }
    },

    handleSaveAndNavigate: async function (action, shouldSave = true) {
        if (!action || this.isNavigating) {
            return;
        }

        const currentPayload = this.getCurrentPayload();
        const targetItemId = this.getActionItemId(action);
        if (!currentPayload || !currentPayload.itemId || !targetItemId || currentPayload.itemId === targetItemId) {
            return;
        }

        this.isNavigating = true;

        try {
            if (shouldSave) {
                const readyUpdateSucceeded = await this.flushPendingReadyUpdate(currentPayload.itemId);
                if (!readyUpdateSucceeded) {
                    messenger.notificationFlashMessage('error', 'Could not save ready state before navigation.');
                    return;
                }
            }

            const targetPayload = await this.fetchPayloadForAction(action);

            if (!targetPayload) {
                throw new Error(`Could not load item ${targetItemId}`);
            }

            // In no-save mode, snapshot the SOURCE item's current dirty state before
            // switching currentItemId to the target.  After switchToPayload the
            // source can no longer be retrieved via getCurrentPayload().
            if (!shouldSave && currentPayload.itemId) {
                this.noSaveChangesMap.set(currentPayload.itemId, {
                    annotations: currentPayload.annotations ? currentPayload.annotations.map(a => ({ ...a })) : [],
                    groupValue: currentPayload.groupValue || null,
                    readyValue: Boolean(currentPayload.readyValue)
                });
            }

            this.switchToPayload(targetPayload);

            if (shouldSave) {
                const saveJob = this.captureCurrentSaveJob(currentPayload);
                if (saveJob) {
                    this.enqueueBackgroundSave(saveJob);
                }
            } else {
                this.formHasUnsavedChanges = false;
            }

            this.preloadAroundPayload(targetPayload);
        } catch (error) {
            console.error('Failed to navigate to buffered item', error);
            messenger.notificationFlashMessage('warning', 'Buffered navigation failed. Falling back to normal page load.');
            this.submitNavigationFallback(action);
        } finally {
            this.isNavigating = false;
        }
    }
};
