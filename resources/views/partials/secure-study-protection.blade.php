@if (config('features.design_protection_enabled', false) && !(auth()->check() && auth()->user()->isAdmin()))
    @php
        $secureStudyUser = auth()->user();
        $secureStudyLabel = trim(implode(' | ', array_filter([
            $secureStudyUser?->email,
            $secureStudyUser ? 'User '.$secureStudyUser->id : null,
            strtoupper($surfaceLabel ?? 'study'),
        ])));
        $secureStudyConfig = [
            'surfaceLabel' => $surfaceLabel ?? 'study',
            'blockShortcuts' => (bool) config('features.design_protection_block_shortcuts', true),
            'blockCopy' => (bool) config('features.design_protection_block_copy', true),
            'blockPrint' => (bool) config('features.design_protection_block_print', true),
            'hideOnBackground' => (bool) config('features.design_protection_hide_on_background', true),
            'blurOnBackgroundDesktop' => (bool) config('features.design_protection_blur_on_background_desktop', false),
            'watermarkEnabled' => (bool) config('features.design_protection_watermark_enabled', true),
            'allowSelection' => (bool) ($allowSelection ?? false),
            'backgroundLockDelayMs' => (int) config('features.design_protection_background_lock_delay_ms', 120),
            'watermarkText' => $secureStudyLabel !== '' ? $secureStudyLabel : 'Protected MedCapsule Content',
            'watermarkTimestamp' => now()->format('Y-m-d H:i:s'),
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

        .secure-study-watermark-layer {
            position: fixed;
            inset: 0;
            z-index: 2147482500;
            pointer-events: none;
            overflow: hidden;
            opacity: 0.15;
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
            color: rgba(15, 23, 42, 0.42);
            font-size: clamp(0.8rem, 1vw, 0.95rem);
            font-weight: 700;
            letter-spacing: 0.08em;
            line-height: 1.7;
            text-transform: uppercase;
            white-space: pre-line;
        }

        body.secure-study-overlay-active {
            overflow: hidden !important;
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

            const isEditableTarget = (target) => {
                if (!(target instanceof HTMLElement)) {
                    return false;
                }

                return Boolean(target.closest('input, textarea, select, [contenteditable=""], [contenteditable="true"], [data-allow-copy], [data-allow-select], .edit-input, .written-answer'));
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

                const watermarkText = `${config.watermarkText}\n${config.watermarkTimestamp}`;

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
                    (withModifier && ['u', 's', 'p'].includes(key));

                if (!isBlockedShortcut) {
                    return;
                }

                if (key === 'printscreen') {
                    blockEvent(event, 'capture');
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
                        showOverlay('background');
                        if (config.blurOnBackgroundDesktop && window.innerWidth >= 1024) {
                            document.body.classList.add('secure-study-background-blur');
                        }
                    }, Math.max(0, Number(config.backgroundLockDelayMs) || 0));
                    return;
                }

                document.body.classList.remove('secure-study-background-blur');
                hideOverlay();
            };

            const attachProtection = () => {
                ensureOverlay();
                ensureWatermark();

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

                            blockEvent(event);
                        }, true);
                    });
                }

                if (config.blockPrint) {
                    window.addEventListener('beforeprint', () => {
                        showOverlay('print');
                    });
                }

                document.addEventListener('visibilitychange', handleBackgroundState, true);
                window.addEventListener('pagehide', () => {
                    if (config.hideOnBackground) {
                        showOverlay('background');
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
