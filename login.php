<?php
// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/auth_check.php';

// If user is already logged in, redirect to dashboard
redirectIfLoggedIn();

$pageTitle = "Login - roperty Custodian Management";

ob_start();
?>

<?php include 'components/login.php'; ?>

<script src="js/api.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const otpForm = document.getElementById('otpForm');
    const otpStep = document.getElementById('otpStep');
    const credentialsStep = document.getElementById('credentialsStep');
    const authMessageContainer = document.getElementById('authMessageContainer');
    const otpEmailHint = document.getElementById('otpEmailHint');
    const resendOtpBtn = document.getElementById('resendOtpBtn');
    const changeAccountBtn = document.getElementById('changeAccountBtn');

    let otpToken = null;

    loginForm?.addEventListener('submit', async function(e) {
        e.preventDefault();
        if (loginForm.dataset.loading === 'true') return;

        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;

        if (!username || !password) {
            showMessage('Please enter both email address and password.', 'error');
            return;
        }

        try {
            setLoading(loginForm, true);
            const response = await fetch('api/auth.php?action=login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, password })
            });

            const result = await safeJson(response);

            if (response.ok && result?.status === 'otp_required') {
                otpToken = result.otp_token;
                otpEmailHint.textContent = result.email_hint || username;
                switchStep('otp');
                const infoMessage = result.message || 'We sent a verification code to your email.';
                const devHint = result.otp_dev_code ? ` Your code (dev mode): ${result.otp_dev_code}.` : '';
                showMessage(infoMessage + devHint, 'info');
            } else if (response.ok && result?.user) {
                sessionStorage.setItem('currentUser', JSON.stringify(result.user));
                window.location.href = 'dashboard.php';
            } else {
                showMessage(result?.message || 'Login failed. Please try again.', 'error');
            }
        } catch (error) {
            console.error(error);
            showMessage('An error occurred. Please try again.', 'error');
        } finally {
            setLoading(loginForm, false);
        }
    });

    otpForm?.addEventListener('submit', async function(e) {
        e.preventDefault();
        if (!otpToken) {
            showMessage('Your verification session has expired. Please sign in again.', 'error');
            switchStep('credentials');
            return;
        }
        if (otpForm.dataset.loading === 'true') return;

        const otpCode = document.getElementById('otpCode').value.trim();

        if (!/^[0-9]{6}$/.test(otpCode)) {
            showMessage('Enter the 6-digit code from your email.', 'error');
            return;
        }

        try {
            setLoading(otpForm, true);
            const response = await fetch('api/auth.php?action=verify_otp', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ otp_token: otpToken, otp_code: otpCode })
            });

            const result = await safeJson(response);

            if (response.ok && result?.user) {
                sessionStorage.setItem('currentUser', JSON.stringify(result.user));
                window.location.href = 'dashboard.php';
            } else {
                const attemptsRemaining = result?.attempts_remaining;
                const message = attemptsRemaining !== undefined
                    ? `${result?.message || 'Invalid code.'} Attempts remaining: ${attemptsRemaining}.`
                    : (result?.message || 'Verification failed.');
                showMessage(message, 'error');

                if (response.status === 410 || response.status === 429) {
                    otpToken = null;
                    switchStep('credentials');
                }
            }
        } catch (error) {
            console.error(error);
            showMessage('Unable to verify the code. Please try again.', 'error');
        } finally {
            setLoading(otpForm, false);
        }
    });

    resendOtpBtn?.addEventListener('click', async function() {
        if (!otpToken) {
            showMessage('Your verification session has expired. Please sign in again.', 'error');
            switchStep('credentials');
            return;
        }

        try {
            resendOtpBtn.disabled = true;
            const response = await fetch('api/auth.php?action=resend_otp', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ otp_token: otpToken })
            });

            const result = await safeJson(response);

            if (response.ok && result?.otp_token) {
                otpToken = result.otp_token;
                const infoMessage = result?.message || 'We sent another code to your email.';
                const devHint = result?.otp_dev_code ? ` Your code (dev mode): ${result.otp_dev_code}.` : '';
                showMessage(infoMessage + devHint, 'info');
            } else {
                showMessage(result?.message || 'Unable to resend the code. Please try again shortly.', 'error');
            }
        } catch (error) {
            console.error(error);
            showMessage('Unable to resend the code. Please try again.', 'error');
        } finally {
            setTimeout(() => {
                resendOtpBtn.disabled = false;
            }, 5000);
        }
    });

    changeAccountBtn?.addEventListener('click', function() {
        otpToken = null;
        switchStep('credentials');
        loginForm.reset();
        document.getElementById('otpCode').value = '';
        showMessage('Sign in with your credentials to request a new code.', 'info');
    });

    function switchStep(step) {
        if (step === 'otp') {
            credentialsStep?.classList.add('hidden');
            otpStep?.classList.remove('hidden');
            otpForm?.reset();
            document.getElementById('otpCode').focus();
        } else {
            credentialsStep?.classList.remove('hidden');
            otpStep?.classList.add('hidden');
            otpForm?.reset();
        }
    }

    function showMessage(message, type = 'info') {
        if (!authMessageContainer) return;
        authMessageContainer.innerHTML = '';

        const alert = document.createElement('div');
        const baseClass = 'rounded-xl px-4 py-3 text-sm border';

        if (type === 'error') {
            alert.className = `${baseClass} border-red-200 bg-red-50 text-red-700`;
        } else if (type === 'success') {
            alert.className = `${baseClass} border-green-200 bg-green-50 text-green-700`;
        } else {
            alert.className = `${baseClass} border-blue-200 bg-blue-50 text-blue-700`;
        }

        alert.textContent = message;
        authMessageContainer.appendChild(alert);
    }

    function setLoading(formEl, isLoading) {
        if (!formEl) return;
        formEl.dataset.loading = isLoading ? 'true' : 'false';
        const submitBtn = formEl.querySelector('button[type="submit"]');
        if (!submitBtn) return;
        submitBtn.disabled = isLoading;
        submitBtn.classList.toggle('opacity-60', isLoading);
        submitBtn.classList.toggle('cursor-not-allowed', isLoading);
    }

    async function safeJson(response) {
        try {
            return await response.json();
        } catch (error) {
            return null;
        }
    }
});
</script>

<?php
$content = ob_get_clean();
include 'layouts/layout.php';
?>
