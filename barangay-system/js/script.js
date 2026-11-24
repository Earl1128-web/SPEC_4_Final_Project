/**
 * js/script.js
 * -----------------------------------------------------------------
 * Vanilla JavaScript only. Used for client-side validation and UI
 * interactions. PHP re-validates everything server-side — this file
 * is only here to improve the user experience.
 * -----------------------------------------------------------------
 */

document.addEventListener('DOMContentLoaded', function () {
    initAppSidebar();
    initLoginPage();
    initRegisterValidation();
    initPasswordStrength();
    initReservationForm();
    initConfirmDialogs();
    initPanelSwitch();
    initRegisterPwToggle();
    initForgotPwModal();
});

/* ============================================================
   0. LOGIN PAGE — password visibility toggle & submission
   ============================================================ */
function initLoginPage() {
    var toggle   = document.getElementById('pwToggle');
    var pwField  = document.getElementById('password');
    var iconShow = document.getElementById('pwIconShow');
    var iconHide = document.getElementById('pwIconHide');

    if (toggle && pwField) {
        toggle.addEventListener('click', function () {
            var isPassword = pwField.type === 'password';
            pwField.type = isPassword ? 'text' : 'password';
            if (iconShow) iconShow.style.display = isPassword ? 'none' : '';
            if (iconHide) iconHide.style.display = isPassword ? ''     : 'none';
            toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        });
    }

    var loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            clearErrors(loginForm);
            var valid = true;
            var emailField = document.getElementById('email');
            var passField  = document.getElementById('password');

            if (emailField && !emailField.value.trim()) {
                showError(emailField, 'Please enter your email address or username.');
                valid = false;
            }
            if (passField && !passField.value) {
                showError(passField, 'Please enter your password.');
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
                return;
            }

            // Prevent double-submission
            var submitBtn = document.getElementById('loginSubmitBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="auth-btn-spinner" aria-hidden="true"></span> <span>Logging in...</span>';
            }
        });
    }
}

/* ============================================================
   1. REGISTRATION FORM VALIDATION
   ============================================================ */
function initRegisterValidation() {
    const form = document.getElementById('registerForm');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        let valid = true;
        clearErrors(form);

        const requiredFields = [
            { name: 'first_name', label: 'First Name' },
            { name: 'last_name', label: 'Last Name' },
            { name: 'date_of_birth', label: 'Date of Birth' },
            { name: 'address', label: 'Residential Address' },
            { name: 'contact_number', label: 'Contact Number' },
            { name: 'email', label: 'Email Address' },
            { name: 'username', label: 'Username' },
            { name: 'password', label: 'Password' },
            { name: 'confirm_password', label: 'Confirm Password' }
        ];

        requiredFields.forEach(function (item) {
            const field = form.querySelector('[name="' + item.name + '"]');
            if (field && field.value.trim() === '') {
                showError(field, item.label + ' is required.');
                valid = false;
            }
        });

        // Email format check
        const email = form.querySelector('[name="email"]');
        if (email && email.value.trim() !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
            showError(email, 'Please enter a valid email address.');
            valid = false;
        }

        // Philippine mobile check (09XXXXXXXXX or +639XXXXXXXXX)
        const contact = form.querySelector('[name="contact_number"]');
        if (contact && contact.value.trim() !== '') {
            const raw = contact.value.trim();
            if (!/^(09\d{9}|\+639\d{9})$/.test(raw)) {
                showError(contact, 'Please enter a valid Philippine mobile number (e.g., 09XXXXXXXXX).');
                valid = false;
            }
        }

        // Date of birth: not in the future, resident at least 10 years old
        const dobField = form.querySelector('[name="date_of_birth"]');
        if (dobField && dobField.value) {
            const dobDate = new Date(dobField.value);
            const now = new Date();
            if (isNaN(dobDate.getTime())) {
                showError(dobField, 'Please enter a valid date of birth.');
                valid = false;
            } else if (dobDate > now) {
                showError(dobField, 'Date of birth cannot be in the future.');
                valid = false;
            } else {
                let age = now.getFullYear() - dobDate.getFullYear();
                const m = now.getMonth() - dobDate.getMonth();
                if (m < 0 || (m === 0 && now.getDate() < dobDate.getDate())) {
                    age--;
                }
                if (age < 10) {
                    showError(dobField, 'Residents must be at least 10 years of age.');
                    valid = false;
                }
            }
        }

        // Username: min 4 chars, letters/numbers/dots/underscores
        const username = form.querySelector('[name="username"]');
        if (username && username.value.trim() !== '') {
            const uval = username.value.trim();
            if (uval.length < 4) {
                showError(username, 'Username must be at least 4 characters long.');
                valid = false;
            } else if (!/^[a-zA-Z0-9._]+$/.test(uval)) {
                showError(username, 'Username may only contain letters, numbers, dots, and underscores.');
                valid = false;
            }
        }

        // Password complexity: min 8 chars, uppercase, lowercase, digit
        const password = form.querySelector('[name="password"]');
        if (password && password.value) {
            const pval = password.value;
            if (pval.length < 8) {
                showError(password, 'Password must be at least 8 characters long.');
                valid = false;
            } else if (!/[A-Z]/.test(pval) || !/[a-z]/.test(pval) || !/[0-9]/.test(pval)) {
                showError(password, 'Password must include uppercase, lowercase, and a number.');
                valid = false;
            }
        }

        // Confirm Password match
        const confirm = form.querySelector('[name="confirm_password"]');
        if (password && confirm && password.value && confirm.value) {
            if (password.value !== confirm.value) {
                showError(confirm, 'Passwords do not match.');
                valid = false;
            }
        }

        if (!valid) {
            e.preventDefault();
        } else {
            // Prevent double-submission
            const regBtn = document.getElementById('registerSubmitBtn');
            if (regBtn) {
                regBtn.disabled = true;
                regBtn.innerHTML = '<span class="auth-btn-spinner" aria-hidden="true"></span> <span>Creating Account...</span>';
            }
        }
    });
}

