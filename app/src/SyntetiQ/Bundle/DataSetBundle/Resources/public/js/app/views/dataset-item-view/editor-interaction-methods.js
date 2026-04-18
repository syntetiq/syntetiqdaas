import $ from 'jquery';
import messenger from 'oroui/js/messenger';

function normalizePersistedArea(area, index, imageDimensions) {
    const title = typeof area.title === 'string' ? area.title : '';
    const name = typeof area.name === 'string' ? area.name : '';
    const normalized = {
        x: area.x,
        y: area.y,
        width: area.width,
        height: area.height,
        name: title || name,
        index: Number.isInteger(area.index) ? area.index : index
    };

    if (imageDimensions && imageDimensions.width && imageDimensions.height) {
        normalized.x = Math.round((area.x / 100) * imageDimensions.width);
        normalized.y = Math.round((area.y / 100) * imageDimensions.height);
        normalized.width = Math.round((area.width / 100) * imageDimensions.width);
        normalized.height = Math.round((area.height / 100) * imageDimensions.height);
    }

    if (typeof area.latitude !== 'undefined') {
        normalized.latitude = area.latitude;
    }

    if (typeof area.longitude !== 'undefined') {
        normalized.longitude = area.longitude;
    }

    return normalized;
}

function normalizePersistedAreas(areas, imageDimensions) {
    return Array.isArray(areas)
        ? areas.map((area, index) => normalizePersistedArea(area, index, imageDimensions))
        : [];
}

export default {
    mountSvelteApp: function (container, props) {
        if (this.svelteApp && this.svelteApp.$destroy) {
            this.svelteApp.$destroy();
        }

        container.innerHTML = '';

        this.svelteApp = this.mountApp(container, props);
        this.updateEditorHeight();

        const syncFileInput = () => {
            const svelteInput = container.querySelector('input[type="file"]');
            if (svelteInput) {
                svelteInput.addEventListener('change', event => {
                    const file = event.target.files[0];
                    if (!file) {
                        return;
                    }

                    const $form = this.$el.closest('form');
                    const $formInput = $form.find('.input-widget-file input[type="file"]');
                    const formInput = $formInput.get(0);

                    if (!formInput) {
                        console.error('Could not find Oro form file input to sync!');
                        return;
                    }

                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    formInput.files = dataTransfer.files;
                    $formInput.trigger('change');
                });
            } else {
                setTimeout(syncFileInput, 500);
            }
        };

        setTimeout(syncFileInput, 0);
    },

    handleFileSelect: function (e) {
        const file = e.target.files[0];
        if (!file) {
            return;
        }

        const maxSize = $(e.target).data('max-size');
        if (maxSize && file.size > maxSize) {
            messenger.notificationFlashMessage('error', 'File size exceeds the limit of ' + (maxSize / 1024 / 1024) + 'MB');
            e.target.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = (event) => {
            const imageSrc = event.target.result;
            const img = new Image();

            img.onload = () => {
                const currentPayload = this.getCurrentPayload();
                const hiddenObjectInfo = {
                    ...((currentPayload && currentPayload.hiddenObjectInfo) || {}),
                    imgWidth: img.naturalWidth,
                    imgHeight: img.naturalHeight,
                    areas: []
                };
                const nextPayload = {
                    ...(currentPayload || {}),
                    imageSrc,
                    bufferedImageSrc: imageSrc,
                    imageDimensions: {
                        width: img.naturalWidth,
                        height: img.naturalHeight
                    },
                    hiddenObjectInfo,
                    annotations: [],
                    isDirty: true
                };

                this.formHasUnsavedChanges = true;
                this.storePayload(nextPayload);
                this.applyPayloadToForm(nextPayload);

                const container = this.el.querySelector('.svelte-container');
                if (container && this.mountApp) {
                    this.mountSvelteApp(container, this.buildSvelteProps(nextPayload));
                    this.handleAnnotationsChange([]);
                    this.resizeHandler();
                }
            };

            img.src = imageSrc;
        };

        reader.readAsDataURL(file);
    },

    handleAutoDetectRequest: async function (detail) {
        const currentPayload = this.getCurrentPayload();
        const itemId = currentPayload && currentPayload.itemId;
        const url = this.getSegmentClickUrl(itemId);

        if (!url) {
            messenger.notificationFlashMessage('error', 'Could not resolve the auto-detect endpoint for this dataset item.');
            return null;
        }

        try {
            const response = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    xPct: detail && detail.xPct,
                    yPct: detail && detail.yPct
                })
            });

            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                const message = payload.details || payload.message || `Automatic segmentation failed with status ${response.status}.`;
                messenger.notificationFlashMessage('error', message);
                return null;
            }

            return payload;
        } catch (error) {
            console.error('Automatic segmentation request failed', error);
            messenger.notificationFlashMessage('error', 'Could not reach the automatic segmentation service.');
            return null;
        }
    },

    updateImageDimensions: function (width, height) {
        const hiddenInput = this.getHiddenInputElement();
        if (!hiddenInput) {
            return;
        }

        const currentData = this.parseJsonValue(hiddenInput.value, {});
        currentData.imgWidth = width;
        currentData.imgHeight = height;

        hiddenInput.value = JSON.stringify(currentData);
    },

    handleAnnotationsChange: function (updatedAnnotations) {
        const hiddenInput = this.getHiddenInputElement();
        if (!hiddenInput) {
            console.error('Could not find hidden input for annotation bridge');
            return;
        }

        const currentData = this.parseJsonValue(hiddenInput.value, {});
        const imageDimensions = currentData.imgWidth && currentData.imgHeight
            ? { width: currentData.imgWidth, height: currentData.imgHeight }
            : null;
        const areas = normalizePersistedAreas(updatedAnnotations, imageDimensions);
        const currentAreas = normalizePersistedAreas(currentData.areas, null);
        const nextDataObj = { ...currentData, areas };
        const didChange = JSON.stringify(currentAreas) !== JSON.stringify(areas);

        if (didChange) {
            hiddenInput.value = JSON.stringify(nextDataObj);
            $(hiddenInput).trigger('change');
        }

        const currentPayload = this.getCurrentPayload();
        if (currentPayload) {
            currentPayload.hiddenObjectInfo = nextDataObj;
            currentPayload.annotations = updatedAnnotations;
            if (didChange) {
                currentPayload.isDirty = true;
                this.formHasUnsavedChanges = true;
            }
            this.storePayload(currentPayload);
        }
    }
};
