<script>
    let {
        annotations = $bindable(),
        imageDimensions = { width: 0, height: 0 },
        titleOptions = [],
        onTitleChange = null,
        showAiShortcut = false,
        zoomScale = 1,
        onZoomIn = null,
        onZoomOut = null,
        onZoomReset = null
    } = $props();

    let activeAnnotation = $derived(annotations.find(a => a.isActive));
    let showTitleOptions = $state(false);
    let titleQuery = $state('');
    let altPressed = $state(false);
    let ctrlPressed = $state(false);
    let shiftPressed = $state(false);

    // Convert percentage to pixels helper
    const toPx = (val, total) => Math.round((val / 100) * total);
    const getAnnotationWidthPx = (annotation) => toPx(annotation.width, imageDimensions.width);
    const getAnnotationHeightPx = (annotation) => toPx(annotation.height, imageDimensions.height);
    const getAnnotationAreaPx = (annotation) => getAnnotationWidthPx(annotation) * getAnnotationHeightPx(annotation);

    let availableTitleOptions = $derived.by(() => {
        const query = titleQuery.trim().toLowerCase();

        return titleOptions.filter(option => {
            if (!option) {
                return false;
            }

            return query.length === 0 || option.toLowerCase().includes(query);
        });
    });

    function deleteActive() {
        if (!activeAnnotation) return;
        annotations = annotations.filter(a => a.id !== activeAnnotation.id);
    }

    function selectAnnotation(id) {
        annotations = annotations.map(a => ({ ...a, isActive: a.id === id }));
    }

    function clearSelection() {
        annotations = annotations.map(a => ({ ...a, isActive: false }));
    }

    function openTitleOptions() {
        titleQuery = '';
        showTitleOptions = true;
    }

    function closeTitleOptions() {
        setTimeout(() => {
            showTitleOptions = false;
        }, 100);
    }

    function applyTitleOption(option) {
        updateActiveTitle(option, { closeOptions: true });
    }

    function updateActiveTitle(value, { closeOptions = false } = {}) {
        if (!activeAnnotation) {
            return;
        }

        annotations = annotations.map(annotation => annotation.id === activeAnnotation.id
            ? { ...annotation, title: value }
            : annotation
        );
        titleQuery = value;
        showTitleOptions = !closeOptions;
        notifyTitleChange(value);
    }

    function notifyTitleChange(value) {
        if (typeof onTitleChange !== 'function') {
            return;
        }

        const normalizedValue = typeof value === 'string' ? value.trim() : '';
        if (!normalizedValue) {
            return;
        }

        onTitleChange(normalizedValue);
    }

    function syncModifierStateFromEvent(event) {
        altPressed = Boolean(event.altKey);
        ctrlPressed = Boolean(event.ctrlKey);
        shiftPressed = Boolean(event.shiftKey);
    }

    function clearModifierState() {
        altPressed = false;
        ctrlPressed = false;
        shiftPressed = false;
    }

    function getHelperItemClass(isActive) {
        return `inline-flex items-center gap-1 rounded-md px-2 py-1 transition-all ${
            isActive
                ? 'bg-blue-600 text-white shadow-sm ring-1 ring-inset ring-blue-500/30'
                : 'bg-white text-slate-500 border-0 ring-1 ring-inset ring-slate-200'
        }`;
    }

    function getHelperKeyClass(isActive) {
        return `rounded border px-1 py-0.5 text-[9px] font-bold uppercase tracking-[0.12em] ${
            isActive
                ? 'border-0 bg-white/15 text-white ring-1 ring-inset ring-white/30'
                : 'border-0 bg-slate-50 text-slate-600 ring-1 ring-inset ring-slate-300'
        }`;
    }

    function triggerZoom(action) {
        if (typeof action === 'function') {
            action();
        }
    }
</script>

<svelte:window onkeydown={syncModifierStateFromEvent} onkeyup={syncModifierStateFromEvent} onblur={clearModifierState} />

