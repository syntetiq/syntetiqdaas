<script>
    import { COLORS } from '../utils/constants.js';

    let {
        imageSrc,
        annotations = $bindable(),
        imageDimensions = $bindable(),
        scale = $bindable(1),
        offset = $bindable({ x: 0, y: 0 }),
        defaultAnnotationTitle = null,
        aiAssistEnabled = false,
        aiAssistPending = false,
        requestAutoDetect = null
    } = $props();
    
    let isPanning = $state(false);
    let isDrawing = $state(false);
    let isMoving = $state(false);
    let isResizing = $state(false);
    let activeResizeHandle = $state(null);
    let resizeStartBounds = $state(null);
    let resizePointerOffset = $state({ x: 0, y: 0 });
    let showCrosshair = $state(false);
    let hoverPoint = $state({ x: 50, y: 50 });
    let altPressed = $state(false);

    let imageElement;

    function syncImageDimensions(image) {
        if (!image || !image.naturalWidth || !image.naturalHeight) {
            return;
        }

        imageDimensions = {
            width: image.naturalWidth,
            height: image.naturalHeight
        };
    }

    function handleImageLoad(e) {
        syncImageDimensions(e.target);
    }
    
    let startPoint = $state(null);
    let currentPoint = $state(null); // For drawing preview
    let lastPanPos = $state(null);
    
    let movingModelId = $state(null);
    let moveOffset = $state(null);

    let container;
    let stage;

    $effect(() => {
        if (imageElement && imageElement.complete && imageElement.naturalWidth > 0 && imageElement.naturalHeight > 0) {
            syncImageDimensions(imageElement);
        }
    });

    const MIN_BOX_SIZE = 1;
    const HANDLE_SIZE = 12;
    const EDGE_HANDLE_LENGTH = 28;
    const EDGE_HANDLE_THICKNESS = 8;
    const HANDLE_OFFSET = 4;
    const clamp = (val, min, max) => Math.min(Math.max(val, min), max);
    const resizeHandles = [
        { name: 'nw', style: `left: -${HANDLE_OFFSET + HANDLE_SIZE}px; top: -${HANDLE_OFFSET + HANDLE_SIZE}px; width: ${HANDLE_SIZE}px; height: ${HANDLE_SIZE}px; border-radius: 9999px; cursor: nwse-resize !important; pointer-events: auto;` },
        { name: 'n', style: `left: calc(50% - ${EDGE_HANDLE_LENGTH / 2}px); top: -${HANDLE_OFFSET + EDGE_HANDLE_THICKNESS}px; width: ${EDGE_HANDLE_LENGTH}px; height: ${EDGE_HANDLE_THICKNESS}px; border-radius: 9999px; cursor: ns-resize !important; pointer-events: auto;` },
        { name: 'ne', style: `right: -${HANDLE_OFFSET + HANDLE_SIZE}px; top: -${HANDLE_OFFSET + HANDLE_SIZE}px; width: ${HANDLE_SIZE}px; height: ${HANDLE_SIZE}px; border-radius: 9999px; cursor: nesw-resize !important; pointer-events: auto;` },
        { name: 'e', style: `right: -${HANDLE_OFFSET + EDGE_HANDLE_THICKNESS}px; top: calc(50% - ${EDGE_HANDLE_LENGTH / 2}px); width: ${EDGE_HANDLE_THICKNESS}px; height: ${EDGE_HANDLE_LENGTH}px; border-radius: 9999px; cursor: ew-resize !important; pointer-events: auto;` },
        { name: 'se', style: `right: -${HANDLE_OFFSET + HANDLE_SIZE}px; bottom: -${HANDLE_OFFSET + HANDLE_SIZE}px; width: ${HANDLE_SIZE}px; height: ${HANDLE_SIZE}px; border-radius: 9999px; cursor: nwse-resize !important; pointer-events: auto;` },
        { name: 's', style: `left: calc(50% - ${EDGE_HANDLE_LENGTH / 2}px); bottom: -${HANDLE_OFFSET + EDGE_HANDLE_THICKNESS}px; width: ${EDGE_HANDLE_LENGTH}px; height: ${EDGE_HANDLE_THICKNESS}px; border-radius: 9999px; cursor: ns-resize !important; pointer-events: auto;` },
        { name: 'sw', style: `left: -${HANDLE_OFFSET + HANDLE_SIZE}px; bottom: -${HANDLE_OFFSET + HANDLE_SIZE}px; width: ${HANDLE_SIZE}px; height: ${HANDLE_SIZE}px; border-radius: 9999px; cursor: nesw-resize !important; pointer-events: auto;` },
        { name: 'w', style: `left: -${HANDLE_OFFSET  + EDGE_HANDLE_THICKNESS}px; top: calc(50% - ${EDGE_HANDLE_LENGTH / 2}px); width: ${EDGE_HANDLE_THICKNESS}px; height: ${EDGE_HANDLE_LENGTH}px; border-radius: 9999px; cursor: ew-resize !important; pointer-events: auto;` }
    ];

    function getDefaultAnnotationTitle() {
        const preferredTitle = typeof defaultAnnotationTitle === 'string' ? defaultAnnotationTitle.trim() : '';
        if (preferredTitle) {
            return preferredTitle;
        }

        const previousAnnotation = [...annotations]
            .reverse()
            .find(annotation => annotation.title && annotation.title.trim().length > 0);

        return previousAnnotation?.title ?? `Area ${annotations.length + 1}`;
    }

    function syncModifierStateFromEvent(event) {
        altPressed = Boolean(event.altKey);

        if (event.altKey) {
            showCrosshair = false;
        }
    }

    function clearModifierState() {
        altPressed = false;
    }

    function getRelativeCoords(e) {
        if (!stage) return { x: 0, y: 0 };
        const rect = stage.getBoundingClientRect();
        // Calculate position relative to the stage, accounting for scale
        const mx = e.clientX - rect.left;
        const my = e.clientY - rect.top;
        
        // Convert to percentage relative to the *unscaled* image dimensions, clamped to [0, 100]
        return {
            x: clamp((mx / rect.width) * 100, 0, 100),
            y: clamp((my / rect.height) * 100, 0, 100)
        };
    }

    function resizeBounds(bounds, coords, handle) {
        const left = bounds.x;
        const top = bounds.y;
        const right = bounds.x + bounds.width;
        const bottom = bounds.y + bounds.height;

        let newLeft = left;
        let newTop = top;
        let newRight = right;
        let newBottom = bottom;

        if (handle.includes('w')) {
            newLeft = clamp(coords.x, 0, right - MIN_BOX_SIZE);
        }
        if (handle.includes('e')) {
            newRight = clamp(coords.x, left + MIN_BOX_SIZE, 100);
        }
        if (handle.includes('n')) {
            newTop = clamp(coords.y, 0, bottom - MIN_BOX_SIZE);
        }
        if (handle.includes('s')) {
            newBottom = clamp(coords.y, top + MIN_BOX_SIZE, 100);
        }

        return {
            x: newLeft,
            y: newTop,
            width: newRight - newLeft,
            height: newBottom - newTop
        };
    }

    function getResizeAnchor(bounds, handle) {
        const right = bounds.x + bounds.width;
        const bottom = bounds.y + bounds.height;

        return {
            x: handle.includes('w') ? bounds.x : handle.includes('e') ? right : null,
            y: handle.includes('n') ? bounds.y : handle.includes('s') ? bottom : null
        };
    }

    function updateCrosshairState(e) {
        if (!stage || aiAssistPending) {
            showCrosshair = false;
            return;
        }

        const target = e.target;
        const overResizeHandle = !!target.closest('.resize-handle');
        const overStage = !!target.closest('.canvas-stage');

        showCrosshair = overStage && !overResizeHandle && !e.altKey;

        if (showCrosshair) {
            hoverPoint = getRelativeCoords(e);
        }
    }

    function handleStageLeave() {
        showCrosshair = false;
    }

    function handleMouseDown(e) {
        syncModifierStateFromEvent(e);

        if (e.button === 0 && e.altKey) {
            e.preventDefault();
            isPanning = true;
            lastPanPos = { x: e.clientX, y: e.clientY };
            return;
        }

        if (e.button !== 0) return;

        const target = e.target;
        const marker = target.closest('.annotation-marker');

        if (marker) {
            const id = marker.dataset.id;
            const model = annotations.find(a => a.id === id);
            if (model) {
                e.preventDefault();
                // Check if we clicked on the resize handle
                const resizeHandleElement = target.closest('.resize-handle');
                if (resizeHandleElement) {
                    const handle = resizeHandleElement.dataset.handle || 'se';
                    const coords = getRelativeCoords(e);
                    const bounds = {
                        x: model.x,
                        y: model.y,
                        width: model.width,
                        height: model.height
                    };
                    const anchor = getResizeAnchor(bounds, handle);

                    isResizing = true;
                    movingModelId = id;
                    activeResizeHandle = handle;
                    resizeStartBounds = bounds;
                    resizePointerOffset = {
                        x: anchor.x === null ? 0 : coords.x - anchor.x,
                        y: anchor.y === null ? 0 : coords.y - anchor.y
                    };
                    // Set active
                    annotations = annotations.map(a => ({ ...a, isActive: a.id === id }));
                    return;
                }

                isMoving = true;
                movingModelId = id;
                const coords = getRelativeCoords(e);
                moveOffset = {
                    x: coords.x - model.x,
                    y: coords.y - model.y
                };
                
                // Set active
                annotations = annotations.map(a => ({ ...a, isActive: a.id === id }));
                return;
            }
        }

        const aiAssistRequested = aiAssistEnabled || e.shiftKey;
        if (aiAssistRequested && !aiAssistPending && typeof requestAutoDetect === 'function') {
            e.preventDefault();
            const coords = getRelativeCoords(e);
            requestAutoDetect({
                xPct: coords.x,
                yPct: coords.y
            });
            return;
        }

        // Start Drawing
        e.preventDefault();
        startPoint = getRelativeCoords(e);
        currentPoint = startPoint;
        isDrawing = true;
        
        // Clear selection when drawing started
        annotations = annotations.map(a => ({ ...a, isActive: false }));
    }

    function handleMouseMove(e) {
        syncModifierStateFromEvent(e);
        updateCrosshairState(e);

        if (isPanning || isMoving || isResizing || isDrawing) {
            e.preventDefault();
        }

        if (isPanning && lastPanPos) {
            const dx = e.clientX - lastPanPos.x;
            const dy = e.clientY - lastPanPos.y;
            offset = {
                x: offset.x + dx,
                y: offset.y + dy
            };
            lastPanPos = { x: e.clientX, y: e.clientY };
            return;
        }

        if (isMoving && movingModelId) {
            const coords = getRelativeCoords(e);
            
            // Update the specific annotation in the array
            const index = annotations.findIndex(a => a.id === movingModelId);
            if (index !== -1) {
                const model = annotations[index];
                
                // Calculate new position and clamp it to ensure the entire box stays inside
                const newX = clamp(coords.x - moveOffset.x, 0, 100 - model.width);
                const newY = clamp(coords.y - moveOffset.y, 0, 100 - model.height);
                
                annotations[index].x = newX;
                annotations[index].y = newY;
            }
            return;
        }

        if (isResizing && movingModelId && resizeStartBounds && activeResizeHandle) {
            const coords = getRelativeCoords(e);
            const adjustedCoords = {
                x: coords.x - resizePointerOffset.x,
                y: coords.y - resizePointerOffset.y
            };
            
            const index = annotations.findIndex(a => a.id === movingModelId);
            if (index !== -1) {
                const nextBounds = resizeBounds(resizeStartBounds, adjustedCoords, activeResizeHandle);
                annotations[index].x = nextBounds.x;
                annotations[index].y = nextBounds.y;
                annotations[index].width = nextBounds.width;
                annotations[index].height = nextBounds.height;
            }
            return;
        }

        if (isDrawing && startPoint) {
            currentPoint = getRelativeCoords(e);
        }
    }

    function handleMouseUp(e) {
        syncModifierStateFromEvent(e);

        if (isPanning) {
            isPanning = false;
            lastPanPos = null;
            return;
        }

        if (isMoving) {
            isMoving = false;
            movingModelId = null;
            return;
        }

        if (isResizing) {
            isResizing = false;
            movingModelId = null;
            activeResizeHandle = null;
            resizeStartBounds = null;
            resizePointerOffset = { x: 0, y: 0 };
            return;
        }

        if (isDrawing && startPoint) {
            currentPoint = getRelativeCoords(e);
            
            const x = Math.min(startPoint.x, currentPoint.x);
            const y = Math.min(startPoint.y, currentPoint.y);
            const w = Math.abs(startPoint.x - currentPoint.x);
            const h = Math.abs(startPoint.y - currentPoint.y);

            if (w > 0.5 && h > 0.5) {
                const newAnnotation = {
                    id: crypto.randomUUID(),
                    x, y, width: w, height: h,
                    color: COLORS[annotations.length % COLORS.length],
                    title: getDefaultAnnotationTitle(),
                    isActive: true
                };
                annotations = [...annotations, newAnnotation];
                annotations = annotations.map(a => ({ ...a, isActive: a.id === newAnnotation.id }));
            }
        }

        isDrawing = false;
        startPoint = null;
        currentPoint = null;
    }

    function handleWheel(e) {
        syncModifierStateFromEvent(e);

        if (e.ctrlKey) {
            e.preventDefault();
            const factor = Math.pow(1.1, -e.deltaY / 100);
            const newScale = Math.min(Math.max(scale * factor, 0.5), 10);

            if (!stage) {
                scale = newScale;
                return;
            }

            const stageRect = stage.getBoundingClientRect();
            const insideStage =
                e.clientX >= stageRect.left &&
                e.clientX <= stageRect.right &&
                e.clientY >= stageRect.top &&
                e.clientY <= stageRect.bottom;

            let localX;
            let localY;

            if (insideStage) {
                localX = (e.clientX - stageRect.left) / scale;
                localY = (e.clientY - stageRect.top) / scale;
            } else {
                localX = stageRect.width / (2 * scale);
                localY = stageRect.height / (2 * scale);
            }

            offset = {
                x: offset.x - localX * (newScale - scale),
                y: offset.y - localY * (newScale - scale)
            };
            scale = newScale;
        } else {
            // Pan
            offset = {
                x: offset.x - e.deltaX,
                y: offset.y - e.deltaY
            };
        }
    }

