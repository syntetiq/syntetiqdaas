<script>
    import { onMount } from 'svelte';
    import Canvas from './components/Canvas.svelte';
    import Sidebar from './components/Sidebar.svelte';
    import { COLORS } from './utils/constants.js';

    function normalizeImageDimensions(dimensions) {
        if (!dimensions || typeof dimensions !== 'object') {
            return { width: 0, height: 0 };
        }

        const width = Number.parseInt(dimensions.width, 10);
        const height = Number.parseInt(dimensions.height, 10);

        if (!width || !height || width < 1 || height < 1) {
            return { width: 0, height: 0 };
        }

        return { width, height };
    }

    // Produces a stable JSON key for annotation comparison (ignores color)
    function annotationsToKey(arr) {
        return JSON.stringify(
            arr.map(a => ({ id: a.id, x: a.x, y: a.y, width: a.width, height: a.height, title: a.title }))
        );
    }

    let {
        imageSrc = $bindable(null),
        initialImageDimensions = null,
        annotations = $bindable([]),
        groupOptions = [],
        groupValue = null,
        readyValue = false,
        titleOptions = [],
        lastUsedTitle = null,
        previousItemAction = null,
        nextItemAction = null,
        autoDetectRequest = null,
        canDeleteItem = false,
        // Server-side originals – used as the undo target.
        // When a buffered item has stored no-save changes, the parent passes the
        // no-save state as the normal props above and the server originals here.
        // null means "not provided" (standalone / initial-page use) — distinguished
        // from [] which means "provided but empty".
        serverAnnotations = null,
        serverGroupValue = undefined,
        serverReadyValue = undefined
    } = $props();

    let imageDimensions = $state({ width: 0, height: 0 });
    let selectedGroup = $state(null);
    let selectedReady = $state(false);
    let rememberedTitle = $state('');
    let aiAssistEnabled = $state(false);
    let aiAssistPending = $state(false);
    let canvasScale = $state(1);
    let canvasOffset = $state({ x: 0, y: 0 });
    let isNavigating = $state(false);
    let autoSaveEnabled = $state(true);
    let fileInput;
    let container;

    // Change-tracking internals (plain vars – NOT $state so they don't create
    // reactive dependencies inside $derived.by)
    let _mountComplete = $state(false);
    let _originalAnnotations = [];
    let _originalAnnotationsKey = '';
    let _originalGroupValue = null;
    let _originalReadyValue = false;

    // Derived: true when current state differs from what was loaded from the server
    let hasUnsavedChanges = $derived.by(() => {
        if (!_mountComplete) return false;
        return annotationsToKey($state.snapshot(annotations)) !== _originalAnnotationsKey
            || selectedGroup !== _originalGroupValue
            || selectedReady !== _originalReadyValue;
    });

    let availableTitles = $derived.by(() => {
        const values = [...titleOptions];

        for (const annotation of annotations) {
            const title = annotation.title?.trim();
            if (title) {
                values.push(title);
            }
        }

        return [...new Set(values)];
    });

    let saveBtnClass = $derived(
        autoSaveEnabled
            ? 'bg-emerald-600 text-white border-emerald-600 hover:bg-emerald-700'
            : hasUnsavedChanges
                ? 'bg-red-50 text-red-600 border-red-200 hover:bg-red-100'
                : 'bg-white text-slate-400 border-slate-200 hover:bg-slate-50'
    );

    let saveBtnTitle = $derived(
        autoSaveEnabled
            ? 'Auto-Save is ON'
            : hasUnsavedChanges
                ? 'No Save — Unsaved Changes!'
                : 'Auto-Save is OFF'
    );

    // Ensure all annotations have colors when they are loaded or changed
    $effect(() => {
        let changed = false;
        const sanitized = annotations.map((annot, index) => {
            if (!annot.color) {
                changed = true;
                return { ...annot, color: COLORS[index % COLORS.length] };
            }
            return annot;
        });

        if (changed) {
            annotations = sanitized;
        }
    });

    onMount(() => {
        // Use server originals as the undo target.
        // serverAnnotations is null when not provided (initial-page load without a
        // buffer entry): fall back to the current annotation state.
        // When the integration layer provides serverAnnotations (even an empty
        // array) it is authoritative and used directly.
        const origSnap = serverAnnotations !== null
            ? serverAnnotations
            : $state.snapshot(annotations);
        _originalAnnotations = origSnap.map(a => ({ ...a }));
        _originalAnnotationsKey = annotationsToKey(origSnap);
        _originalGroupValue = serverGroupValue !== undefined ? serverGroupValue : selectedGroup;
        _originalReadyValue = serverReadyValue !== undefined ? serverReadyValue : selectedReady;
        _mountComplete = true;
    });

    function handleUpload(e) {
        const file = e.target.files?.[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (ev) => {
                imageSrc = ev.target.result;
                annotations = []; // Reset on new image
                imageDimensions = { width: 0, height: 0 }; // Reset dimensions
                canvasScale = 1;
                canvasOffset = { x: 0, y: 0 };
            };
            reader.readAsDataURL(file);
        }
    }

    function triggerUpload() {
        fileInput.click();
    }

    function getDefaultAnnotationTitle() {
        if (rememberedTitle) {
            return rememberedTitle;
        }

        const previousAnnotation = [...annotations]
            .reverse()
            .find(annotation => annotation.title && annotation.title.trim().length > 0);

        return previousAnnotation?.title ?? `Area ${annotations.length + 1}`;
    }

    async function handleAutoDetectRequest(detail) {
        if (aiAssistPending || typeof autoDetectRequest !== 'function') {
            return null;
        }

        aiAssistPending = true;

        try {
            const result = await autoDetectRequest(detail);
            const bbox = result?.bbox;

            if (!bbox) {
                return null;
            }

            const newAnnotation = {
                id: crypto.randomUUID(),
                x: bbox.x,
                y: bbox.y,
                width: bbox.width,
                height: bbox.height,
                color: COLORS[annotations.length % COLORS.length],
                title: getDefaultAnnotationTitle(),
                isActive: true
            };

            annotations = [...annotations, newAnnotation];
            annotations = annotations.map(a => ({ ...a, isActive: a.id === newAnnotation.id }));

            return result;
        } catch (error) {
            console.error('Automatic object detection failed', error);
            return null;
        } finally {
            aiAssistPending = false;
        }
    }

    function handleGroupSelect(value) {
        if (!value || value === selectedGroup) {
            return;
        }

        selectedGroup = value;

        if (container) {
            container.dispatchEvent(new CustomEvent('groupchange', {
                detail: { value },
                bubbles: true,
                composed: true
            }));
        }

        syncNativeGroupInput(value);
    }

    function syncNativeGroupInput(value) {
        if (!container) {
            return;
        }

        const form = container.closest('form');
        if (!form) {
            return;
        }

        const groupInput = form.querySelector('select[name$="[group]"], input[name$="[group]"]');
        const targetValue = value === null || value === undefined ? '' : String(value);
        if (!groupInput || groupInput.value === targetValue) {
            return;
        }

        groupInput.value = targetValue;
        groupInput.dispatchEvent(new Event('input', { bubbles: true }));
        groupInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function handleSaveAndNavigate(action) {
        if (!container || !action) {
            return;
        }

        isNavigating = true;

        container.dispatchEvent(new CustomEvent('saveandnavigate', {
            detail: { action, shouldSave: autoSaveEnabled },
            bubbles: true,
            composed: true
        }));
    }

    function handleDeleteItem() {
        if (!container || !canDeleteItem) {
            return;
        }

        container.dispatchEvent(new CustomEvent('deleteitem', {
            bubbles: true,
            composed: true
        }));
    }

    function handleReadySelect(value) {
        if (value === selectedReady) {
            return;
        }

        selectedReady = value;

        if (container) {
            container.dispatchEvent(new CustomEvent('readychange', {
                detail: { value },
                bubbles: true,
                composed: true
            }));
        }
    }

    function handleUndo() {
        annotations = _originalAnnotations.map(a => ({ ...a }));
        selectedGroup = _originalGroupValue;
        selectedReady = _originalReadyValue;

        if (container) {
            container.dispatchEvent(new CustomEvent('groupchange', {
                detail: { value: selectedGroup },
                bubbles: true,
                composed: true
            }));
            container.dispatchEvent(new CustomEvent('readychange', {
                detail: { value: selectedReady },
                bubbles: true,
                composed: true
            }));
        }
        syncNativeGroupInput(selectedGroup);
    }

    function handleTitleChange(value) {
        const normalizedValue = typeof value === 'string' ? value.trim() : '';
        if (!normalizedValue || normalizedValue === rememberedTitle) {
            return;
        }

        rememberedTitle = normalizedValue;

        if (container) {
            container.dispatchEvent(new CustomEvent('lastusedtitlechange', {
                detail: { value: normalizedValue },
                bubbles: true,
                composed: true
            }));
        }
    }

    function handleZoomIn() {
        canvasScale = Math.min(canvasScale * 1.5, 10);
    }

    function handleZoomOut() {
        canvasScale = Math.max(canvasScale / 1.5, 0.5);
    }

    function handleZoomReset() {
        canvasScale = 1;
        canvasOffset = { x: 0, y: 0 };
    }

    // Dispatch custom event for integration
    $effect(() => {
        if (container) {
            container.dispatchEvent(new CustomEvent('change', {
                detail: $state.snapshot(annotations),
                bubbles: true,
                composed: true
            }));
        }
    });

    $effect(() => {
        // Use a local variable to avoid reading selectedGroup after writing it,
        // which would create a reactive dependency and cause this effect to
        // re-run (and reset selectedGroup) whenever the user picks a new group.
        const newValue = groupValue ?? null;
        selectedGroup = newValue;
        syncNativeGroupInput(newValue);
    });

    $effect(() => {
        selectedReady = Boolean(readyValue);
    });

    $effect(() => {
        imageDimensions = normalizeImageDimensions(initialImageDimensions);
    });

    $effect(() => {
        rememberedTitle = typeof lastUsedTitle === 'string' ? lastUsedTitle.trim() : '';
    });

    $effect(() => {
        const stored = localStorage.getItem('syntetiq-auto-save');
        if (stored !== null) {
            autoSaveEnabled = stored === 'true';
        }
    });

    $effect(() => {
        localStorage.setItem('syntetiq-auto-save', autoSaveEnabled);
        if (container) {
            container.dispatchEvent(new CustomEvent('autosavechange', {
                detail: { value: autoSaveEnabled },
                bubbles: true,
                composed: true
            }));
        }
    });
</script>

<div bind:this={container} class="sq-svelte-image-editor flex flex-col h-full min-h-0 overflow-hidden text-slate-900 bg-slate-100 font-sans border border-slate-200 rounded-xl shadow-sm" style="border-width: 1px;">
    <header class="bg-white border-b border-slate-200 px-6 py-2 flex items-center justify-between z-40 shrink-0 shadow-sm" style="border-bottom-width: 1px;">
        <div class="flex items-center gap-3 min-w-0">
            {#if groupOptions.length > 0}
                <div class="flex items-center gap-2 min-w-0">
                    <span class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400 whitespace-nowrap">Group</span>
                    <div class="inline-flex items-center h-10 rounded-lg border-0 ring-1 ring-inset ring-slate-300 bg-slate-50 p-1 gap-1">
                        {#each groupOptions as option (option.value)}
                            <button
                                type="button"
                                onclick={() => handleGroupSelect(option.value)}
                                class={`inline-flex h-full items-center rounded-md px-3 py-1.5 text-xs font-semibold transition-colors ${
                                    option.value === selectedGroup
                                        ? 'bg-white text-blue-600 shadow-sm ring-1 ring-blue-100'
                                        : 'text-slate-500 hover:bg-white hover:text-slate-700'
                                }`}
                            >
                                {option.label}
                            </button>
                        {/each}
                    </div>
                </div>
            {/if}
            {#if canDeleteItem}
                <button
                    type="button"
                    onclick={handleDeleteItem}
                    class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-red-600 hover:bg-red-700 text-white shadow-sm transition-all"
                    title="Delete Item"
                    aria-label="Delete Item"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            {/if}
            <button
                type="button"
                onclick={triggerUpload}
                class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-blue-600 hover:bg-blue-700 text-white shadow-sm transition-all"
                title={imageSrc ? 'Change Image' : 'Upload Image'}
                aria-label={imageSrc ? 'Change Image' : 'Upload Image'}
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
            </button>
            <button
                type="button"
                onclick={() => autoSaveEnabled = !autoSaveEnabled}
                class={`inline-flex items-center justify-center w-10 h-10 rounded-lg shadow-sm transition-all border ${saveBtnClass}`}
                title={saveBtnTitle}
                aria-label="Toggle Auto-Save"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
                    <polyline stroke-linecap="round" stroke-linejoin="round" stroke-width="2" points="17 21 17 13 7 13 7 21"/>
                    <polyline stroke-linecap="round" stroke-linejoin="round" stroke-width="2" points="7 3 7 8 15 8"/>
                </svg>
            </button>
            {#if hasUnsavedChanges}
                <button
                    type="button"
                    onclick={handleUndo}
                    class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-white hover:bg-slate-50 text-slate-500 border border-slate-200 shadow-sm transition-all"
                    title="Undo all changes"
                    aria-label="Undo all changes"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a4 4 0 010 8H3m0-8l4-4m-4 4l4 4"/>
                    </svg>
                </button>
            {/if}
            {#if imageSrc && autoDetectRequest}
                <button
                    type="button"
                    onclick={() => {
                        if (!aiAssistPending) {
                            aiAssistEnabled = !aiAssistEnabled;
                        }
                    }}
                    class={`inline-flex items-center justify-center w-10 h-10 rounded-md shadow-sm transition-all border ${
                        aiAssistEnabled
                            ? 'bg-emerald-600 hover:bg-emerald-700 text-white border-emerald-600'
                            : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-200'
                    } ${aiAssistPending ? 'opacity-70 cursor-not-allowed' : ''}`}
                    title={aiAssistPending ? 'Detecting...' : 'AI Select'}
                    aria-label={aiAssistPending ? 'Detecting...' : 'AI Select'}
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.813 15.904L9 18l-.813-2.096A4.5 4.5 0 005.904 13.187L3.808 12l2.096-.813A4.5 4.5 0 008.187 8.904L9 6.808l.813 2.096A4.5 4.5 0 0012.096 11.187L14.192 12l-2.096.813A4.5 4.5 0 009.813 15.904zM18 14v4m-2-2h4M17 5l.47 1.222L18.692 6.7l-1.222.47L17 8.392l-.47-1.222-1.222-.47 1.222-.47L17 5z" />
                    </svg>
                </button>
            {/if}
        </div>
        <div class="flex items-center gap-2">
            <div class="inline-flex items-center h-10 rounded-lg border-0 ring-1 ring-inset ring-slate-300 bg-slate-50 p-1 gap-1">
                <button
                    type="button"
                    onclick={() => handleReadySelect(false)}
                    class={`inline-flex h-full items-center rounded-md px-3 py-1.5 text-xs font-semibold transition-colors ${
                        !selectedReady
                            ? 'bg-white text-amber-600 shadow-sm ring-1 ring-amber-100'
                            : 'text-slate-500 hover:bg-white hover:text-slate-700'
                    }`}
                >
                    Not Ready
                </button>
                <button
                    type="button"
                    onclick={() => handleReadySelect(true)}
                    class={`inline-flex h-full items-center rounded-md px-3 py-1.5 text-xs font-semibold transition-colors ${
                        selectedReady
                            ? 'bg-white text-emerald-600 shadow-sm ring-1 ring-emerald-100'
                            : 'text-slate-500 hover:bg-white hover:text-slate-700'
                    }`}
                >
                    Ready
                </button>
            </div>
            {#if previousItemAction}
                <button
                    type="button"
                    onclick={() => handleSaveAndNavigate(previousItemAction)}
                    disabled={isNavigating}
                    class={`inline-flex items-center justify-center w-10 h-10 rounded-md shadow-sm transition-all text-white ${isNavigating ? 'bg-emerald-500 opacity-50 cursor-not-allowed' : 'bg-emerald-600 hover:bg-emerald-700'}`}
                    title="Previous item"
                    aria-label="Previous item"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
            {/if}
            {#if nextItemAction}
                <button
                    type="button"
                    onclick={() => handleSaveAndNavigate(nextItemAction)}
                    disabled={isNavigating}
                    class={`inline-flex items-center justify-center w-10 h-10 rounded-md shadow-sm transition-all text-white ${isNavigating ? 'bg-emerald-500 opacity-50 cursor-not-allowed' : 'bg-emerald-600 hover:bg-emerald-700'}`}
                    title="Next item"
                    aria-label="Next item"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            {/if}
            <input
                bind:this={fileInput}
                type="file"
                accept="image/*"
                class="hidden"
                onchange={handleUpload}
            />
        </div>
    </header>

    <main class="flex-1 min-h-0 flex overflow-hidden relative">
        <div class="flex-1 min-h-0 relative overflow-hidden bg-slate-100 p-4 md:p-8 flex items-center justify-center">
            {#if !imageSrc}
                <div class="h-full flex flex-col items-center justify-center text-center max-w-lg mx-auto">
                    <div class="w-24 h-24 bg-white rounded-3xl shadow-xl flex items-center justify-center mb-8 rotate-3 border border-slate-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h2 class="text-3xl font-extrabold mb-3 text-slate-800 tracking-tight">Manual Image Annotation</h2>
                    <p class="text-slate-500 mb-10 text-lg leading-relaxed">Zoom into details, drag markers, and define custom areas with specific information.</p>
                    <button onclick={triggerUpload} class="px-10 py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-all font-bold text-lg shadow-2xl shadow-blue-500/30 active:scale-95">Get Started</button>
                </div>
            {:else}
                <Canvas
                    {imageSrc}
                    bind:annotations
                    bind:imageDimensions
                    bind:scale={canvasScale}
                    bind:offset={canvasOffset}
                    defaultAnnotationTitle={rememberedTitle || null}
                    {aiAssistEnabled}
                    {aiAssistPending}
                    requestAutoDetect={handleAutoDetectRequest}
                />
            {/if}
        </div>

        {#if imageSrc}
            <Sidebar
                bind:annotations
                {imageDimensions}
                titleOptions={availableTitles}
                onTitleChange={handleTitleChange}
                showAiShortcut={Boolean(autoDetectRequest)}
                zoomScale={canvasScale}
                onZoomIn={handleZoomIn}
                onZoomOut={handleZoomOut}
                onZoomReset={handleZoomReset}
            />
        {/if}
        {#if isNavigating}
            <div class="absolute inset-0 z-50 flex items-center justify-center bg-white/60 backdrop-blur-sm">
                <div class="flex flex-col items-center gap-3">
                    <svg class="w-10 h-10 text-emerald-600 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm font-semibold text-slate-600">Loading...</span>
                </div>
            </div>
        {/if}
    </main>
</div>
