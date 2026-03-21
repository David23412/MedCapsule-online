@if (config('features.design_protection_enabled', true))
    <style>
        .design-protection-locked {
            overflow: hidden !important;
        }

        .design-protection-locked #app {
            filter: blur(14px);
            pointer-events: none;
            user-select: none;
            -webkit-user-select: none;
        }

        .design-protection-overlay {
            position: fixed;
            inset: 0;
            z-index: 99999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: rgba(15, 23, 42, 0.92);
            color: #f8fafc;
            text-align: center;
        }

        .design-protection-overlay.is-visible {
            display: flex;
        }

        .design-protection-overlay__card {
            max-width: 32rem;
            padding: 1.75rem;
            border: 1px solid rgba(148, 163, 184, 0.3);
            border-radius: 1rem;
            background: rgba(15, 23, 42, 0.9);
            box-shadow: 0 24px 80px rgba(15, 23, 42, 0.45);
        }

        .design-protection-overlay__title {
            margin: 0 0 0.75rem;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .design-protection-overlay__text {
            margin: 0;
            font-size: 1rem;
            line-height: 1.6;
            color: rgba(248, 250, 252, 0.84);
        }
    </style>

    <script>
        (() => {
            if (window.__medcapsuleDesignProtectionLoaded) {
                return;
            }

            window.__medcapsuleDesignProtectionLoaded = true;

            const devtoolsThreshold = Number(@json(config('features.design_protection_devtools_threshold', 160))) || 160;
            const blockedKeys = new Set(['f12']);
            let lockReason = null;
            let overlay;
            let temporaryLockTimer = null;

            const isEditableTarget = (target) => {
                if (!(target instanceof HTMLElement)) {
                    return false;
                }

                return Boolean(target.closest('input, textarea, select, [contenteditable=""], [contenteditable="true"]'));
            };

            const ensureOverlay = () => {
                if (overlay || !document.body) {
                    return overlay;
                }

                overlay = document.createElement('div');
                overlay.className = 'design-protection-overlay';
                overlay.setAttribute('aria-live', 'assertive');
                overlay.setAttribute('role', 'alert');
                overlay.innerHTML = `
                    <div class="design-protection-overlay__card">
                        <h2 class="design-protection-overlay__title">Inspection blocked</h2>
                        <p class="design-protection-overlay__text">
                            This page disables DevTools and common copy paths to protect the MedCapsule interface.
                            Close any open inspection panels to continue.
                        </p>
                    </div>
                `;

                document.body.appendChild(overlay);
                return overlay;
            };

            const setLocked = (shouldLock, reason = null) => {
                if (!document.body) {
                    return;
                }

                const overlayElement = ensureOverlay();

                if (shouldLock) {
                    lockReason = reason;
                    document.body.classList.add('design-protection-locked');
                    overlayElement?.classList.add('is-visible');
                    return;
                }

                if (lockReason !== 'devtools-detected' && reason === 'devtools-closed') {
                    return;
                }

                lockReason = null;
                document.body.classList.remove('design-protection-locked');
                overlayElement?.classList.remove('is-visible');
            };

            const blockEvent = (event, shouldLock = false, reason = null) => {
                event.preventDefault();
                event.stopPropagation();

                if (shouldLock) {
                    setLocked(true, reason);

                    if (reason === 'shortcut-blocked') {
                        window.clearTimeout(temporaryLockTimer);
                        temporaryLockTimer = window.setTimeout(() => {
                            if (lockReason === 'shortcut-blocked') {
                                setLocked(false);
                            }
                        }, 1800);
                    }
                }
            };

            const handleKeyboardShortcut = (event) => {
                const key = String(event.key || '').toLowerCase();
                const usesModifier = event.ctrlKey || event.metaKey;
                const isInspectShortcut =
                    blockedKeys.has(key) ||
                    (usesModifier && event.shiftKey && ['i', 'j', 'c', 'k'].includes(key)) ||
                    (usesModifier && ['u', 's'].includes(key));

                if (!isInspectShortcut) {
                    return;
                }

                blockEvent(event, true, 'shortcut-blocked');
            };

            const detectDevtools = () => {
                const widthGap = window.outerWidth - window.innerWidth;
                const heightGap = window.outerHeight - window.innerHeight;
                const suspiciousGap = Math.max(widthGap, heightGap);
                const looksLikeDesktopViewport = window.innerWidth >= 1024 || window.innerHeight >= 720;

                if (looksLikeDesktopViewport && suspiciousGap > devtoolsThreshold) {
                    setLocked(true, 'devtools-detected');
                    return;
                }

                if (lockReason === 'devtools-detected') {
                    setLocked(false, 'devtools-closed');
                }
            };

            const attachProtection = () => {
                ensureOverlay();

                document.addEventListener('keydown', handleKeyboardShortcut, true);

                document.addEventListener('contextmenu', (event) => {
                    if (isEditableTarget(event.target)) {
                        return;
                    }

                    blockEvent(event);
                }, true);

                document.addEventListener('copy', (event) => {
                    if (isEditableTarget(event.target)) {
                        return;
                    }

                    blockEvent(event);
                }, true);

                document.addEventListener('cut', (event) => {
                    if (isEditableTarget(event.target)) {
                        return;
                    }

                    blockEvent(event);
                }, true);

                document.addEventListener('selectstart', (event) => {
                    if (isEditableTarget(event.target)) {
                        return;
                    }

                    blockEvent(event);
                }, true);

                document.addEventListener('dragstart', (event) => {
                    if (isEditableTarget(event.target)) {
                        return;
                    }

                    blockEvent(event);
                }, true);

                window.addEventListener('resize', detectDevtools, { passive: true });
                window.addEventListener('load', detectDevtools, { once: true });
                window.setInterval(detectDevtools, 1000);

                detectDevtools();
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', attachProtection, { once: true });
                return;
            }

            attachProtection();
        })();
    </script>
@endif
