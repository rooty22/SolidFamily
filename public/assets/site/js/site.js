/**
 * Sandouk - Member Portal & Site JavaScript
 * Lightweight micro-interactions, SweetAlert2 integrations, OTP handler
 */

document.addEventListener('DOMContentLoaded', function () {
    // Mobile Sidebar Drawer Toggle
    const toggleBtn = document.querySelector('.sidebar-toggle-btn');
    const sidebar = document.querySelector('.sidebar');
    
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            sidebar.classList.toggle('open');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function (e) {
            if (window.innerWidth <= 992 && sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target !== toggleBtn) {
                sidebar.classList.remove('open');
            }
        });
    }

    // Auto-dismiss modern alerts after 5 seconds with smooth fade
    document.querySelectorAll('.alert-modern[data-auto-dismiss]').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .5s ease, transform .5s ease';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-8px)';
            setTimeout(function () { el.remove(); }, 500);
        }, 5000);
    });

    // Smart OTP auto-focus, backspace, paste, and filled state
    document.querySelectorAll('.otp-inputs').forEach(function (group) {
        const inputs = Array.from(group.querySelectorAll('input'));
        const hiddenInput = document.getElementById('otp-code');
        const submitBtn = group.closest('form')?.querySelector('button[type="submit"]');

        function syncHiddenCode() {
            const code = inputs.map(i => i.value).join('');
            if (hiddenInput) hiddenInput.value = code;
            inputs.forEach(i => {
                if (i.value) i.classList.add('filled');
                else i.classList.remove('filled');
            });
            return code;
        }

        inputs.forEach(function (input, idx) {
            // Auto focus first input on load if empty
            if (idx === 0 && !input.value) {
                setTimeout(() => input.focus(), 150);
            }

            input.addEventListener('input', function (e) {
                input.value = input.value.replace(/[^0-9]/g, '');
                if (input.value.length > 1) {
                    input.value = input.value.slice(-1);
                }
                const fullCode = syncHiddenCode();
                if (input.value.length >= 1 && idx < inputs.length - 1) {
                    inputs[idx + 1].focus();
                    inputs[idx + 1].select();
                } else if (fullCode.length === inputs.length && submitBtn) {
                    submitBtn.focus();
                }
            });

            input.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace') {
                    if (input.value === '' && idx > 0) {
                        inputs[idx - 1].focus();
                        inputs[idx - 1].value = '';
                        syncHiddenCode();
                    } else {
                        input.value = '';
                        syncHiddenCode();
                    }
                } else if (e.key === 'ArrowLeft' && idx > 0) {
                    inputs[idx - 1].focus();
                } else if (e.key === 'ArrowRight' && idx < inputs.length - 1) {
                    inputs[idx + 1].focus();
                }
            });

            input.addEventListener('paste', function (e) {
                e.preventDefault();
                const pasteData = (e.clipboardData || window.clipboardData).getData('text').trim().replace(/[^0-9]/g, '');
                if (pasteData) {
                    const digits = pasteData.split('');
                    inputs.forEach((inp, i) => {
                        inp.value = digits[i] || '';
                    });
                    const fullCode = syncHiddenCode();
                    const nextIdx = Math.min(digits.length, inputs.length - 1);
                    inputs[nextIdx].focus();
                    if (fullCode.length === inputs.length && submitBtn) {
                        submitBtn.focus();
                    }
                }
            });
        });

        // Global auto-fill function for demo mode
        window.autoFillOtp = function (demoCode) {
            if (!demoCode) return;
            const digits = String(demoCode).replace(/[^0-9]/g, '').split('');
            inputs.forEach((inp, i) => {
                inp.value = digits[i] || '';
                inp.classList.add('filled');
            });
            syncHiddenCode();
            if (submitBtn) {
                submitBtn.focus();
                // Subtle pulse animation
                submitBtn.style.transform = 'scale(1.03)';
                setTimeout(() => submitBtn.style.transform = '', 300);
            }
        };
    });

    // Resend/verify countdown timer. data-cooldown is server-computed and already accounts for the real
    // security throttle (which can run into minutes), not just the cosmetic default wait.
    const resendBtn = document.getElementById('resend-btn');
    const resendTimerEl = document.getElementById('resend-timer');
    const otpLockedWrap = document.querySelector('.otp-inputs[data-locked]');
    const otpSubmitBtn = document.getElementById('otp-submit-btn');

    function formatRemaining(seconds) {
        if (seconds < 60) return String(seconds);
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        return m + ':' + String(s).padStart(2, '0');
    }

    if (resendBtn && resendTimerEl) {
        const cooldownSeconds = parseInt(resendBtn.getAttribute('data-cooldown') || '50', 10);
        const storageKey = 'otp_resend_until_' + window.location.pathname;
        const now = Math.floor(Date.now() / 1000);
        const freshUntil = now + cooldownSeconds;

        let until = parseInt(sessionStorage.getItem(storageKey) || '0', 10);
        // Trust the fresh server value whenever it disagrees with what's cached: bigger means a real
        // security lock just kicked in, smaller means time has genuinely passed since the last render.
        if (!until || Math.abs(freshUntil - until) > 2) {
            until = freshUntil;
        }
        sessionStorage.setItem(storageKey, until);

        function updateTimer() {
            const current = Math.floor(Date.now() / 1000);
            const remaining = until - current;

            if (remaining > 0) {
                resendBtn.disabled = true;
                resendBtn.classList.add('is-locked');
                resendBtn.classList.remove('is-ready');
                resendTimerEl.textContent = formatRemaining(remaining);
                setTimeout(updateTimer, 1000);
            } else {
                sessionStorage.removeItem(storageKey);

                // A real security lock (not just the cosmetic wait) just expired in demo mode: fetch a
                // fresh code automatically instead of leaving the user staring at one that's now invalid.
                if (resendBtn.dataset.autoResend === '1') {
                    resendBtn.closest('form')?.requestSubmit();
                    return;
                }

                const isEn = document.documentElement.lang === 'en';
                resendBtn.disabled = false;
                resendBtn.classList.remove('is-locked');
                resendBtn.classList.add('is-ready');
                resendBtn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> <span>' + (isEn ? 'Resend Code Now' : 'إعادة إرسال الرمز الآن') + '</span>';

                if (otpLockedWrap) {
                    otpLockedWrap.querySelectorAll('input').forEach(function (i) { i.disabled = false; });
                    otpLockedWrap.removeAttribute('data-locked');
                }
                if (otpSubmitBtn) otpSubmitBtn.disabled = false;
            }
        }
        updateTimer();

        resendBtn.closest('form')?.addEventListener('submit', function () {
            sessionStorage.setItem(storageKey, Math.floor(Date.now() / 1000) + cooldownSeconds);
        });
    }

    // Copy to clipboard helper (useful for IBAN / Account numbers)
    document.querySelectorAll('[data-copy]').forEach(function (el) {
        el.addEventListener('click', function () {
            const text = el.getAttribute('data-copy');
            if (!text) return;
            navigator.clipboard.writeText(text).then(function () {
                if (window.Swal) {
                    Swal.fire({
                        toast: true,
                        position: 'top-start',
                        icon: 'success',
                        title: 'تم النسخ بنجاح',
                        showConfirmButton: false,
                        timer: 2000
                    });
                }
            });
        });
    });

    // Generic confirmation dialog using SweetAlert2 if present
    document.querySelectorAll('[data-confirm]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            const message = btn.getAttribute('data-confirm') || 'هل أنت متأكد من هذه العملية؟';
            if (window.Swal) {
                e.preventDefault();
                Swal.fire({
                    title: 'تأكيد العملية',
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#059669',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'نعم، استمر',
                    cancelButtonText: 'إلغاء',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (btn.tagName === 'A') {
                            window.location.href = btn.href;
                        } else if (btn.closest('form')) {
                            btn.closest('form').submit();
                        }
                    }
                });
            }
        });
    });
});
