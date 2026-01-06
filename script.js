// script.js - Updated for new login flow
document.addEventListener('DOMContentLoaded', () => {
    // --- Form Containers ---
    const loginFormContainer = document.getElementById('login-form-container');
    const registerFormContainer = document.getElementById('register-form-container');
    const forgotPasswordFormContainer = document.getElementById('forgot-password-form-container');

    // --- Forms ---
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const forgotPasswordForm = document.getElementById('forgot-password-form');

    // --- Links & Buttons for Form Switching ---
    const showRegisterLink = document.getElementById('show-register-link');
    const showLoginLink = document.getElementById('show-login-link');
    const forgotPasswordLink = document.getElementById('forgot-password-link');
    const backToLoginLink = document.getElementById('back-to-login-link');

    // --- Input Fields & UI Elements ---
    const loginEmailInput = document.getElementById('login-email');
    const rememberMeCheckbox = document.getElementById('remember-me');
    const personalizedGreeting = document.getElementById('personalized-greeting');

    const regNameInput = document.getElementById('reg-name'); // Correctly reference the 'reg-name' input
    const regEmailInput = document.getElementById('reg-email');
    const regCountryCodeInput = document.getElementById('reg-country-code');
    const regPhoneInput = document.getElementById('reg-phone');
    const regPasswordInput = document.getElementById('reg-password');
    const regConfirmPasswordInput = document.getElementById('reg-confirm-password');
    const termsConditionsCheckbox = document.getElementById('terms-conditions');

    const passwordStrengthIndicator = document.getElementById('password-strength');
    const strengthBar = passwordStrengthIndicator ? passwordStrengthIndicator.querySelector('.strength-bar') : null;
    const strengthText = passwordStrengthIndicator ? passwordStrengthIndicator.querySelector('.strength-text') : null;
    const suggestPasswordBtn = document.getElementById('suggest-password-btn');
    const passwordMatchIconEl = document.getElementById('password-match-icon');

    const forgotEmailInput = document.getElementById('forgot-email');

    const togglePasswordIcons = document.querySelectorAll('.toggle-password');
    const messageArea = document.getElementById('message-area');
    const loadingOverlay = document.getElementById('loading-overlay');
    const currentYearSpan = document.getElementById('current-year');

    // --- State Variables ---
    let currentActiveFormName = 'login';

    // --- Initial Setup ---
    function initialize() {
        if (currentYearSpan) {
            currentYearSpan.textContent = new Date().getFullYear();
        }
        showForm('login');
        loadRememberedUser();
        setupEventListeners();
    }

    // --- Event Listeners Setup ---
    function setupEventListeners() {
        if (showRegisterLink) showRegisterLink.addEventListener('click', (e) => { e.preventDefault(); showForm('register'); });
        if (showLoginLink) showLoginLink.addEventListener('click', (e) => { e.preventDefault(); showForm('login'); });
        if (forgotPasswordLink) forgotPasswordLink.addEventListener('click', (e) => { e.preventDefault(); showForm('forgot-password'); });
        if (backToLoginLink) backToLoginLink.addEventListener('click', (e) => { e.preventDefault(); showForm('login'); });

        if (loginForm) loginForm.addEventListener('submit', handleLogin);
        if (registerForm) registerForm.addEventListener('submit', handleRegistration);
        if (forgotPasswordForm) forgotPasswordForm.addEventListener('submit', handleForgotPassword);

        togglePasswordIcons.forEach(icon => {
            icon.addEventListener('click', () => {
                const targetInputId = icon.dataset.target;
                const targetInput = document.getElementById(targetInputId);
                if (targetInput) togglePasswordVisibility(targetInput, icon);
            });
        });

        if (regPasswordInput) {
            regPasswordInput.addEventListener('input', () => {
                handlePasswordStrength();
                validatePasswordMatch();
            });
        }
        if (regConfirmPasswordInput) {
            regConfirmPasswordInput.addEventListener('input', validatePasswordMatch);
        }
        if (suggestPasswordBtn) {
            suggestPasswordBtn.addEventListener('click', suggestStrongPassword);
        }
    }

    // --- Form Navigation ---
    function showForm(formName) {
        clearAllMessages();
        currentActiveFormName = formName;
        const formsToManage = [
            { container: loginFormContainer, name: 'login', formEl: loginForm },
            { container: registerFormContainer, name: 'register', formEl: registerForm },
            { container: forgotPasswordFormContainer, name: 'forgot-password', formEl: forgotPasswordForm }
        ];

        formsToManage.forEach(f => {
            if (f.container) {
                if (f.name === formName) {
                    f.container.classList.add('active');
                    if (f.formEl) {
                        f.formEl.reset();
                        if (f.name === 'register') {
                            if (regCountryCodeInput) regCountryCodeInput.value = "+970";
                            resetPasswordStrength();
                            if (passwordMatchIconEl) {
                                passwordMatchIconEl.className = 'password-match-icon';
                                passwordMatchIconEl.innerHTML = '';
                            }
                        }
                    }
                    clearAllInputErrors(f.formEl);
                } else {
                    f.container.classList.remove('active');
                }
            }
        });

        if (formName === 'login') {
            loadRememberedUser();
        }
        const activeFormContainer = document.querySelector('.form-container.active');
        if (activeFormContainer) {
            activeFormContainer.scrollTop = 0;
        }
    }

    // --- UI Helpers ---
    function togglePasswordVisibility(inputField, icon) {
        if (inputField.type === 'password') {
            inputField.type = 'text';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        } else {
            inputField.type = 'password';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    }

    function showLoading(show) {
        if (loadingOverlay) loadingOverlay.style.display = show ? 'flex' : 'none';
    }

    function displayMessage(message, type, autoClearDelay = 4000) {
        if (!messageArea) return;
        messageArea.innerHTML = message;
        messageArea.className = 'message-display';
        messageArea.classList.add(type);
        messageArea.style.display = 'block';

        if (autoClearDelay > 0) {
            setTimeout(() => {
                if (messageArea.innerHTML === message && messageArea.classList.contains(type)) {
                    clearAllMessages();
                }
            }, autoClearDelay);
        }
    }

    function clearAllMessages() {
        if (!messageArea) return;
        messageArea.textContent = '';
        messageArea.style.display = 'none';
        messageArea.className = 'message-display';
    }

    // --- Password Strength & Suggestion ---
    function handlePasswordStrength() {
        if (!regPasswordInput || !strengthBar || !strengthText) return;
        const password = regPasswordInput.value;
        const strength = checkPasswordStrength(password);
        updatePasswordStrengthUI(strength, password);
    }

    function checkPasswordStrength(password) {
        let score = 0;
        if (!password) return 'none';
        if (password.length >= 8) score++;
        if (password.length >= 12) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^A-Za-z0-9\s]/.test(password)) score++;

        if (score <= 2) return 'weak';
        if (score <= 4) return 'medium';
        return 'strong';
    }

    function updatePasswordStrengthUI(strength, password) {
        if (!strengthBar || !strengthText) return;
        if (!password) {
            resetPasswordStrength();
            return;
        }
        strengthText.textContent = strength.charAt(0).toUpperCase() + strength.slice(1);
        let barColor = 'var(--border-light)';
        let textColor = 'var(--text-light)';
        let barWidth = '0%';

        switch (strength) {
            case 'weak': barWidth = '33%'; barColor = 'var(--error)'; textColor = 'var(--error)'; break;
            case 'medium': barWidth = '66%'; barColor = 'var(--warning)'; textColor = 'var(--warning)'; break;
            case 'strong': barWidth = '100%'; barColor = 'var(--success)'; textColor = 'var(--success)'; break;
        }
        strengthBar.style.width = barWidth;
        strengthBar.style.backgroundColor = barColor;
        strengthText.style.color = textColor;
    }

    function resetPasswordStrength() {
        if (!strengthBar || !strengthText) return;
        strengthBar.style.width = '0%';
        strengthBar.style.backgroundColor = 'var(--border-light)';
        strengthText.textContent = '';
        strengthText.style.color = 'var(--text-light)';
    }

    function generateStrongPassword() {
        const length = 14;
        const lower = "abcdefghijklmnopqrstuvwxyz";
        const upper = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        const digits = "0123456789";
        const symbols = "!@#$%^&*()_+~`|}{[]:;?><,./-=";
        const allChars = lower + upper + digits + symbols;

        let password = "";
        password += lower.charAt(Math.floor(Math.random() * lower.length));
        password += upper.charAt(Math.floor(Math.random() * upper.length));
        password += digits.charAt(Math.floor(Math.random() * digits.length));
        password += symbols.charAt(Math.floor(Math.random() * symbols.length));

        for (let i = 4; i < length; i++) {
            password += allChars.charAt(Math.floor(Math.random() * allChars.length));
        }
        return password.split('').sort(() => 0.5 - Math.random()).join('');
    }

    function suggestStrongPassword() {
        const newPassword = generateStrongPassword();
        if (regPasswordInput) regPasswordInput.value = newPassword;
        if (regConfirmPasswordInput) regConfirmPasswordInput.value = newPassword;
        handlePasswordStrength();
        validatePasswordMatch();
        displayMessage("Strong password generated and filled!", "success", 3000);
    }

    function validatePasswordMatch() {
        if (!regPasswordInput || !regConfirmPasswordInput || !passwordMatchIconEl) return;

        clearInputError(regConfirmPasswordInput);
        passwordMatchIconEl.className = 'password-match-icon';
        passwordMatchIconEl.innerHTML = '';

        if (regConfirmPasswordInput.value === "" && regPasswordInput.value === "") return true;
        if (regConfirmPasswordInput.value === "" && regPasswordInput.value !== "") return false;

        if (regPasswordInput.value === regConfirmPasswordInput.value) {
            passwordMatchIconEl.classList.add('match');
            passwordMatchIconEl.innerHTML = '<i class="fas fa-check-circle"></i>';
            return true;
        } else {
            setInputError(regConfirmPasswordInput, "Passwords do not match.");
            passwordMatchIconEl.classList.add('no-match');
            passwordMatchIconEl.innerHTML = '<i class="fas fa-times-circle"></i>';
            return false;
        }
    }

    // --- Input Validation ---
    function setInputError(inputElement, message) {
        if (!inputElement) return;
        inputElement.classList.add('invalid');
        const parentGroup = inputElement.closest('.input-group') || inputElement.closest('.phone-group');
        if (parentGroup) {
            const errorSpan = parentGroup.querySelector('.error-message');
            if (errorSpan) errorSpan.textContent = message;
        }
        if (inputElement.type === "checkbox" && inputElement.id === "terms-conditions") {
            const label = inputElement.closest('.checkbox-label');
            if (label) {
                label.classList.add('invalid');
                const errorSpanForCheckbox = label.closest('.input-group')?.querySelector('.error-message');
                if (errorSpanForCheckbox) errorSpanForCheckbox.textContent = message;
            }
        }
    }

    function clearInputError(inputElement) {
        if (!inputElement) return;
        inputElement.classList.remove('invalid');
        const parentGroup = inputElement.closest('.input-group') || inputElement.closest('.phone-group');
        if (parentGroup) {
            const errorSpan = parentGroup.querySelector('.error-message');
            if (errorSpan) errorSpan.textContent = '';
        }
        if (inputElement.type === "checkbox" && inputElement.id === "terms-conditions") {
            const label = inputElement.closest('.checkbox-label');
            if (label) {
                label.classList.remove('invalid');
                const errorSpanForCheckbox = label.closest('.input-group')?.querySelector('.error-message');
                if (errorSpanForCheckbox) errorSpanForCheckbox.textContent = '';
            }
        }
    }

    function clearAllInputErrors(formElement) {
        if (!formElement) return;
        formElement.querySelectorAll('input, select').forEach(el => clearInputError(el));
        formElement.querySelectorAll('.error-message').forEach(span => span.textContent = '');
    }

    function validateEmail(email) {
        const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    }

    function validateRegistrationForm() {
        clearAllInputErrors(registerForm);
        let isValid = true;
        
        // Name validation
        if (!regNameInput.value.trim()) { setInputError(regNameInput, "Full name is required."); isValid = false; } // Validate the new input
        
        // Email validation
        if (!regEmailInput.value.trim()) { setInputError(regEmailInput, "Email is required."); isValid = false; }
        else if (!validateEmail(regEmailInput.value)) { setInputError(regEmailInput, "Invalid email format."); isValid = false; }
        
        // Phone validation
        if (!regPhoneInput.value.trim()) { setInputError(regPhoneInput, "Phone number is required."); isValid = false; }
        else if (!/^\d{7,15}$/.test(regPhoneInput.value.trim())) { setInputError(regPhoneInput, "Invalid phone (7-15 digits)."); isValid = false; }
        
        // Password validation
        if (!regPasswordInput.value) { setInputError(regPasswordInput, "Password is required."); isValid = false; }
        else if (checkPasswordStrength(regPasswordInput.value) === 'weak') { setInputError(regPasswordInput, "Password is too weak."); isValid = false; }
        
        // Confirm password
        if (!regConfirmPasswordInput.value) { setInputError(regConfirmPasswordInput, "Confirm your password."); isValid = false; }
        else if (!validatePasswordMatch()) { isValid = false; }
        
        // Terms agreement
        if (!termsConditionsCheckbox.checked) { setInputError(termsConditionsCheckbox, "Agreement to terms is required."); isValid = false; }
        
        return isValid;
    }

    function validateLoginForm() {
        clearAllInputErrors(loginForm);
        let isValid = true;
        const passwordField = loginForm.querySelector('#login-password');
        if (!loginEmailInput.value.trim()) { setInputError(loginEmailInput, "Email is required."); isValid = false; }
        else if (!validateEmail(loginEmailInput.value)) { setInputError(loginEmailInput, "Invalid email format."); isValid = false; }
        if (!passwordField.value) { setInputError(passwordField, "Password is required."); isValid = false; }
        return isValid;
    }

    function validateForgotForm() {
        clearAllInputErrors(forgotPasswordForm);
        let isValid = true;
        if (!forgotEmailInput.value.trim()) { setInputError(forgotEmailInput, "Email is required."); isValid = false; }
        else if (!validateEmail(forgotEmailInput.value)) { setInputError(forgotEmailInput, "Invalid email format."); isValid = false; }
        return isValid;
    }

    // --- Form Submission Handlers (Simulated) ---
    async function handleRegistration(event) {
        event.preventDefault();
        if (!validateRegistrationForm()) {
            displayMessage("Please correct errors in the form.", "error");
            return;
        }

        const fullPhone = regCountryCodeInput.value.trim() + regPhoneInput.value.trim();

        const formData = {
            name: regNameInput.value.trim(), // Send a single 'name' field
            email: regEmailInput.value.trim(),
            phone: fullPhone,
            password: regPasswordInput.value
        };

        showLoading(true);
        try {
            const response = await fetch('register.php', { 
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            
            const result = await response.json();
            showLoading(false);

            if (result.status === 'success') {
                displayMessage(result.message, 'success');
                setTimeout(() => showForm('login'), 2000);
            } else {
                displayMessage(result.message, 'error');
            }
        } catch (error) {
            showLoading(false);
            displayMessage('An error occurred. Please try again.', 'error');
        }
    }

    async function handleLogin(event) {
        event.preventDefault();
        if (!validateLoginForm()) {
            displayMessage("Please correct errors.", "error");
            return;
        }

        const formData = {
            email: loginEmailInput.value.trim(),
            password: loginForm.querySelector('#login-password').value
        };

        showLoading(true);
        try {
            const response = await fetch('login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            const result = await response.json();
            showLoading(false);

            if (result.status === 'success') {
                // Store the full user object for the main page to use
                localStorage.setItem('userData', JSON.stringify(result.user));

                // "Remember Me" for pre-filling the email field
                if (rememberMeCheckbox.checked) {
                    localStorage.setItem('rememberedUserEmail', result.user.email);
                } else {
                    localStorage.removeItem('rememberedUserEmail');
                }
                
                displayMessage(result.message, 'success');
                // Redirect to the main order page
                setTimeout(() => {
                    window.location.href = 'index.html';
                }, 1500);
            } else {
                displayMessage(result.message, 'error');
            }
        } catch (error) {
            showLoading(false);
            displayMessage('An error occurred. Please try again.', 'error');
        }
    }

    function handleForgotPassword(event) {
        event.preventDefault();
        if (!validateForgotForm()) {
            displayMessage("Please enter a valid email.", "error");
            return;
        }
        showLoading(true);
        const email = forgotEmailInput.value.trim();
        setTimeout(() => {
            showLoading(false);
            displayMessage(`If an account exists for ${email}, a reset link has been "sent".`, "success");
            setTimeout(() => showForm('login'), 3000);
        }, 1500);
    }

    // --- Remember Me & Greeting ---
    function loadRememberedUser() {
        const rememberedEmail = localStorage.getItem('rememberedUserEmail');
        const personalizedGreeting = document.getElementById('personalized-greeting');

        if (rememberedEmail && loginEmailInput && rememberMeCheckbox) {
            loginEmailInput.value = rememberedEmail;
            rememberMeCheckbox.checked = true;
        } else if (rememberMeCheckbox) {
            rememberMeCheckbox.checked = false;
        }

        // The personalized greeting on this page is less critical now,
        // as the user will be redirected upon successful login.
        // This can be simplified or removed.
        if (personalizedGreeting) {
            personalizedGreeting.style.display = 'none';
        }
    }

    // --- Initialize ---
    initialize();
});