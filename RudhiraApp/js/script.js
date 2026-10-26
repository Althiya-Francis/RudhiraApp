/* js/script.js - small UI helpers. Real security is done in PHP; this only improves the experience. */
document.addEventListener('DOMContentLoaded', function () {

    // 1. Splash page: redirect after the loading animation (2.5 seconds)
    var splash = document.querySelector('body.splash-page');
    if (splash) {
        setTimeout(function () { window.location.href = splash.dataset.redirect; }, 2500);
    }

    // 2. Mobile menu button
    var toggle = document.getElementById('menuToggle');
    var sidebar = document.getElementById('sidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', function () { sidebar.classList.toggle('open'); });
    }

    // 3. Confirmation dialogs: <button data-confirm="Are you sure?">
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (event) {
            if (!window.confirm(el.dataset.confirm)) { event.preventDefault(); }
        });
    });

    // 3b. Confirm only for important outcomes: data-confirm-select="selectId" data-confirm-values="A,B"
    document.querySelectorAll('[data-confirm-select]').forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            var select = document.getElementById(btn.dataset.confirmSelect);
            var important = btn.dataset.confirmValues.split(',');
            if (select && important.indexOf(select.value) !== -1) {
                if (!window.confirm('Record this donor as "' + select.value + '"?')) { event.preventDefault(); }
            }
        });
    });

    // 4. Client-side validation: highlights empty required fields. (PHP validates again on the server.)
    ['loginForm', 'registerForm', 'searchForm', 'requestForm', 'contactForm'].forEach(function (formId) {
        var form = document.getElementById(formId);
        if (!form) return;
        form.addEventListener('submit', function (event) {
            var ok = true;
            form.querySelectorAll('[required]').forEach(function (field) {
                var empty = field.value.trim() === '' || field.value === '0';
                field.classList.toggle('field-error', empty);
                if (empty) ok = false;
            });
            if (!ok) {
                event.preventDefault();
                alert('Please fill in all required fields.');
                return;
            }
            // Registration: extra checks (PHP repeats all of them on the server)
            if (formId === 'registerForm') {
                var pw = form.querySelector('#password').value;
                var confirmPw = form.querySelector('#confirm_password').value;
                var problem = '';
                if (pw.length < 8) problem = 'Password must be at least 8 characters.';
                else if (!/[A-Za-z]/.test(pw) || !/\d/.test(pw)) problem = 'Password needs at least one letter and one number.';
                else if (pw !== confirmPw) problem = 'Password and confirmation do not match.';
                if (problem) { event.preventDefault(); alert(problem); }
            }
        });
    });

    // 5. Find Donors: choosing a request auto-selects its blood group
    var requestSelect = document.querySelector('#searchForm #request_id');
    var groupSelect = document.querySelector('#searchForm #blood_group');
    if (requestSelect && groupSelect) {
        requestSelect.addEventListener('change', function () {
            var chosen = requestSelect.options[requestSelect.selectedIndex];
            if (chosen && chosen.dataset.bg) { groupSelect.value = chosen.dataset.bg; }
        });
    }

    // 6. Copy to clipboard (Generate Message page)
    var copyBtn = document.getElementById('copyBtn');
    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            var box = document.getElementById(copyBtn.dataset.target);
            var status = document.getElementById('copyStatus');
            var done = function () { status.textContent = 'Copied!'; };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(box.value).then(done);
            } else {                      // older-browser fallback
                box.select();
                document.execCommand('copy');
                done();
            }
        });
    }

    // 7. Show/Hide password buttons: <button data-toggle-password="fieldId">
    document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var field = document.getElementById(btn.dataset.togglePassword);
            var show = field.type === 'password';
            field.type = show ? 'text' : 'password';
            btn.textContent = show ? 'Hide' : 'Show';
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    });
});
