@if (
    config('features.design_protection_enabled', false)
    && !(
        config('features.design_protection_disable_for_admins', true)
        && auth()->check()
        && auth()->user()->isAdmin()
    )
)
    @php
        $secureStudyUser = auth()->user();
        $secureStudyLabel = trim(implode(' | ', array_filter([
            $secureStudyUser ? 'User '.$secureStudyUser->id : null,
        ])));
        $secureStudyConfig = [
            'surfaceLabel' => $surfaceLabel ?? 'study',
            'blockShortcuts' => (bool) config('features.design_protection_block_shortcuts', true),
            'blockCopy' => (bool) config('features.design_protection_block_copy', true),
            'blockPrint' => (bool) config('features.design_protection_block_print', true),
            'hideOnBackground' => (bool) ($hideOnBackground ?? config('features.design_protection_hide_on_background', true)),
            'blurOnBackgroundDesktop' => (bool) config('features.design_protection_blur_on_background_desktop', false),
            'watermarkEnabled' => (bool) ($watermarkEnabled ?? config('features.design_protection_watermark_enabled', true)),
            'allowSelection' => (bool) ($allowSelection ?? false),
            'backgroundLockDelayMs' => (int) config('features.design_protection_background_lock_delay_ms', 120),
            'watermarkText' => $secureStudyLabel !== '' ? $secureStudyLabel : 'Protected MedCapsule Content',
        ];
    @endphp

    <style>
        .secure-study-overlay {
            position: fixed;
            inset: 0;
            z-index: 2147483000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: rgba(2, 6, 23, 0.96);
            color: #f8fafc;
            text-align: center;
        }

        .secure-study-overlay.is-visible {
            display: flex;
        }

        .secure-study-overlay__card {
            width: min(540px, 100%);
            padding: 28px 24px;
            border-radius: 28px;
            border: 1px solid rgba(148, 163, 184, 0.32);
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.98), rgba(15, 23, 42, 0.9));
            box-shadow: 0 24px 80px rgba(2, 6, 23, 0.45);
        }

        .secure-study-overlay__pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 160px;
            padding: 10px 18px;
            border-radius: 999px;
            background: rgba(59, 130, 246, 0.18);
            border: 1px solid rgba(96, 165, 250, 0.35);
            color: #bfdbfe;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .secure-study-overlay__title {
            margin: 18px 0 10px;
            font-size: clamp(1.4rem, 2vw, 1.8rem);
            font-weight: 700;
        }

        .secure-study-overlay__text {
            margin: 0;
            color: rgba(226, 232, 240, 0.86);
            line-height: 1.65;
        }

        .secure-study-overlay__meta {
            margin-top: 16px;
            color: rgba(148, 163, 184, 0.9);
            font-size: 0.9rem;
        }

        body.secure-study-hardened {
            -webkit-touch-callout: none !important;
            -webkit-user-drag: none !important;
        }

        body.secure-study-hardened img,
        body.secure-study-hardened svg,
        body.secure-study-hardened canvas {
            -webkit-user-drag: none !important;
            user-drag: none !important;
        }

        body.secure-study-hardened.secure-study-no-select,
        body.secure-study-hardened.secure-study-no-select *:not(input):not(textarea):not(select):not(option):not([contenteditable=""]):not([contenteditable="true"]):not([data-allow-select]):not(.written-answer):not(.edit-input) {
            user-select: none !important;
            -webkit-user-select: none !important;
        }

        .secure-study-watermark-layer {
            position: fixed;
            inset: 0;
            z-index: 2147482500;
            pointer-events: none;
            overflow: hidden;
            opacity: 0.24;
            user-select: none;
            -webkit-user-select: none;
        }

        .secure-study-watermark-grid {
            position: absolute;
            inset: -15%;
            display: grid;
            grid-template-columns: repeat(4, minmax(160px, 1fr));
            gap: 56px;
            transform: rotate(-24deg);
        }

        .secure-study-watermark-item {
            color: rgba(15, 23, 42, 0.5);
            font-size: clamp(0.88rem, 1.1vw, 1rem);
            font-weight: 700;
            letter-spacing: 0.08em;
            line-height: 1.7;
            text-transform: uppercase;
            white-space: pre-line;
        }

        body.secure-study-overlay-active {
            overflow: hidden !important;
        }

        body.secure-study-away > :not(.secure-study-overlay):not(.secure-study-watermark-layer) {
            visibility: hidden !important;
        }

        body.secure-study-away .secure-study-overlay,
        body.secure-study-away .secure-study-watermark-layer {
            visibility: visible !important;
        }

        body.secure-study-background-blur > :not(.secure-study-overlay):not(.secure-study-watermark-layer) {
            filter: blur(12px);
            pointer-events: none !important;
            user-select: none !important;
            -webkit-user-select: none !important;
        }

        @media print {
            body * {
                visibility: hidden !important;
            }
        }

        @media (max-width: 768px) {
            .secure-study-watermark-grid {
                grid-template-columns: repeat(3, minmax(140px, 1fr));
                gap: 36px;
            }

            .secure-study-overlay__card {
                border-radius: 24px;
            }
        }
    </style>

    <script>
        (() => {
            if (window.__medcapsuleSecureStudyProtectionLoaded) {
                return;
            }

            window.__medcapsuleSecureStudyProtectionLoaded = true;

            const config = @json($secureStudyConfig);
            let overlay = null;
            let overlayTitle = null;
            let overlayText = null;
            let overlayMeta = null;
            let backgroundTimer = null;
            let selectionResetTimer = null;
            let awayLocked = false;
            const userAgent = navigator.userAgent || '';
            const platform = navigator.platform || '';
            const isTouchMac = platform === 'MacIntel' && Number(navigator.maxTouchPoints || 0) > 1;
            const isPhoneOrTabletBrowser =
                /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(userAgent) ||
                isTouchMac;

            const isEditableTarget = (target) => {
                if (!(target instanceof HTMLElement)) {
                    return false;
                }

                return Boolean(target.closest('input, textarea, select, [contenteditable=""], [contenteditable="true"], [data-allow-copy], [data-allow-select], .edit-input, .written-answer'));
            };

            const resolveElement = (node) => {
                if (!node) {
                    return null;
                }

                if (node instanceof HTMLElement) {
                    return node;
                }

                return node.parentElement instanceof HTMLElement ? node.parentElement : null;
            };

            const selectionAllowed = () => {
                if (config.allowSelection) {
                    return true;
                }

                const selection = window.getSelection ? window.getSelection() : null;
                if (!selection || selection.rangeCount === 0 || selection.isCollapsed) {
                    return true;
                }

                const anchorElement = resolveElement(selection.anchorNode);
                const focusElement = resolveElement(selection.focusNode);

                return isEditableTarget(anchorElement) || isEditableTarget(focusElement);
            };

            const clearBlockedSelection = () => {
                if (config.allowSelection) {
                    return;
                }

                const selection = window.getSelection ? window.getSelection() : null;
                if (!selection || selection.rangeCount === 0 || selection.isCollapsed || selectionAllowed()) {
                    return;
                }

                selection.removeAllRanges();
            };

            const ensureOverlay = () => {
                if (overlay || !document.body) {
                    return overlay;
                }

                overlay = document.createElement('div');
                overlay.className = 'secure-study-overlay';
                overlay.setAttribute('role', 'alert');
                overlay.setAttribute('aria-live', 'assertive');
                overlay.innerHTML = `
                    <div class="secure-study-overlay__card">
                        <div class="secure-study-overlay__pill">Protected Session</div>
                        <h2 class="secure-study-overlay__title"></h2>
                        <p class="secure-study-overlay__text"></p>
                        <div class="secure-study-overlay__meta"></div>
                    </div>
                `;

                document.body.appendChild(overlay);
                overlayTitle = overlay.querySelector('.secure-study-overlay__title');
                overlayText = overlay.querySelector('.secure-study-overlay__text');
                overlayMeta = overlay.querySelector('.secure-study-overlay__meta');

                return overlay;
            };

            const ensureWatermark = () => {
                if (!config.watermarkEnabled || !document.body || document.querySelector('.secure-study-watermark-layer')) {
                    return;
                }

                const watermarkLayer = document.createElement('div');
                watermarkLayer.className = 'secure-study-watermark-layer';
                const watermarkGrid = document.createElement('div');
                watermarkGrid.className = 'secure-study-watermark-grid';

                const watermarkText = config.watermarkText;

                for (let index = 0; index < 12; index += 1) {
                    const item = document.createElement('div');
                    item.className = 'secure-study-watermark-item';
                    item.textContent = watermarkText;
                    watermarkGrid.appendChild(item);
                }

                watermarkLayer.appendChild(watermarkGrid);
                document.body.appendChild(watermarkLayer);
            };

            const showOverlay = (reason) => {
                const activeOverlay = ensureOverlay();
                if (!activeOverlay) {
                    return;
                }

                const copy = {
                    background: {
                        title: 'Session hidden while you are away',
                        text: 'Protected study content is hidden until you return to this tab.',
                    },
                    shortcut: {
                        title: 'Inspection and export shortcuts are blocked',
                        text: 'This study surface blocks common inspect, save, print, and capture shortcuts.',
                    },
                    copy: {
                        title: 'Copying is blocked on this session',
                        text: 'Protected study content cannot be copied from this page.',
                    },
                    print: {
                        title: 'Printing is disabled for this session',
                        text: 'Protected study content cannot be printed from this page.',
                    },
                    capture: {
                        title: 'Capture shortcut detected',
                        text: 'This session reacted to a capture key. Content remains watermarked and protected.',
                    },
                }[reason] ?? {
                    title: 'Protected session active',
                    text: 'Sensitive study content is protected on this page.',
                };

                overlayTitle.textContent = copy.title;
                overlayText.textContent = copy.text;
                overlayMeta.textContent = config.watermarkText;
                activeOverlay.classList.add('is-visible');
                document.body.classList.add('secure-study-overlay-active');
            };

            const hideOverlay = () => {
                if (!overlay) {
                    return;
                }

                overlay.classList.remove('is-visible');
                document.body.classList.remove('secure-study-overlay-active');
            };

            const lockAwayState = () => {
                if (!document.body || awayLocked) {
                    return;
                }

                awayLocked = true;
                document.body.classList.add('secure-study-away');
                showOverlay('background');
            };

            const unlockAwayState = () => {
                if (!document.body) {
                    return;
                }

                awayLocked = false;
                document.body.classList.remove('secure-study-away', 'secure-study-background-blur');
                hideOverlay();
            };

            const showTemporaryOverlay = (reason, duration = 1800) => {
                showOverlay(reason);
                window.setTimeout(() => {
                    if (document.visibilityState === 'visible') {
                        hideOverlay();
                    }
                }, duration);
            };

            const blockEvent = (event, reason = null) => {
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation?.();

                if (reason) {
                    showTemporaryOverlay(reason);
                }
            };

            const handleShortcut = (event) => {
                const key = String(event.key || '').toLowerCase();
                const withModifier = event.ctrlKey || event.metaKey;
                const withShiftModifier = withModifier && event.shiftKey;
                const isBlockedShortcut =
                    key === 'f12' ||
                    key === 'printscreen' ||
                    (withShiftModifier && ['i', 'j', 'c', 'k', 's'].includes(key)) ||
                    (withModifier && ['a', 'c', 'p', 's', 'u', 'x'].includes(key));

                if (!isBlockedShortcut) {
                    return;
                }

                if (key === 'printscreen') {
                    blockEvent(event, 'capture');
                    return;
                }

                if (['a', 'c', 'x'].includes(key)) {
                    blockEvent(event, 'copy');
                    return;
                }

                blockEvent(event, key === 'p' ? 'print' : 'shortcut');
            };

            const handleBackgroundState = () => {
                window.clearTimeout(backgroundTimer);

                if (!config.hideOnBackground) {
                    return;
                }

                if (document.visibilityState !== 'visible') {
                    backgroundTimer = window.setTimeout(() => {
                        lockAwayState();
                        if (config.blurOnBackgroundDesktop && window.innerWidth >= 1024) {
                            document.body.classList.add('secure-study-background-blur');
                        }
                    }, Math.max(0, Number(config.backgroundLockDelayMs) || 0));
                    return;
                }

                unlockAwayState();
            };

            const attachProtection = () => {
                if (isPhoneOrTabletBrowser) {
                    if (config.watermarkEnabled) {
                        ensureWatermark();
                    }
                    return;
                }

                ensureOverlay();
                ensureWatermark();
                document.body.classList.add('secure-study-hardened');
                if (!config.allowSelection) {
                    document.body.classList.add('secure-study-no-select');
                }

                if (config.blockShortcuts) {
                    document.addEventListener('keydown', handleShortcut, true);
                }

                if (config.blockCopy) {
                    const blockedEvents = ['copy', 'cut', 'contextmenu', 'dragstart'];
                    if (!config.allowSelection) {
                        blockedEvents.push('selectstart');
                    }

                    blockedEvents.forEach((eventName) => {
                        document.addEventListener(eventName, (event) => {
                            if (isEditableTarget(event.target)) {
                                return;
                            }

                            if (eventName === 'copy' || eventName === 'cut') {
                                try {
                                    event.clipboardData?.setData('text/plain', 'Copying is disabled on this MedCapsule session.');
                                } catch (error) {
                                    // Ignore clipboard access failures.
                                }

                                navigator.clipboard?.writeText?.('Copying is disabled on this MedCapsule session.').catch(() => {});
                            }

                            blockEvent(event, eventName === 'copy' || eventName === 'cut' ? 'copy' : null);
                        }, true);
                    });
                }

                document.addEventListener('selectionchange', () => {
                    window.clearTimeout(selectionResetTimer);
                    selectionResetTimer = window.setTimeout(clearBlockedSelection, 0);
                }, true);

                document.addEventListener('mousedown', clearBlockedSelection, true);
                document.addEventListener('mouseup', clearBlockedSelection, true);
                document.addEventListener('touchend', clearBlockedSelection, true);

                if (config.blockPrint) {
                    window.addEventListener('beforeprint', () => {
                        showOverlay('print');
                    });
                }

                document.addEventListener('visibilitychange', handleBackgroundState, true);
                window.addEventListener('pagehide', () => {
                    if (config.hideOnBackground) {
                        lockAwayState();
                    }
                }, true);
                window.addEventListener('blur', () => {
                    if (config.hideOnBackground) {
                        lockAwayState();
                    }
                }, true);
                window.addEventListener('focus', handleBackgroundState, true);

                handleBackgroundState();
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', attachProtection, { once: true });
                return;
            }

            attachProtection();
        })();
    </script>
@endif
