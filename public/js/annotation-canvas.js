/**
 * ══════════════════════════════════════════════════════════
 *  Annotation Canvas – Full-Screen Drawing Overlay
 *  Optimised for iPad + Apple Pencil (pressure & tilt)
 *  Works equally well with mouse / finger on any device
 * ══════════════════════════════════════════════════════════
 */
;(function () {
    'use strict';

    /* ── constants ─────────────────────────────────────── */
    const UNDO_LIMIT              = 50;
    const MIN_PRESSURE            = 0.01;
    const DEFAULT_LINE_WIDTH      = 3;
    const MIN_LINE_WIDTH          = 1;
    const MAX_LINE_WIDTH          = 20;
    const DEFAULT_ERASER_SIZE     = 28;
    const MIN_ERASER_SIZE         = 8;
    const MAX_ERASER_SIZE         = 80;
    const DEFAULT_HIGHLIGHTER_SIZE = 22;
    const MIN_HIGHLIGHTER_SIZE    = 8;
    const MAX_HIGHLIGHTER_SIZE    = 60;
    const HIGHLIGHTER_ALPHA       = 0.35;
    const RESIZE_DEBOUNCE_MS      = 100;
    const ORIENTATION_CHANGE_MS   = 300;  // iPad needs time to settle after rotation
    const TAP_DISTANCE_THRESHOLD  = 8;    // max px movement to count as a tap
    const TAP_TIME_THRESHOLD      = 300;  // max ms for a tap
    const COLORS = [
        '#1a1a2e',   // dark navy (default)
        '#e74c3c',   // red
        '#2ecc71',   // green
        '#3498db',   // blue
        '#f39c12',   // orange
        '#9b59b6',   // purple
        '#ffffff'    // white
    ];

    /* ── tool modes ───────────────────────────────────── */
    const TOOL_PEN         = 'pen';
    const TOOL_HIGHLIGHTER = 'highlighter';
    const TOOL_ERASER      = 'eraser';

    /* ── state ─────────────────────────────────────────── */
    let canvas, ctx;
    let overlay, toolbar;
    let isOpen        = false;
    let isDrawing     = false;
    let activeTool    = TOOL_PEN;
    let currentColor  = COLORS[0];
    let lineWidth     = DEFAULT_LINE_WIDTH;
    let eraserSize    = DEFAULT_ERASER_SIZE;
    let highlighterSize = DEFAULT_HIGHLIGHTER_SIZE;
    let undoStack     = [];
    let redoStack     = [];
    let lastPoint     = null;
    let toggleBtn     = null;
    let points        = [];            // collected points for current stroke
    let activePointerId = null;        // track which pointer is drawing
    let activePointerType = null;      // 'pen', 'mouse', or 'touch'
    let hlCanvas      = null;          // off-screen canvas for highlighter strokes
    let hlCtx         = null;
    let preHighlightSnapshot = null;   // canvas state before current highlighter stroke
    let cursorCircle  = null;          // visual cursor circle for eraser/highlighter
    let strokeStarted = false;         // whether actual drawing has begun for current gesture
    let tapDownPoint  = null;          // record pointer-down position for tap detection

    /* ── helpers ───────────────────────────────────────── */

    function getBodyZoom() {
        var zoom = 1;
        try {
            var computedZoom = parseFloat(window.getComputedStyle(document.body).zoom || '1');
            if (Number.isFinite(computedZoom) && computedZoom > 0) {
                zoom = computedZoom;
            }
        } catch (error) {
            zoom = 1;
        }

        return zoom;
    }

    function getCanvasCssSize() {
        var doc = document.documentElement;
        var body = document.body;
        var zoom = getBodyZoom();

        var viewportWidth = window.innerWidth / zoom;
        var viewportHeight = window.innerHeight / zoom;

        var width = Math.max(
            viewportWidth,
            doc ? doc.scrollWidth : 0,
            doc ? doc.clientWidth : 0,
            body ? body.scrollWidth : 0,
            body ? body.clientWidth : 0
        );

        var height = Math.max(
            viewportHeight,
            doc ? doc.scrollHeight : 0,
            doc ? doc.clientHeight : 0,
            body ? body.scrollHeight : 0,
            body ? body.clientHeight : 0
        );

        return {
            width: Math.ceil(width),
            height: Math.ceil(height)
        };
    }

    /** Convert pointer coords to canvas-local coords (handles DPR + CSS sizing). */
    function getCanvasPoint(e) {
        const rect   = canvas.getBoundingClientRect();
        const scaleX = canvas.width  / rect.width;
        const scaleY = canvas.height / rect.height;
        return {
            x: (e.clientX - rect.left) * scaleX,
            y: (e.clientY - rect.top)  * scaleY,
            pressure: e.pressure ?? 0.5
        };
    }

    /** Save current canvas bitmap for undo. */
    function pushUndo() {
        if (undoStack.length >= UNDO_LIMIT) undoStack.shift();
        undoStack.push(canvas.toDataURL('image/png'));
        redoStack = [];
        updateUndoRedoBtns();
    }

    function undo() {
        if (!undoStack.length) return;
        redoStack.push(canvas.toDataURL('image/png'));
        const img = new Image();
        img.onload = function () {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, 0, 0);
        };
        img.src = undoStack.pop();
        updateUndoRedoBtns();
    }

    function redo() {
        if (!redoStack.length) return;
        undoStack.push(canvas.toDataURL('image/png'));
        const img = new Image();
        img.onload = function () {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, 0, 0);
        };
        img.src = redoStack.pop();
        updateUndoRedoBtns();
    }

    function clearCanvas() {
        pushUndo();
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    }

    function updateUndoRedoBtns() {
        var undoBtn = document.getElementById('annot-undo');
        var redoBtn = document.getElementById('annot-redo');
        if (undoBtn) undoBtn.classList.toggle('annot-disabled', !undoStack.length);
        if (redoBtn) redoBtn.classList.toggle('annot-disabled', !redoStack.length);
    }

    /* ── stroke style helpers ─────────────────────────────── */

    /** Get the current drawing width based on tool + pressure. */
    function getStrokeWidth(pressure) {
        if (activeTool === TOOL_ERASER)      return eraserSize;
        if (activeTool === TOOL_HIGHLIGHTER)  return highlighterSize;
        return lineWidth * (0.5 + (pressure || 0.5) * 1.5);
    }

    /** Apply the current tool's rendering style to ctx. */
    function applyStrokeStyle(pressure) {
        const isEraser = activeTool === TOOL_ERASER;
        const isHighlighter = activeTool === TOOL_HIGHLIGHTER;

        ctx.lineCap  = isHighlighter ? 'square' : 'round';
        ctx.lineJoin = isHighlighter ? 'bevel'  : 'round';

        if (isEraser) {
            ctx.strokeStyle = 'rgba(0,0,0,1)';
            ctx.fillStyle   = 'rgba(0,0,0,1)';
            ctx.globalAlpha = 1;
            ctx.globalCompositeOperation = 'destination-out';
        } else if (isHighlighter) {
            ctx.strokeStyle = currentColor;
            ctx.fillStyle   = currentColor;
            ctx.globalAlpha = HIGHLIGHTER_ALPHA;
            ctx.globalCompositeOperation = 'source-over';
        } else {
            ctx.strokeStyle = currentColor;
            ctx.fillStyle   = currentColor;
            ctx.globalAlpha = 1;
            ctx.globalCompositeOperation = 'source-over';
        }

        ctx.lineWidth = getStrokeWidth(pressure);
    }

    /** Reset composite/alpha back to defaults after a stroke ends. */
    function resetComposite() {
        ctx.globalCompositeOperation = 'source-over';
        ctx.globalAlpha = 1;
    }

    /* ── cursor circle helpers ────────────────────────── */

    /** Get the visual CSS-pixel radius of the current tool's brush. */
    function getCursorRadius() {
        var dpr = window.devicePixelRatio || 1;
        if (activeTool === TOOL_ERASER)      return eraserSize / dpr / 2;
        if (activeTool === TOOL_HIGHLIGHTER)  return highlighterSize / dpr / 2;
        return 0;
    }

    /** Show/hide and resize the cursor circle based on current tool. */
    function updateCursorCircleVisibility() {
        if (!cursorCircle) return;
        var showCircle = isOpen && (activeTool === TOOL_ERASER || activeTool === TOOL_HIGHLIGHTER);
        cursorCircle.style.display = showCircle ? 'block' : 'none';
        if (showCircle) {
            var r = getCursorRadius();
            var d = r * 2;
            cursorCircle.style.width  = d + 'px';
            cursorCircle.style.height = d + 'px';
            // Style based on tool
            if (activeTool === TOOL_ERASER) {
                cursorCircle.style.borderColor = 'rgba(255,255,255,0.9)';
                cursorCircle.style.boxShadow = '0 0 0 1px rgba(0,0,0,0.35), inset 0 0 0 1px rgba(220,53,69,0.10)';
                cursorCircle.style.background = 'rgba(220,53,69,0.06)';
            } else {
                cursorCircle.style.borderColor = 'rgba(255,255,255,0.85)';
                cursorCircle.style.boxShadow = '0 0 0 1px rgba(0,0,0,0.25), inset 0 0 0 1px rgba(245,158,11,0.10)';
                cursorCircle.style.background = 'rgba(245,158,11,0.06)';
            }
        }
    }

    /** Move the cursor circle to follow the pointer. */
    function positionCursorCircle(clientX, clientY) {
        if (!cursorCircle || cursorCircle.style.display === 'none') return;
        var zoom = getBodyZoom();
        cursorCircle.style.left = (clientX / zoom) + 'px';
        cursorCircle.style.top  = (clientY / zoom) + 'px';
    }

    /* ── tap-through helpers ──────────────────────────── */

    /**
     * Check whether an element (or its ancestor) is an interactive page element
     * that should receive forwarded taps through the annotation overlay.
     */
    function isInteractiveElement(el) {
        if (!el) return false;
        var match = el.closest(
            '.option-card, .check-answer, .next-question, .view-results, .btn-dont-know, ' +
            'button.btn, a.btn, textarea, input, select, a[href], label'
        );
        if (!match) return false;
        // Never forward to our own annotation controls
        if (match.closest('#annot-overlay, #annot-toggle-btn')) return false;
        return true;
    }

    /** Find the real page element beneath the annotation overlay at given coords. */
    function getElementBeneath(clientX, clientY) {
        overlay.style.pointerEvents = 'none';
        canvas.style.pointerEvents  = 'none';
        if (cursorCircle) cursorCircle.style.display = 'none';
        var el = document.elementFromPoint(clientX, clientY);
        overlay.style.pointerEvents = '';
        canvas.style.pointerEvents  = '';
        updateCursorCircleVisibility();
        return el;
    }

    /* ── deferred stroke begin ────────────────────────── */

    /** Actually start drawing (pushUndo + initial dot). Called on first move or tap-on-empty. */
    function beginStroke(pt) {
        if (strokeStarted) return;
        strokeStarted = true;
        pushUndo();

        if (activeTool === TOOL_HIGHLIGHTER) {
            if (!hlCanvas) {
                hlCanvas = document.createElement('canvas');
                hlCtx = hlCanvas.getContext('2d');
            }
            hlCanvas.width  = canvas.width;
            hlCanvas.height = canvas.height;
            hlCtx.clearRect(0, 0, hlCanvas.width, hlCanvas.height);

            preHighlightSnapshot = ctx.getImageData(0, 0, canvas.width, canvas.height);

            hlCtx.lineCap  = 'square';
            hlCtx.lineJoin = 'bevel';
            hlCtx.fillStyle = currentColor;
            hlCtx.globalAlpha = 1;
            hlCtx.lineWidth = highlighterSize;
            hlCtx.beginPath();
            hlCtx.arc(pt.x, pt.y, highlighterSize / 2, 0, Math.PI * 2);
            hlCtx.fill();

            ctx.putImageData(preHighlightSnapshot, 0, 0);
            ctx.save();
            ctx.globalAlpha = HIGHLIGHTER_ALPHA;
            ctx.drawImage(hlCanvas, 0, 0);
            ctx.restore();
        } else {
            preHighlightSnapshot = null;
            applyStrokeStyle(pt.pressure);
            ctx.beginPath();
            ctx.arc(pt.x, pt.y, ctx.lineWidth / 2, 0, Math.PI * 2);
            ctx.fill();
        }
    }

    /* ── pointer handlers ──────────────────────────────── */
    function onPointerDown(e) {
        // Palm rejection: ignore finger-touch when a pen is being used
        if (e.pointerType === 'touch' && activePointerId !== null) {
            e.preventDefault();
            return;
        }
        if (e.pointerType === 'mouse' && e.button !== 0) return;
        // Only allow one pointer at a time
        if (activePointerId !== null) return;

        activePointerId = e.pointerId;
        activePointerType = e.pointerType;   // 'pen', 'mouse', or 'touch'
        isDrawing = true;
        strokeStarted = false;   // defer actual drawing until first move

        var pt = getCanvasPoint(e);
        points = [pt];
        lastPoint = pt;

        // Record tap start position for click-through detection
        tapDownPoint = { x: e.clientX, y: e.clientY, time: Date.now() };

        canvas.setPointerCapture(e.pointerId);
        e.preventDefault();
    }

    function onPointerMove(e) {
        // Always update the cursor circle position (even when not drawing)
        positionCursorCircle(e.clientX, e.clientY);

        if (!isDrawing || e.pointerId !== activePointerId) return;
        e.preventDefault();

        // Begin the stroke on first movement (deferred from pointerDown for tap detection)
        if (!strokeStarted) {
            beginStroke(points[0]);
        }

        // Use coalesced events for smoother lines (esp. on trackpads / mice)
        var events = (e.getCoalescedEvents && e.getCoalescedEvents().length)
                   ? e.getCoalescedEvents() : [e];

        if (activeTool === TOOL_HIGHLIGHTER) {
            // Draw all coalesced segments to offscreen canvas
            for (var i = 0; i < events.length; i++) {
                var pt = getCanvasPoint(events[i]);
                points.push(pt);
                if (points.length < 2) continue;

                hlCtx.lineWidth  = highlighterSize;
                hlCtx.lineCap    = 'square';
                hlCtx.lineJoin   = 'bevel';
                hlCtx.strokeStyle = currentColor;
                hlCtx.globalAlpha = 1;

                hlCtx.beginPath();
                if (points.length < 3) {
                    var p0 = points[points.length - 2];
                    var p1 = points[points.length - 1];
                    hlCtx.moveTo(p0.x, p0.y);
                    hlCtx.lineTo(p1.x, p1.y);
                } else {
                    var a  = points[points.length - 3];
                    var b  = points[points.length - 2];
                    var c  = points[points.length - 1];
                    hlCtx.moveTo((a.x + b.x) / 2, (a.y + b.y) / 2);
                    hlCtx.quadraticCurveTo(b.x, b.y, (b.x + c.x) / 2, (b.y + c.y) / 2);
                }
                hlCtx.stroke();
            }

            // Recomposite: restore snapshot, then overlay the full offscreen at reduced alpha
            ctx.putImageData(preHighlightSnapshot, 0, 0);
            ctx.save();
            ctx.globalAlpha = HIGHLIGHTER_ALPHA;
            ctx.drawImage(hlCanvas, 0, 0);
            ctx.restore();
        } else {
            // Pen / Eraser: draw directly
            for (var j = 0; j < events.length; j++) {
                var pt2 = getCanvasPoint(events[j]);
                points.push(pt2);
                if (points.length < 2) continue;

                applyStrokeStyle(pt2.pressure);
                ctx.beginPath();

                if (points.length < 3) {
                    var s0 = points[points.length - 2];
                    var s1 = points[points.length - 1];
                    ctx.moveTo(s0.x, s0.y);
                    ctx.lineTo(s1.x, s1.y);
                } else {
                    var sa = points[points.length - 3];
                    var sb = points[points.length - 2];
                    var sc = points[points.length - 1];
                    ctx.moveTo((sa.x + sb.x) / 2, (sa.y + sb.y) / 2);
                    ctx.quadraticCurveTo(sb.x, sb.y, (sb.x + sc.x) / 2, (sb.y + sc.y) / 2);
                }
                ctx.stroke();
            }
        }

        lastPoint = points.length ? points[points.length - 1] : null;
    }

    function onPointerUp(e) {
        if (!isDrawing || e.pointerId !== activePointerId) return;

        /* ── Tap-through detection ────────────────────────── */
        if (!strokeStarted && tapDownPoint) {
            var dx = e.clientX - tapDownPoint.x;
            var dy = e.clientY - tapDownPoint.y;
            var dist = Math.sqrt(dx * dx + dy * dy);
            var elapsed = Date.now() - tapDownPoint.time;

            if (dist < TAP_DISTANCE_THRESHOLD && elapsed < TAP_TIME_THRESHOLD) {
                // It was a tap — check for interactive elements beneath the overlay
                var beneath = getElementBeneath(e.clientX, e.clientY);
                if (beneath && isInteractiveElement(beneath)) {
                    // Clean up drawing state without drawing anything
                    isDrawing = false;
                    activePointerId = null;
                    activePointerType = null;
                    tapDownPoint = null;
                    points = [];
                    lastPoint = null;

                    // Forward interaction to the underlying element
                    var target = beneath.closest(
                        '.option-card, button, a, input, select, label'
                    ) || beneath;

                    // For textareas / inputs: focus so the user can type immediately
                    if (beneath.tagName === 'TEXTAREA' || beneath.tagName === 'INPUT') {
                        beneath.focus();
                        // Temporarily close annotation mode so user can type
                        close();
                    } else {
                        target.click();
                    }
                    return;
                }
            }

            // Not an interactive tap — draw a dot at this position
            beginStroke(points[0]);
        }

        /* ── Normal stroke end ────────────────────────────── */
        isDrawing = false;
        activePointerId = null;
        activePointerType = null;
        tapDownPoint = null;

        if (activeTool === TOOL_HIGHLIGHTER && preHighlightSnapshot) {
            // Draw final segment on offscreen
            if (points.length >= 2) {
                var last = points[points.length - 1];
                var prev = points[points.length - 2];
                hlCtx.lineWidth  = highlighterSize;
                hlCtx.lineCap    = 'square';
                hlCtx.lineJoin   = 'bevel';
                hlCtx.strokeStyle = currentColor;
                hlCtx.globalAlpha = 1;
                hlCtx.beginPath();
                hlCtx.moveTo((prev.x + last.x) / 2, (prev.y + last.y) / 2);
                hlCtx.lineTo(last.x, last.y);
                hlCtx.stroke();
            }

            // Final composite
            ctx.putImageData(preHighlightSnapshot, 0, 0);
            ctx.save();
            ctx.globalAlpha = HIGHLIGHTER_ALPHA;
            ctx.drawImage(hlCanvas, 0, 0);
            ctx.restore();

            preHighlightSnapshot = null;
        } else {
            // Pen / Eraser: draw final segment
            if (points.length >= 2) {
                var last2 = points[points.length - 1];
                var prev2 = points[points.length - 2];
                var midX = (prev2.x + last2.x) / 2;
                var midY = (prev2.y + last2.y) / 2;
                applyStrokeStyle(last2.pressure);
                ctx.beginPath();
                ctx.moveTo(midX, midY);
                ctx.lineTo(last2.x, last2.y);
                ctx.stroke();
            }
        }

        points    = [];
        lastPoint = null;
        resetComposite();
    }

    function onPointerCancel(e) {
        if (e.pointerId !== activePointerId) return;
        // Clean up without drawing — gesture was cancelled (e.g. browser initiated scroll)
        isDrawing = false;
        activePointerId = null;
        activePointerType = null;
        strokeStarted = false;
        tapDownPoint = null;
        points = [];
        lastPoint = null;
        preHighlightSnapshot = null;
        resetComposite();
    }

    /* ── resize handler ────────────────────────────────── */
    function resizeCanvas() {
        if (!canvas) return;

        // Save current drawing
        var dataURL = canvas.toDataURL('image/png');
        var size = getCanvasCssSize();

        var dpr = window.devicePixelRatio || 1;
        canvas.width  = Math.max(1, Math.floor(size.width * dpr));
        canvas.height = Math.max(1, Math.floor(size.height * dpr));
        canvas.style.width  = size.width + 'px';
        canvas.style.height = size.height + 'px';

        if (overlay) {
            overlay.style.width = size.width + 'px';
            overlay.style.height = size.height + 'px';
        }

        // Restore drawing
        var img = new Image();
        img.onload = function () {
            ctx.drawImage(img, 0, 0);
        };
        img.src = dataURL;
    }

    /* ── toggle annotation mode ────────────────────────── */
    function open() {
        if (isOpen) return;
        isOpen = true;
        overlay.classList.add('annot-visible');
        toggleBtn.classList.add('annot-toggle-active');
        document.body.classList.add('annot-mode-active');
        resizeCanvas();
    }

    function close() {
        if (!isOpen) return;
        isOpen = false;
        isDrawing = false;
        activePointerId = null;
        activePointerType = null;
        strokeStarted = false;
        tapDownPoint = null;
        overlay.classList.remove('annot-visible');
        toggleBtn.classList.remove('annot-toggle-active');
        document.body.classList.remove('annot-mode-active');
        if (cursorCircle) cursorCircle.style.display = 'none';
    }

    function toggle() {
        isOpen ? close() : open();
    }

    /* ── toolbar colour / tool switching ───────────────── */
    function selectColor(color) {
        currentColor = color;
        if (activeTool === TOOL_ERASER) activeTool = TOOL_PEN;
        updateToolbarState();
    }

    function selectTool(tool) {
        activeTool = tool;
        updateToolbarState();
    }

    function updateToolbarState() {
        if (!toolbar) return;
        toolbar.querySelectorAll('.annot-color-btn').forEach(function (btn) {
            btn.classList.toggle('annot-active', activeTool !== TOOL_ERASER && btn.dataset.color === currentColor);
        });
        var eraserBtn = toolbar.querySelector('.annot-eraser-btn');
        if (eraserBtn) eraserBtn.classList.toggle('annot-active', activeTool === TOOL_ERASER);

        var penBtn = toolbar.querySelector('.annot-pen-btn');
        if (penBtn) penBtn.classList.toggle('annot-active', activeTool === TOOL_PEN);

        var highlighterBtn = toolbar.querySelector('.annot-highlighter-btn');
        if (highlighterBtn) highlighterBtn.classList.toggle('annot-active', activeTool === TOOL_HIGHLIGHTER);

        // Update canvas cursor — hide system cursor when cursor circle is shown
        if (canvas) {
            if (activeTool === TOOL_ERASER || activeTool === TOOL_HIGHLIGHTER) {
                canvas.style.cursor = 'none';
            } else {
                canvas.style.cursor = 'crosshair';
            }
        }

        // Sync cursor circle visibility + size
        updateCursorCircleVisibility();

        // Sync size indicator to current tool's size
        updateSizeIndicator();
    }

    function setLineWidth(w) {
        if (activeTool === TOOL_ERASER) {
            eraserSize = Math.max(MIN_ERASER_SIZE, Math.min(MAX_ERASER_SIZE, w));
        } else if (activeTool === TOOL_HIGHLIGHTER) {
            highlighterSize = Math.max(MIN_HIGHLIGHTER_SIZE, Math.min(MAX_HIGHLIGHTER_SIZE, w));
        } else {
            lineWidth = Math.max(MIN_LINE_WIDTH, Math.min(MAX_LINE_WIDTH, w));
        }
        updateSizeIndicator();
        updateCursorCircleVisibility();
    }

    /** Return the current brush size for the active tool. */
    function getCurrentBrushSize() {
        if (activeTool === TOOL_ERASER) return eraserSize;
        if (activeTool === TOOL_HIGHLIGHTER) return highlighterSize;
        return lineWidth;
    }

    function updateSizeIndicator() {
        var indicator = document.getElementById('annot-size-indicator');
        if (!indicator) return;
        if (activeTool === TOOL_ERASER) {
            indicator.textContent = eraserSize;
        } else if (activeTool === TOOL_HIGHLIGHTER) {
            indicator.textContent = highlighterSize;
        } else {
            indicator.textContent = lineWidth;
        }
    }

    /* ── build DOM ─────────────────────────────────────── */
    function buildUI() {
        // Overlay container (covers entire viewport)
        overlay = document.createElement('div');
        overlay.id = 'annot-overlay';
        overlay.className = 'annot-overlay';

        // Canvas
        canvas = document.createElement('canvas');
        canvas.id = 'annot-canvas';
        canvas.className = 'annot-canvas';
        canvas.setAttribute('touch-action', 'manipulation');
        overlay.appendChild(canvas);

        // Toolbar
        toolbar = document.createElement('div');
        toolbar.id = 'annot-toolbar';
        toolbar.className = 'annot-toolbar';

        // Prevent toolbar interactions from drawing
        toolbar.addEventListener('pointerdown', function (e) { e.stopPropagation(); });
        toolbar.addEventListener('pointermove', function (e) { e.stopPropagation(); });
        toolbar.addEventListener('pointerup', function (e) { e.stopPropagation(); });
        toolbar.addEventListener('touchstart', function (e) { e.stopPropagation(); }, { passive: false });
        toolbar.addEventListener('touchmove', function (e) { e.stopPropagation(); }, { passive: false });

        var html = '';

        // Colour buttons
        html += '<div class="annot-section annot-colors">';
        COLORS.forEach(function (c) {
            var border = c === '#ffffff' ? 'border:1.5px solid #ccc;' : '';
            html += '<button class="annot-color-btn' + (c === currentColor ? ' annot-active' : '') +
                     '" data-color="' + c + '" style="background:' + c + ';' + border + '" title="' + c + '" aria-label="Color ' + c + '"></button>';
        });
        html += '</div>';

        // Divider
        html += '<div class="annot-divider"></div>';

        // Size controls
        html += '<div class="annot-section annot-size-section">';
        html += '<button class="annot-tool-btn annot-size-down" title="Thinner ([)" aria-label="Decrease brush size"><i class="fas fa-minus"></i></button>';
        html += '<span id="annot-size-indicator" class="annot-size-indicator">' + lineWidth + '</span>';
        html += '<button class="annot-tool-btn annot-size-up" title="Thicker (])" aria-label="Increase brush size"><i class="fas fa-plus"></i></button>';
        html += '</div>';

        // Divider
        html += '<div class="annot-divider"></div>';

        // Tools
        html += '<div class="annot-section annot-tools">';
        html += '<button class="annot-tool-btn annot-pen-btn annot-active" title="Pen (P)" aria-label="Pen tool"><i class="fas fa-pen"></i></button>';
        html += '<button class="annot-tool-btn annot-highlighter-btn" title="Highlighter (H)" aria-label="Highlighter tool"><i class="fas fa-highlighter"></i></button>';
        html += '<button class="annot-tool-btn annot-eraser-btn" title="Eraser (E)" aria-label="Eraser"><i class="fas fa-eraser"></i></button>';
        html += '</div>';

        // Divider
        html += '<div class="annot-divider"></div>';

        // Actions
        html += '<div class="annot-section annot-actions">';
        html += '<button class="annot-tool-btn" id="annot-undo" title="Undo (Ctrl+Z)" aria-label="Undo"><i class="fas fa-undo"></i></button>';
        html += '<button class="annot-tool-btn" id="annot-redo" title="Redo (Ctrl+Y)" aria-label="Redo"><i class="fas fa-redo"></i></button>';
        html += '<button class="annot-tool-btn annot-clear-btn" title="Clear All" aria-label="Clear canvas"><i class="fas fa-trash-alt"></i></button>';
        html += '</div>';

        // Divider
        html += '<div class="annot-divider"></div>';

        // Close
        html += '<div class="annot-section">';
        html += '<button class="annot-tool-btn annot-close-btn" title="Close annotations" aria-label="Close annotations"><i class="fas fa-times"></i></button>';
        html += '</div>';

        toolbar.innerHTML = html;
        overlay.appendChild(toolbar);

        // Toggle button (always visible in tutor mode)
        toggleBtn = document.createElement('button');
        toggleBtn.id = 'annot-toggle-btn';
        toggleBtn.className = 'annot-toggle-btn';
        toggleBtn.title = 'Toggle Annotations (Draw on screen)';
        toggleBtn.setAttribute('aria-label', 'Toggle annotation mode');
        toggleBtn.innerHTML = '<i class="fas fa-pen-fancy"></i>';

        // Append to body
        document.body.appendChild(overlay);
        document.body.appendChild(toggleBtn);

        // Cursor circle (follows pointer for eraser / highlighter)
        cursorCircle = document.createElement('div');
        cursorCircle.className = 'annot-cursor-circle';
        cursorCircle.style.display = 'none';
        document.body.appendChild(cursorCircle);
    }

    /* ── bind events ───────────────────────────────────── */
    function bindEvents() {
        ctx = canvas.getContext('2d', { willReadFrequently: false });

        // Pointer events on canvas
        canvas.addEventListener('pointerdown',   onPointerDown, { passive: false });
        canvas.addEventListener('pointermove',   onPointerMove, { passive: false });
        canvas.addEventListener('pointerup',     onPointerUp);
        canvas.addEventListener('pointercancel', onPointerCancel);
        canvas.addEventListener('pointerleave',  onPointerUp);

        // Touch handlers: only block native scroll when actively drawing with pen/mouse
        // Finger (touch) gestures pass through so the browser can scroll the page
        canvas.addEventListener('touchstart', function (e) {
            if (isOpen && isDrawing && activePointerType !== 'touch') e.preventDefault();
        }, { passive: false });
        canvas.addEventListener('touchmove',  function (e) {
            if (isOpen && isDrawing && activePointerType !== 'touch') e.preventDefault();
        }, { passive: false });

        // Toggle button
        toggleBtn.addEventListener('click', toggle);
        toggleBtn.addEventListener('touchend', function (e) {
            e.preventDefault();
            toggle();
        });

        // Toolbar click delegation
        toolbar.addEventListener('click', function (e) {
            var btn = e.target.closest('button');
            if (!btn) return;

            if (btn.classList.contains('annot-color-btn')) {
                selectColor(btn.dataset.color);
            } else if (btn.classList.contains('annot-pen-btn')) {
                selectTool(TOOL_PEN);
            } else if (btn.classList.contains('annot-highlighter-btn')) {
                selectTool(TOOL_HIGHLIGHTER);
            } else if (btn.classList.contains('annot-eraser-btn')) {
                selectTool(TOOL_ERASER);
            } else if (btn.id === 'annot-undo') {
                undo();
            } else if (btn.id === 'annot-redo') {
                redo();
            } else if (btn.classList.contains('annot-clear-btn')) {
                clearCanvas();
            } else if (btn.classList.contains('annot-close-btn')) {
                close();
            } else if (btn.classList.contains('annot-size-down')) {
                setLineWidth(getCurrentBrushSize() - 1);
            } else if (btn.classList.contains('annot-size-up')) {
                setLineWidth(getCurrentBrushSize() + 1);
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function (e) {
            if (!isOpen) return;
            // Don't capture keys when focused on an input/textarea
            var tag = (document.activeElement || {}).tagName;
            if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;

            // Escape to close
            if (e.key === 'Escape') {
                close();
                return;
            }

            // Ctrl+Z undo, Ctrl+Shift+Z / Ctrl+Y redo
            if ((e.ctrlKey || e.metaKey) && e.key === 'z') {
                e.preventDefault();
                if (e.shiftKey) redo(); else undo();
                return;
            }
            if ((e.ctrlKey || e.metaKey) && e.key === 'y') {
                e.preventDefault();
                redo();
                return;
            }

            // Tool shortcuts (only when no modifier key)
            if (!e.ctrlKey && !e.metaKey && !e.altKey) {
                if (e.key === 'p' || e.key === 'P') { selectTool(TOOL_PEN); return; }
                if (e.key === 'h' || e.key === 'H') { selectTool(TOOL_HIGHLIGHTER); return; }
                if (e.key === 'e' || e.key === 'E') { selectTool(TOOL_ERASER); return; }

                // Bracket keys for size
                if (e.key === '[') {
                    setLineWidth(getCurrentBrushSize() - 2);
                    return;
                }
                if (e.key === ']') {
                    setLineWidth(getCurrentBrushSize() + 2);
                    return;
                }
            }
        });

        // Scroll wheel: normal = scroll page (native), Ctrl/Meta+wheel = adjust brush size
        canvas.addEventListener('wheel', function (e) {
            if (!isOpen) return;
            if (e.ctrlKey || e.metaKey) {
                // Modifier + wheel = adjust brush size (standard design-tool shortcut)
                e.preventDefault();
                var delta = e.deltaY > 0 ? -1 : 1;
                setLineWidth(getCurrentBrushSize() + delta);
            }
            // Normal wheel: do NOT preventDefault — let the browser scroll naturally
        }, { passive: false });

        // Hide cursor circle when pointer leaves the canvas area
        canvas.addEventListener('pointerleave', function () {
            if (!cursorCircle || isDrawing) return;
            cursorCircle.style.display = 'none';
        });
        canvas.addEventListener('pointerenter', function () {
            updateCursorCircleVisibility();
        });

        // Window resize
        var resizeTimer;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(resizeCanvas, RESIZE_DEBOUNCE_MS);
        });

        // Orientation change (iPad)
        window.addEventListener('orientationchange', function () {
            setTimeout(resizeCanvas, ORIENTATION_CHANGE_MS);
        });

        updateUndoRedoBtns();
    }

    /* ── init ──────────────────────────────────────────── */
    function init() {
        buildUI();
        bindEvents();
        resizeCanvas();
    }

    // Boot once DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Public API
    window.AnnotationCanvas = {
        open: open,
        close: close,
        toggle: toggle,
        clear: clearCanvas,
        undo: undo,
        redo: redo,
        isOpen: function () { return isOpen; }
    };
})();
