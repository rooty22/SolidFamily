/**
 * Sandouk - Executive Admin Dashboard JavaScript
 * Modern table search, SweetAlert2 confirm dialogs, responsive drawer
 */

document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.querySelector('.sidebar-toggle-btn');
    const sidebar = document.querySelector('.sidebar');
    
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            sidebar.classList.toggle('open');
        });

        document.addEventListener('click', function (e) {
            if (window.innerWidth <= 992 && sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target !== toggleBtn) {
                sidebar.classList.remove('open');
            }
        });
    }

    // Modern Confirm Dialog using SweetAlert2
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        const handler = function (e) {
            const message = el.getAttribute('data-confirm') || 'هل أنت متأكد من تنفيذ هذا الإجراء؟';
            if (window.Swal) {
                e.preventDefault();
                Swal.fire({
                    title: 'تأكيد الإجراء',
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#0284c7',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'نعم، استمر',
                    cancelButtonText: 'إلغاء',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (el.tagName === 'FORM') {
                            el.removeEventListener('submit', handler);
                            el.submit();
                        } else if (el.tagName === 'A') {
                            window.location.href = el.href;
                        }
                    }
                });
            } else {
                if (!confirm(message)) {
                    e.preventDefault();
                }
            }
        };

        if (el.tagName === 'FORM') {
            el.addEventListener('submit', handler);
        } else {
            el.addEventListener('click', handler);
        }
    });

    // Auto-dismiss modern alerts after 5 seconds
    document.querySelectorAll('.alert-modern[data-auto-dismiss]').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .5s ease, transform .5s ease';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-8px)';
            setTimeout(function () { el.remove(); }, 500);
        }, 5000);
    });

    // Instant table live search
    document.querySelectorAll('[data-table-search]').forEach(function (input) {
        input.addEventListener('keyup', function () {
            const term = input.value.trim().toLowerCase();
            const tableSelector = input.getAttribute('data-table-search');
            const table = document.querySelector(tableSelector);
            if (!table) return;

            table.querySelectorAll('tbody tr').forEach(function (row) {
                const text = row.innerText.toLowerCase();
                row.style.display = text.indexOf(term) !== -1 ? '' : 'none';
            });
        });
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
            if (idx === 0 && !input.value) {
                setTimeout(() => input.focus(), 150);
            }

            input.addEventListener('input', function () {
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

        // Auto-fill helper for demo mode
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

                resendBtn.disabled = false;
                resendBtn.classList.remove('is-locked');
                resendBtn.classList.add('is-ready');
                resendBtn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> <span>إعادة إرسال الرمز الآن</span>';

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
});
