import BaseView from 'oroui/js/app/views/base/view';

import payloadMethods from './dataset-item-view/payload-methods';
import sessionUiMethods from './dataset-item-view/session-ui-methods';
import navigationBufferMethods from './dataset-item-view/navigation-buffer-methods';
import saveNavigationMethods from './dataset-item-view/save-navigation-methods';
import editorInteractionMethods from './dataset-item-view/editor-interaction-methods';

const DataSetItemView = BaseView.extend(Object.assign({
    svelteApp: null,
    draftPayload: null,
    resizeHandler: null,
    resizeFrame: null,
    itemCache: null,
    draftPayload: null,
    imagePreloadCache: null,
    pendingReadyUpdates: null,
    pendingSaveJobs: null,
    pendingSaveOrder: null,
    failedSaveJobs: null,
    saveProcessorPromise: null,
    deletedItemIds: null,
    isSavingInBackground: false,
    isNavigating: false,
    isDeletingItem: false,
    isResubmittingForm: false,
    formHasUnsavedChanges: false,
    lastUsedTitle: '',
    preloadRadius: 5,

    /**
     * @inheritdoc
     */
    constructor: function DataSetItemView(options) {
        DataSetItemView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function (options) {
        DataSetItemView.__super__.initialize.call(this, options);

        this.itemCache = new Map();
        this.draftPayload = null;
        this.imagePreloadCache = new Map();
        this.pendingReadyUpdates = new Map();
        this.pendingSaveJobs = new Map();
        this.pendingSaveOrder = [];
        this.failedSaveJobs = new Map();
        this.noSaveChangesMap = new Map();
        this.saveProcessorPromise = null;
        this.deletedItemIds = new Set();
        this.isSavingInBackground = false;
        this.isNavigating = false;
        this.isDeletingItem = false;
        this.isResubmittingForm = false;
        this.formHasUnsavedChanges = false;
        this.lastUsedTitle = this.readStoredLastUsedTitle(options);
        this.preloadRadius = Math.max(0, Number.parseInt(options.preloadRadius, 10) || 5);
        this.autoDetectRequestHandler = this.handleAutoDetectRequest.bind(this);

        const container = this.el.querySelector('.svelte-container');
        this.resizeHandler = () => {
            if (this.resizeFrame) {
                window.cancelAnimationFrame(this.resizeFrame);
            }

            this.resizeFrame = window.requestAnimationFrame(() => {
                this.updateEditorHeight();
            });
        };

        this.resizeHandler();
        window.addEventListener('resize', this.resizeHandler);

        this.beforeUnloadHandler = this.handleBeforeUnload.bind(this);
        window.addEventListener('beforeunload', this.beforeUnloadHandler);

        this.formSubmitHandler = this.handleFormSubmit.bind(this);
        const form = this.getFormElement();
        if (form) {
            form.addEventListener('submit', this.formSubmitHandler);
        }

        if (container) {
            this.containerChangeHandler = (event) => {
                if (event.detail) {
                    this.handleAnnotationsChange(event.detail);
                } else {
                    console.warn('Received change event but event.detail is missing');
                }
            };
            this.containerGroupChangeHandler = (event) => {
                if (event.detail && event.detail.value) {
                    this.handleGroupChange(event.detail.value);
                }
            };
            this.containerReadyChangeHandler = (event) => {
                if (event.detail && typeof event.detail.value === 'boolean') {
                    this.handleReadyChange(event.detail.value);
                }
            };
            this.containerAutoSaveChangeHandler = (event) => {
                if (event.detail && typeof event.detail.value === 'boolean') {
                    this.handleAutoSaveChange(event.detail.value);
                }
            };
            this.containerSaveAndNavigateHandler = (event) => {
                if (event.detail && event.detail.action) {
                    this.handleSaveAndNavigate(event.detail.action, event.detail.shouldSave);
                }
            };
            this.containerLastUsedTitleChangeHandler = (event) => {
                if (event.detail && typeof event.detail.value === 'string') {
                    this.handleLastUsedTitleChange(event.detail.value);
                }
            };
            this.containerDeleteItemHandler = () => {
                this.handleDeleteItem();
            };
            this.containerClickHandler = (event) => {
                const button = event.target.closest('button');
                if (button) {
                    event.preventDefault();
                }
            };

            container.addEventListener('change', this.containerChangeHandler);
            container.addEventListener('groupchange', this.containerGroupChangeHandler);
            container.addEventListener('readychange', this.containerReadyChangeHandler);
            container.addEventListener('autosavechange', this.containerAutoSaveChangeHandler);
            container.addEventListener('saveandnavigate', this.containerSaveAndNavigateHandler);
            container.addEventListener('lastusedtitlechange', this.containerLastUsedTitleChangeHandler);
            container.addEventListener('deleteitem', this.containerDeleteItemHandler);
            container.addEventListener('click', this.containerClickHandler);
        }

        this.fileInputChangeHandler = (event) => {
            this.handleFileSelect(event);
        };
        this.$el.closest('form').on('change', '.input-widget-file input[type="file"]', this.fileInputChangeHandler);

        const initialPayload = this.extractPayloadFromPage(document, this.el, this.getCurrentFormAction(), options);
        if (initialPayload) {
            this.currentItemId = initialPayload.itemId;
            const storedInitialPayload = this.storePayload(initialPayload) || initialPayload;
            if (!storedInitialPayload.itemId) {
                this.draftPayload = storedInitialPayload;
            }
            this.updateShellOptionsFromPayload(storedInitialPayload);

            if (storedInitialPayload.imageSrc) {
                void this.preloadImage(storedInitialPayload.imageSrc).then(imageState => {
                    if (!imageState || !this.currentItemId || this.currentItemId !== storedInitialPayload.itemId) {
                        return;
                    }

                    this.storePayload({
                        ...storedInitialPayload,
                        bufferedImageSrc: imageState.objectUrl || storedInitialPayload.imageSrc,
                        imageDimensions: storedInitialPayload.imageDimensions || {
                            width: imageState.width,
                            height: imageState.height
                        }
                    });
                });
            }
        }

        import('syntetiqdataset/js/svelte/editor-svelte-component').then(({ mountApp }) => {
            this.mountApp = mountApp;
            if (container && initialPayload) {
                this.mountSvelteApp(container, this.buildSvelteProps(initialPayload));
                this.resizeHandler();
                this.preloadAroundPayload(initialPayload);
            }
        }).catch(err => {
            console.error('Failed to load Svelte app:', err);
        });
    },

    dispose: function () {
        if (this.disposed) {
            return;
        }

        if (this.svelteApp && this.svelteApp.$destroy) {
            this.svelteApp.$destroy();
            this.svelteApp = null;
        }

        const container = this.el.querySelector('.svelte-container');
        if (container) {
            if (this.containerChangeHandler) {
                container.removeEventListener('change', this.containerChangeHandler);
            }
            if (this.containerGroupChangeHandler) {
                container.removeEventListener('groupchange', this.containerGroupChangeHandler);
            }
            if (this.containerReadyChangeHandler) {
                container.removeEventListener('readychange', this.containerReadyChangeHandler);
            }
            if (this.containerAutoSaveChangeHandler) {
                container.removeEventListener('autosavechange', this.containerAutoSaveChangeHandler);
            }
            if (this.containerSaveAndNavigateHandler) {
                container.removeEventListener('saveandnavigate', this.containerSaveAndNavigateHandler);
            }
            if (this.containerLastUsedTitleChangeHandler) {
                container.removeEventListener('lastusedtitlechange', this.containerLastUsedTitleChangeHandler);
            }
            if (this.containerDeleteItemHandler) {
                container.removeEventListener('deleteitem', this.containerDeleteItemHandler);
            }
            if (this.containerClickHandler) {
                container.removeEventListener('click', this.containerClickHandler);
            }
        }

        if (this.resizeHandler) {
            window.removeEventListener('resize', this.resizeHandler);
            this.resizeHandler = null;
        }

        if (this.beforeUnloadHandler) {
            window.removeEventListener('beforeunload', this.beforeUnloadHandler);
            this.beforeUnloadHandler = null;
        }

        const form = this.getFormElement();
        if (form && this.formSubmitHandler) {
            form.removeEventListener('submit', this.formSubmitHandler);
        }

        if (this.fileInputChangeHandler) {
            this.$el.closest('form').off('change', '.input-widget-file input[type="file"]', this.fileInputChangeHandler);
        }

        if (this.resizeFrame) {
            window.cancelAnimationFrame(this.resizeFrame);
            this.resizeFrame = null;
        }

        DataSetItemView.__super__.dispose.call(this);
    }
}, payloadMethods, sessionUiMethods, navigationBufferMethods, saveNavigationMethods, editorInteractionMethods));

export default DataSetItemView;