function showError(field, message) {
    // Append directly inside the field container so errors sit underneath without overlapping other fields or eye toggles
    const container = field.closest('.reg-field-item') || field.closest('.auth-field-row') || field.parentElement;
    const existing = container.querySelector('.field-error');
    if (existing) existing.remove();

    const err = document.createElement('div');
    err.className = 'field-error';
    err.textContent = message;
    container.appendChild(err);

    field.style.borderColor = '#dc2626';
}

function clearErrors(form) {
    form.querySelectorAll('.field-error').forEach(function (el) { el.remove(); });
    form.querySelectorAll('input, select, textarea').forEach(function (el) {
        el.style.borderColor = '';
    });
}

/* ============================================================
   1.1 REAL-TIME PASSWORD STRENGTH INDICATOR
   ============================================================ */
function initPasswordStrength() {
    var pwField = document.getElementById('reg_password');
    var meter   = document.getElementById('pwStrengthMeter');
    var text    = document.getElementById('pwStrengthText');

    if (!pwField || !meter) return;

    pwField.addEventListener('input', function () {
        var val = pwField.value;
        if (!val) {
            meter.style.display = 'none';
            meter.className = 'pw-strength-meter';
            return;
        }

        meter.style.display = 'flex';

        var score = 0;
        if (val.length >= 8) score++;
        if (/[a-z]/.test(val) && /[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val) || val.length >= 12) score++;

        meter.className = 'pw-strength-meter';
        if (score <= 1) {
            meter.classList.add('pw-weak');
            if (text) text.textContent = 'Weak';
        } else if (score === 2) {
            meter.classList.add('pw-fair');
            if (text) text.textContent = 'Fair';
        } else if (score === 3) {
            meter.classList.add('pw-good');
            if (text) text.textContent = 'Good';
        } else {
            meter.classList.add('pw-strong');
            if (text) text.textContent = 'Strong';
        }
    });
}

/* ============================================================
   2. RESERVATION FORM (purpose "Other" toggle + slot picker)
   ============================================================ */
function initReservationForm() {
    const purposeSelect = document.getElementById('purpose');
    const otherWrapper = document.getElementById('otherPurposeWrapper');

    if (purposeSelect && otherWrapper) {
        function toggleOther() {
            if (purposeSelect.value === 'Other') {
                otherWrapper.style.display = 'block';
            } else {
                otherWrapper.style.display = 'none';
            }
        }
        purposeSelect.addEventListener('change', toggleOther);
        toggleOther();
    }

    // Slot picker: clicking a slot option selects it and fills the hidden input
    const slotOptions = document.querySelectorAll('.slot-option:not(.full)');
    const slotInput = document.getElementById('selected_slot_id');
    slotOptions.forEach(function (opt) {
        opt.addEventListener('click', function () {
            slotOptions.forEach(function (o) { o.classList.remove('selected'); });
            opt.classList.add('selected');
            if (slotInput) slotInput.value = opt.getAttribute('data-slot-id');
        });
    });

    // Basic client-side check before submit: a slot must be chosen
    const reservationForm = document.getElementById('reservationForm');
    if (reservationForm) {
        reservationForm.addEventListener('submit', function (e) {
            if (slotInput && !slotInput.value) {
                e.preventDefault();
                alert('Please select an appointment time slot.');
            }
        });
    }
}

/* ============================================================
   3. CONFIRM DIALOGS for destructive/important actions
   ============================================================ */