</script>

<svelte:window onkeydown={syncModifierStateFromEvent} onkeyup={syncModifierStateFromEvent} onblur={clearModifierState} />

<!-- svelte-ignore a11y_no_static_element_interactions -->
<div 
    bind:this={container}
    onmousedown={handleMouseDown}
    onmousemove={handleMouseMove}
    onmouseup={handleMouseUp}
    onwheel={handleWheel}
    oncontextmenu={(e) => e.preventDefault()}
    class="relative w-full h-full flex flex-col items-center justify-center bg-slate-200 rounded-xl overflow-hidden shadow-inner select-none"
    style="cursor: {aiAssistPending ? 'progress' : isPanning ? 'grabbing' : isMoving ? 'move' : altPressed ? 'grab' : showCrosshair ? 'none' : 'default'}; touch-action: none;"
>
    
    <div 
        bind:this={stage}
        onmouseleave={handleStageLeave}
        class="canvas-stage relative origin-top-left"
        style="transform: translate({offset.x}px, {offset.y}px) scale({scale}); will-change: transform;"
    >
        <img bind:this={imageElement} src={imageSrc} alt="" onload={handleImageLoad} decoding="sync" loading="eager" fetchpriority="high" class="max-h-full max-w-full w-auto block pointer-events-none select-none shadow-2xl" draggable="false" />

        {#if showCrosshair}
            <div
                class="absolute inset-y-0 pointer-events-none z-30"
                style="
                    left: {hoverPoint.x}%;
                    margin-left: -0.5px;
                    width: 1px;
                    background-image: repeating-linear-gradient(
                        to bottom,
                        rgba(255, 255, 255, 0.95) 0 6px,
                        rgba(15, 23, 42, 0.95) 6px 12px
                    );
                "
            ></div>
            <div
                class="absolute inset-x-0 pointer-events-none z-30"
                style="
                    top: {hoverPoint.y}%;
                    margin-top: -0.5px;
                    height: 1px;
                    background-image: repeating-linear-gradient(
                        to right,
                        rgba(255, 255, 255, 0.95) 0 6px,
                        rgba(15, 23, 42, 0.95) 6px 12px
                    );
                "
            ></div>
            <div
                class="absolute pointer-events-none z-30 rounded-full"
                style="
                    left: {hoverPoint.x}%;
                    top: {hoverPoint.y}%;
                    margin-left: -4px;
                    margin-top: -4px;
                    width: 8px;
                    height: 8px;
                    background-color: rgba(255, 255, 255, 0.98);
                    border: 2px solid rgba(15, 23, 42, 0.95);
                    box-sizing: border-box;
                "
            ></div>
        {/if}
        
        {#each annotations as annot (annot.id)}
            <div 
                class="annotation-marker absolute border-2 {annot.isActive ? 'ring-2 ring-white z-50' : 'z-40'}"
                data-id={annot.id}
                style="
                    left: {annot.x}%;
                    top: {annot.y}%;
                    width: {annot.width}%;
                    height: {annot.height}%;
                    border: 2px solid {annot.color} !important;
                    background-color: {annot.isActive ? annot.color + '33' : 'transparent'};
                    display: block !important;
                    box-sizing: border-box;
                "
            >
                {#if annot.isActive}
                    <div 
                        class="annotation-label absolute bottom-full left-0 mb-1 whitespace-nowrap px-1.5 py-0.5 rounded text-[10px] font-bold text-white shadow-lg pointer-events-none"
                        style="
                            background-color: {annot.color};
                            transform: scale({1/scale});
                            transform-origin: bottom left;
                        "
                    >
                        {annot.title}
                    </div>

                    {#each resizeHandles as handle (handle.name)}
                        <div
                            class="resize-handle absolute bg-white border-2 border-blue-600 shadow-sm z-50"
                            data-handle={handle.name}
                            style={handle.style}
                        ></div>
                    {/each}
                {/if}
            </div>
        {/each}

        {#if isDrawing && startPoint && currentPoint}
             {@const x = Math.min(startPoint.x, currentPoint.x)}
             {@const y = Math.min(startPoint.y, currentPoint.y)}
             {@const w = Math.abs(startPoint.x - currentPoint.x)}
             {@const h = Math.abs(startPoint.y - currentPoint.y)}
            <div 
                class="absolute border-2 border-dashed border-white bg-blue-500/20 shadow-2xl pointer-events-none z-40"
                style="
                    left: {x}%;
                    top: {y}%;
                    width: {w}%;
                    height: {h}%;
                    border: 2px dashed white !important;
                    background-color: rgba(59, 130, 246, 0.3) !important;
                    z-index: 100 !important;
                    display: block !important;
                "
            ></div>
        {/if}
    </div>

    {#if aiAssistPending}
        <div class="absolute inset-x-0 top-4 z-[60] flex justify-center pointer-events-none">
            <div class="inline-flex items-center gap-2 rounded-full bg-slate-950/85 px-3 py-2 text-xs font-semibold text-white shadow-2xl ring-1 ring-white/10">
                <span class="h-2.5 w-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Detecting object...</span>
            </div>
        </div>
    {/if}

</div>
