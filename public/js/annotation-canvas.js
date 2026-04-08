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
    const ERASER_WIDTH            = 28;
    const RESIZE_DEBOUNCE_MS      = 100;
    const ORIENTATION_CHANGE_MS   = 300;  // iPad needs time to settle after rotation
    const COLORS = [
        '#1a1a2e',   // dark navy (default)
        '#e74c3c',   // red
        '#2ecc71',   // green
        '#3498db',   // blue
        '#f39c12',   // orange
        '#9b59b6',   // purple
        '#ffffff'    // white
    ];

    /* ── state ─────────────────────────────────────────── */
    let canvas, ctx;
    let overlay, toolbar;
    let isOpen        = false;
    let isDrawing     = false;
    let isErasing     = false;
    let currentColor  = COLORS[0];
    let lineWidth     = DEFAULT_LINE_WIDTH;
    let undoStack     = [];
    let redoStack     = [];
    let lastPoint     = null;
    let toggleBtn     = null;
    let points        = [];            // collected points for current stroke

    /* ── helpers ───────────────────────────────────────── */

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

    /* ── smooth quadratic curve rendering ──────────────── */
    function addPoint(pt) {
        points.push(pt);
    }

    /* ── pointer handlers ──────────────────────────────── */
    function onPointerDown(e) {
        // Palm rejection: ignore touch while using pencil/pen
        if (e.pointerType === 'touch' && isOpen) {
            e.preventDefault();
            return;
        }
        if (e.pointerType === 'mouse' && e.button !== 0) return;

        isDrawing = true;
        pushUndo();

        var pt = getCanvasPoint(e);
        points = [pt];
        lastPoint = pt;

        ctx.lineCap    = 'round';
        ctx.lineJoin   = 'round';
        ctx.strokeStyle = isErasing ? 'rgba(0,0,0,1)' : currentColor;
        ctx.globalCompositeOperation = isErasing ? 'destination-out' : 'source-over';
        ctx.lineWidth  = isErasing ? ERASER_WIDTH : lineWidth * (0.5 + (pt.pressure || 0.5) * 1.5);

        ctx.beginPath();
        ctx.arc(pt.x, pt.y, ctx.lineWidth / 2, 0, Math.PI * 2);
        ctx.fillStyle = isErasing ? 'rgba(0,0,0,1)' : currentColor;
        ctx.fill();

        canvas.setPointerCapture(e.pointerId);
        e.preventDefault();
    }

    function onPointerMove(e) {
        if (!isDrawing) return;
        e.preventDefault();

        // Use coalesced events for smoother lines (esp. on trackpads / mice)
        var events = (e.getCoalescedEvents && e.getCoalescedEvents().length)
                   ? e.getCoalescedEvents() : [e];

        for (var i = 0; i < events.length; i++) {
            var pt = getCanvasPoint(events[i]);
            addPoint(pt);

            if (points.length < 2) continue;

            var pressure = Math.max(pt.pressure, MIN_PRESSURE);
            ctx.lineWidth   = isErasing ? ERASER_WIDTH : lineWidth * (0.5 + pressure * 1.5);
            ctx.lineCap     = 'round';
            ctx.lineJoin    = 'round';
            ctx.strokeStyle = isErasing ? 'rgba(0,0,0,1)' : currentColor;
            ctx.globalCompositeOperation = isErasing ? 'destination-out' : 'source-over';

            ctx.beginPath();

            if (points.length < 3) {
                // First segment: simple line
                var p0 = points[points.length - 2];
                var p1 = points[points.length - 1];
                ctx.moveTo(p0.x, p0.y);
                ctx.lineTo(p1.x, p1.y);
            } else {
                // Smooth quadratic Bézier: draw from prev midpoint to curr midpoint
                var a  = points[points.length - 3];
                var b  = points[points.length - 2];
                var c  = points[points.length - 1];
                var prevMidX = (a.x + b.x) / 2;
                var prevMidY = (a.y + b.y) / 2;
                var currMidX = (b.x + c.x) / 2;
                var currMidY = (b.y + c.y) / 2;
                ctx.moveTo(prevMidX, prevMidY);
                ctx.quadraticCurveTo(b.x, b.y, currMidX, currMidY);
            }

            ctx.stroke();
        }

        lastPoint = points.length ? points[points.length - 1] : null;
    }

    function onPointerUp(e) {
        if (!isDrawing) return;
        isDrawing = false;

        // Draw final segment from last midpoint to actual endpoint
        if (points.length >= 2) {
            var last = points[points.length - 1];
            var prev = points[points.length - 2];
            var midX = (prev.x + last.x) / 2;
            var midY = (prev.y + last.y) / 2;
            var pressure = Math.max(last.pressure, MIN_PRESSURE);
            ctx.lineWidth   = isErasing ? ERASER_WIDTH : lineWidth * (0.5 + pressure * 1.5);
            ctx.lineCap     = 'round';
            ctx.lineJoin    = 'round';
            ctx.strokeStyle = isErasing ? 'rgba(0,0,0,1)' : currentColor;
            ctx.globalCompositeOperation = isErasing ? 'destination-out' : 'source-over';
            ctx.beginPath();
            ctx.moveTo(midX, midY);
            ctx.lineTo(last.x, last.y);
            ctx.stroke();
        }

        points    = [];
        lastPoint = null;
        ctx.globalCompositeOperation = 'source-over';
    }

    function onPointerCancel(e) {
        onPointerUp(e);
    }

    /* ── resize handler ────────────────────────────────── */
    function resizeCanvas() {
        if (!canvas) return;

        // Save current drawing
        var dataURL = canvas.toDataURL('image/png');

        var dpr = window.devicePixelRatio || 1;
        canvas.width  = window.innerWidth  * dpr;
        canvas.height = window.innerHeight * dpr;
        canvas.style.width  = window.innerWidth  + 'px';
        canvas.style.height = window.innerHeight + 'px';

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
        overlay.classList.remove('annot-visible');
        toggleBtn.classList.remove('annot-toggle-active');
        document.body.classList.remove('annot-mode-active');
    }

    function toggle() {
        isOpen ? close() : open();
    }

    /* ── toolbar colour / tool switching ───────────────── */
    function selectColor(color) {
        currentColor = color;
        isErasing = false;
        updateToolbarState();
    }

    function selectEraser() {
        isErasing = true;
        updateToolbarState();
    }

    function updateToolbarState() {
        if (!toolbar) return;
        toolbar.querySelectorAll('.annot-color-btn').forEach(function (btn) {
            btn.classList.toggle('annot-active', !isErasing && btn.dataset.color === currentColor);
        });
        var eraserBtn = toolbar.querySelector('.annot-eraser-btn');
        if (eraserBtn) eraserBtn.classList.toggle('annot-active', isErasing);
    }

    function setLineWidth(w) {
        lineWidth = Math.max(MIN_LINE_WIDTH, Math.min(MAX_LINE_WIDTH, w));
        updateSizeIndicator();
    }

    function updateSizeIndicator() {
        var indicator = document.getElementById('annot-size-indicator');
        if (indicator) indicator.textContent = lineWidth;
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
        canvas.setAttribute('touch-action', 'none');
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
        html += '<button class="annot-tool-btn annot-size-down" title="Thinner" aria-label="Decrease brush size"><i class="fas fa-minus"></i></button>';
        html += '<span id="annot-size-indicator" class="annot-size-indicator">' + lineWidth + '</span>';
        html += '<button class="annot-tool-btn annot-size-up" title="Thicker" aria-label="Increase brush size"><i class="fas fa-plus"></i></button>';
        html += '</div>';

        // Divider
        html += '<div class="annot-divider"></div>';

        // Tools
        html += '<div class="annot-section annot-tools">';
        html += '<button class="annot-tool-btn annot-eraser-btn" title="Eraser" aria-label="Eraser"><i class="fas fa-eraser"></i></button>';
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

        // Prevent default touch to avoid scrolling while drawing
        canvas.addEventListener('touchstart', function (e) { if (isOpen) e.preventDefault(); }, { passive: false });
        canvas.addEventListener('touchmove',  function (e) { if (isOpen) e.preventDefault(); }, { passive: false });

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
            } else if (btn.classList.contains('annot-eraser-btn')) {
                selectEraser();
            } else if (btn.id === 'annot-undo') {
                undo();
            } else if (btn.id === 'annot-redo') {
                redo();
            } else if (btn.classList.contains('annot-clear-btn')) {
                clearCanvas();
            } else if (btn.classList.contains('annot-close-btn')) {
                close();
            } else if (btn.classList.contains('annot-size-down')) {
                setLineWidth(lineWidth - 1);
            } else if (btn.classList.contains('annot-size-up')) {
                setLineWidth(lineWidth + 1);
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function (e) {
            if (!isOpen) return;

            // Escape to close
            if (e.key === 'Escape') {
                close();
                return;
            }

            // Ctrl+Z undo, Ctrl+Y redo
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
