/**
 * js/script.js
 * -----------------------------------------------------------------
 * Vanilla JavaScript only. Used for client-side validation and UI
 * interactions. PHP re-validates everything server-side — this file
 * is only here to improve the user experience.
 * -----------------------------------------------------------------
 */

document.addEventListener('DOMContentLoaded', function () {
    initLoginPage();
    initRegisterValidation();
    initReservationForm();
    initConfirmDialogs();
    initPanelSwitch();
    initRegisterPwToggle();
});

/* ============================================================
   0. LOGIN PAGE — password visibility toggle
   ============================================================ */
function initLoginPage() {
    var toggle  = document.getElementById('pwToggle');
    var pwField = document.getElementById('password');
    var iconShow = document.getElementById('pwIconShow');
    var iconHide = document.getElementById('pwIconHide');

    if (!toggle || !pwField) return;

    toggle.addEventListener('click', function () {
        var isPassword = pwField.type === 'password';
        pwField.type = isPassword ? 'text' : 'password';
        iconShow.style.display = isPassword ? 'none'  : '';
        iconHide.style.display = isPassword ? ''      : 'none';
        toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
    });
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

        const requiredFields = ['first_name', 'last_name', 'date_of_birth', 'address',
            'contact_number', 'email', 'username', 'password', 'confirm_password'];

        requiredFields.forEach(function (name) {
            const field = form.querySelector('[name="' + name + '"]');
            if (field && field.value.trim() === '') {
                showError(field, 'This field is required.');
                valid = false;
            }
        });

        const email = form.querySelector('[name="email"]');
        if (email && email.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
            showError(email, 'Please enter a valid email address.');
            valid = false;
        }

        const password = form.querySelector('[name="password"]');
        if (password && password.value && password.value.length < 8) {
            showError(password, 'Password must be at least 8 characters.');
            valid = false;
        }

        const confirm = form.querySelector('[name="confirm_password"]');
        if (password && confirm && password.value !== confirm.value) {
            showError(confirm, 'Passwords do not match.');
            valid = false;
        }

        const contact = form.querySelector('[name="contact_number"]');
        if (contact && contact.value && !/^[0-9+()\-\s]{7,20}$/.test(contact.value)) {
            showError(contact, 'Please enter a valid contact number.');
            valid = false;
        }

        if (!valid) {
            e.preventDefault();
        }
    });
}

function showError(field, message) {
    const err = document.createElement('div');
    err.className = 'field-error';
    err.textContent = message;
    field.parentElement.appendChild(err);
    field.style.borderColor = '#b02a2a';
}

function clearErrors(form) {
    form.querySelectorAll('.field-error').forEach(function (el) { el.remove(); });
    form.querySelectorAll('input, select, textarea').forEach(function (el) {
        el.style.borderColor = '';
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
    var container   = document.getElementById('authContainer') || document.getElementById('authPanels');
    var btnRegister = document.getElementById('btnShowRegister');
    var btnLogin    = document.getElementById('btnShowLogin');
    var authPanel   = document.querySelector('.auth-panel') || document.querySelector('.login-panel-right');

    if (!container) return;

    function showRegisterPanel() {
        container.classList.remove('login-transition');
        container.classList.remove('returning');
        container.classList.add('register-active');
        container.classList.add('show-register'); // fallback compatibility
        if (authPanel) authPanel.scrollTop = 0;
        var firstField = document.getElementById('reg_first_name');
        if (firstField) setTimeout(function () { firstField.focus(); }, 350);
    }

    function showLoginPanel() {
        container.classList.add('login-transition');
        container.classList.add('returning');
        container.classList.remove('register-active');
        container.classList.remove('show-register');
        setTimeout(function () {
            container.classList.remove('login-transition');
            container.classList.remove('returning');
        }, 380);
        if (authPanel) authPanel.scrollTop = 0;
        var emailField = document.getElementById('email');
        if (emailField) setTimeout(function () { emailField.focus(); }, 350);
    }

    if (btnRegister) {
        btnRegister.addEventListener('click', function (e) {
            e.preventDefault();
            showRegisterPanel();
        });
    }
    if (btnLogin) {
        btnLogin.addEventListener('click', function (e) {
            e.preventDefault();
            showLoginPanel();
        });
    }
}


/* ============================================================
   5. REGISTER PANEL — Password visibility toggle
   ============================================================ */
function initRegisterPwToggle() {
    var toggle   = document.getElementById('regPwToggle');
    var pwField  = document.getElementById('reg_password');
    var iconShow = document.getElementById('regPwIconShow');
    var iconHide = document.getElementById('regPwIconHide');

    if (!toggle || !pwField) return;

    toggle.addEventListener('click', function () {
        var isPassword = pwField.type === 'password';
        pwField.type = isPassword ? 'text' : 'password';
        if (iconShow) iconShow.style.display = isPassword ? 'none' : '';
        if (iconHide) iconHide.style.display = isPassword ? ''     : 'none';
        toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
    });
}