function initConfirmDialogs() {
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            const message = el.getAttribute('data-confirm') || 'Are you sure?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

/* ============================================================
   4. PANEL SWITCH — Login ↔ Register (same page, no reload)
   ============================================================ */
function initPanelSwitch() {
    var container   = document.getElementById('authContainer');
    var btnRegister = document.getElementById('btnShowRegister');
    var btnLogin    = document.getElementById('btnShowLogin');

    if (!container) return;

    function showRegister() {
        container.classList.add('register-active');
        var firstField = document.getElementById('reg_first_name');
        if (firstField) {
            setTimeout(function () { firstField.focus(); }, 250);
        }
    }

    function showLogin() {
        container.classList.remove('register-active');
        var emailField = document.getElementById('email');
        if (emailField) {
            setTimeout(function () { emailField.focus(); }, 250);
        }
    }

    if (btnRegister) {
        btnRegister.addEventListener('click', function (e) {
            e.preventDefault();
            showRegister();
        });
    }

    if (btnLogin) {
        btnLogin.addEventListener('click', function (e) {
            e.preventDefault();
            showLogin();
        });
    }
}

/* ============================================================
   5. REGISTER PANEL — Password visibility toggles (both fields)
   ============================================================ */
function initRegisterPwToggle() {
    function setupToggle(toggleId, fieldId, iconShowId, iconHideId) {
        var toggle   = document.getElementById(toggleId);
        var pwField  = document.getElementById(fieldId);
        var iconShow = document.getElementById(iconShowId);
        var iconHide = document.getElementById(iconHideId);

        if (!toggle || !pwField) return;

        toggle.addEventListener('click', function () {
            var isPassword = pwField.type === 'password';
            pwField.type = isPassword ? 'text' : 'password';
            if (iconShow) iconShow.style.display = isPassword ? 'none' : '';
            if (iconHide) iconHide.style.display = isPassword ? ''     : 'none';
            toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        });
    }

    setupToggle('regPwToggle', 'reg_password', 'regPwIconShow', 'regPwIconHide');
    setupToggle('regConfirmPwToggle', 'reg_confirm_password', 'regConfirmPwIconShow', 'regConfirmPwIconHide');
}

/* ============================================================
   6. FORGOT PASSWORD MODAL — Accessible notice dialog
   ============================================================ */
function initForgotPwModal() {
    var btnForgot  = document.getElementById('btnForgotPassword');
    var modal      = document.getElementById('forgotPwModal');
    var btnClose   = document.getElementById('btnCloseForgotPwModal');
    var btnDismiss = document.getElementById('btnDismissForgotPwModal');

    if (!btnForgot || !modal) return;

    function openModal() {
        modal.classList.add('is-active');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
        modal.classList.remove('is-active');
        modal.setAttribute('aria-hidden', 'true');
    }

    btnForgot.addEventListener('click', function (e) {
        e.preventDefault();
        openModal();
    });

    if (btnClose) btnClose.addEventListener('click', closeModal);
    if (btnDismiss) btnDismiss.addEventListener('click', closeModal);

    // Click outside dialog to close
    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            closeModal();
        }
    });

    // Escape key to close
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('is-active')) {
            closeModal();
        }
    });
}

/* ============================================================
   7. UNIFIED SIDEBAR NAVIGATION & ABOUT DROPDOWN
   ============================================================ */
function initAppSidebar() {
    // 1. About Barangay Dropdown Toggle
    var aboutToggle = document.getElementById('aboutMenuToggle');
    var aboutWrap   = document.getElementById('aboutDropdownWrap');

    if (aboutToggle && aboutWrap) {
        aboutToggle.addEventListener('click', function (e) {
            e.preventDefault();
            var isOpen = aboutWrap.classList.contains('open');
            if (isOpen) {
                aboutWrap.classList.remove('open');
                aboutToggle.setAttribute('aria-expanded', 'false');
            } else {
                aboutWrap.classList.add('open');
                aboutToggle.setAttribute('aria-expanded', 'true');
            }
        });
    }

    // 2. Mobile Sidebar Off-Canvas Drawer Toggle
    var sidebarToggle = document.getElementById('sidebarToggle');
    var sidebar       = document.getElementById('appSidebar');
    var backdrop      = document.getElementById('sidebarBackdrop');

    if (sidebarToggle && sidebar && backdrop) {
        function openSidebar() {
            sidebar.classList.add('is-open');
            backdrop.classList.add('is-active');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('is-open');
            backdrop.classList.remove('is-active');
            document.body.style.overflow = '';
        }

        sidebarToggle.addEventListener('click', function (e) {
            e.preventDefault();
            if (sidebar.classList.contains('is-open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });

        backdrop.addEventListener('click', closeSidebar);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && sidebar.classList.contains('is-open')) {
                closeSidebar();
            }
        });
    }
}