<div class="relative h-full bg-white border-l border-slate-200 transition-all duration-300 z-30 flex flex-col w-80 text-left" style="border-left-width: 1px;">
    <div class="p-6 border-b border-slate-100" style="border-bottom-width: 1px;">
        <h2 class="text-lg font-bold flex items-center justify-between text-slate-800">
            <span>Annotations</span>
            <span class="text-xs bg-slate-100 px-2 py-1 rounded-full text-slate-500 font-medium">{annotations.length}</span>
        </h2>
    </div>

    <div class="flex-1 overflow-y-auto p-4 space-y-4">
        {#if activeAnnotation}
            <div class="animate-in fade-in slide-in-from-right duration-300">
                <div class="flex items-center justify-between mb-4">
                    <button onclick={clearSelection} class="text-xs text-blue-600 font-semibold flex items-center gap-1 hover:underline">
                        Back to list
                    </button>
                    <div class="flex items-center gap-2">
                        <button onclick={deleteActive} class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Delete Area">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="relative">
                        <label for="annot-title" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Title</label>
                        <input 
                            id="annot-title"
                            type="text" 
                            value={activeAnnotation.title}
                            class="block w-full box-border h-12 px-4 bg-white border border-slate-200 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 text-sm leading-normal text-slate-700 outline-none appearance-none"
                            style="width: 100%; max-width: none; min-width: 0;"
                            autocomplete="off"
                            onfocus={openTitleOptions}
                            onclick={openTitleOptions}
                            oninput={(event) => {
                                updateActiveTitle(event.currentTarget.value);
                            }}
                            onblur={closeTitleOptions}
                        />
                        {#if showTitleOptions && availableTitleOptions.length > 0}
                            <div class="absolute left-0 right-0 top-full z-20 mt-2 max-h-56 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-xl">
                                {#each availableTitleOptions as option (option)}
                                    <button
                                        type="button"
                                        class="block w-full px-4 py-3 text-left text-sm text-slate-700 hover:bg-slate-50"
                                        onmousedown={(event) => {
                                            event.preventDefault();
                                            applyTitleOption(option);
                                        }}
                                    >
                                        {option}
                                    </button>
                                {/each}
                            </div>
                        {/if}
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 space-y-2">
                        <div class="flex items-center justify-between text-xs px-1">
                            <span class="font-bold text-slate-400 uppercase tracking-tighter">X Position</span>
                            <span class="font-bold text-slate-700 bg-white px-2 py-0.5 rounded border border-slate-200">
                                {toPx(activeAnnotation.x, imageDimensions.width)} px
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs px-1">
                            <span class="font-bold text-slate-400 uppercase tracking-tighter">Y Position</span>
                            <span class="font-bold text-slate-700 bg-white px-2 py-0.5 rounded border border-slate-200">
                                {toPx(activeAnnotation.y, imageDimensions.height)} px
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs px-1">
                            <span class="font-bold text-slate-400 uppercase tracking-tighter">Dimensions</span>
                            <span class="font-bold text-slate-700 bg-white px-2 py-0.5 rounded border border-slate-200">
                                {getAnnotationWidthPx(activeAnnotation)} x {getAnnotationHeightPx(activeAnnotation)}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs px-1">
                            <span class="font-bold text-slate-400 uppercase tracking-tighter">Area</span>
                            <span class="font-bold text-slate-700 bg-white px-2 py-0.5 rounded border border-slate-200">
                                {getAnnotationAreaPx(activeAnnotation)} px2
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        {:else if annotations.length > 0}
            {#each annotations as annot (annot.id)}
                <!-- svelte-ignore a11y_click_events_have_key_events -->
                <!-- svelte-ignore a11y_no_static_element_interactions -->
                <div 
                    onclick={() => selectAnnotation(annot.id)}
                    class="annot-item p-3 rounded-lg border-0 ring-1 ring-inset ring-slate-200 bg-white cursor-pointer hover:shadow-md transition-all flex items-center gap-3"
                >
                    <div class="w-3 h-3 rounded-full shrink-0" style="background-color: {annot.color}"></div>
                    <div class="flex-1 min-w-0 text-left">
                        <div class="text-sm font-semibold truncate text-slate-800">{annot.title}</div>
                        <div class="text-[10px] text-slate-400 font-medium">
                            {getAnnotationWidthPx(annot)} x {getAnnotationHeightPx(annot)} • {getAnnotationAreaPx(annot)} px2
                        </div>
                    </div>
                </div>
            {/each}
        {:else}
            <div class="text-center py-10 px-4 text-sm text-slate-400">No areas selected. Draw on the image to get started.</div>
        {/if}
    </div>
    
    <div class="p-4 bg-slate-50 border-t border-slate-200 space-y-2" style="border-top-width: 1px;">
        <div class={`flex flex-nowrap items-center justify-center gap-1 text-[10px] font-medium ${altPressed || ctrlPressed || shiftPressed ? 'text-slate-700' : 'text-slate-400'}`}>
            <div class={getHelperItemClass(ctrlPressed)}>
                <span class={getHelperKeyClass(ctrlPressed)}>Ctrl</span>
                <span>Scroll</span>
            </div>
            <div class={getHelperItemClass(altPressed)}>
                <span class={getHelperKeyClass(altPressed)}>Alt</span>
                <span>Drag</span>
            </div>
            {#if showAiShortcut}
                <div class={getHelperItemClass(shiftPressed)}>
                    <span class={getHelperKeyClass(shiftPressed)}>Shift</span>
                    <span>AI</span>
                </div>
            {/if}
        </div>
        <div class="rounded-xl border-0 ring-1 ring-inset ring-slate-200 bg-white p-1 shadow-sm">
            <div class="flex items-center gap-1">
                <button
                    type="button"
                    onclick={() => triggerZoom(onZoomOut)}
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-700 transition-colors hover:bg-slate-100"
                    title="Zoom out"
                    aria-label="Zoom out"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" />
                    </svg>
                </button>
                <div class="min-w-0 flex-1 text-center text-xs font-bold text-slate-500">
                    {Math.round(zoomScale * 100)}%
                </div>
                <button
                    type="button"
                    onclick={() => triggerZoom(onZoomIn)}
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-700 transition-colors hover:bg-slate-100"
                    title="Zoom in"
                    aria-label="Zoom in"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                    </svg>
                </button>
                <div class="h-4 w-px bg-slate-200"></div>
                <button
                    type="button"
                    onclick={() => triggerZoom(onZoomReset)}
                    class="inline-flex h-8 items-center justify-center rounded-lg px-2.5 text-[11px] font-bold text-blue-600 transition-colors hover:bg-slate-100"
                    title="Reset zoom"
                    aria-label="Reset zoom"
                >
                    Reset
                </button>
            </div>
        </div>
    </div>
</div>
