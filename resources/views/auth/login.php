<div class="auth-container">
    <div class="auth-box">
        <h1><?php echo function_exists('__') ? __('login') : 'Login'; ?></h1>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($_SESSION['error']); ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_SESSION['success']); ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/login" class="auth-form">
            <div class="form-group">
                <label for="email"><?php echo function_exists('__') ? __('email') : 'Email'; ?></label>
                <input type="email" id="email" name="email" required placeholder="your@email.com">
            </div>

            <div class="form-group">
                <label for="password"><?php echo function_exists('__') ? __('password') : 'Password'; ?></label>
                <div class="password-input-wrapper">
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                    <button type="button" class="password-toggle" data-target="password" aria-label="Toggle password visibility">
                        <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg class="eye-off-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="remember_me" value="1">
                    <?php echo function_exists('__') ? __('remember_me') : 'Remember me'; ?>
                </label>
            </div>

            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">

            <button type="submit" class="btn btn-primary btn-block">
                <?php echo function_exists('__') ? __('login') : 'Login'; ?>
            </button>

            <?php
                $googleEnabled = function_exists('get_setting') && get_setting('google_auth_enabled', '0') === '1';
                $facebookEnabled = function_exists('get_setting') && get_setting('facebook_auth_enabled', '0') === '1';
            ?>
            <?php if ($googleEnabled || $facebookEnabled): ?>
            <div class="social-login-divider">
                <span><?php echo function_exists('__') ? __('or_login_with') : 'Or login with'; ?></span>
            </div>

            <div class="social-login-buttons">
                <?php if ($googleEnabled): ?>
                <a href="/auth/google" class="btn btn-google btn-block">
                    <img src="https://www.google.com/favicon.ico" alt="Google" width="20"> Google
                </a>
                <?php endif; ?>
                <?php if ($facebookEnabled): ?>
                <a href="/auth/facebook" class="btn btn-facebook btn-block">
                    <img src="https://www.facebook.com/favicon.ico" alt="Facebook" width="20"> Facebook
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </form>

        <div class="auth-links">
            <p><?php echo function_exists('__') ? __('dont_have_account') : "Don't have an account?"; ?> 
                <a href="/register"><?php echo function_exists('__') ? __('register_here') : 'Register here'; ?></a>
            </p>
            <p>
                <a href="/forgot-password"><?php echo function_exists('__') ? __('forgot_password') : 'Forgot password?'; ?></a>
            </p>
        </div>
    </div>
</div>

<style>
    .auth-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 60vh;
        padding: 2rem;
    }

    .auth-box {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 400px;
    }

    .auth-box h1 {
        margin-bottom: 1.5rem;
        text-align: center;
        color: #1e293b;
    }

    .auth-form .form-group {
        margin-bottom: 1rem;
    }

    .auth-form label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #1e293b;
    }

    .auth-form input[type="email"],
    .auth-form input[type="password"],
    .auth-form input[type="text"] {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .auth-form input[type="email"]:focus,
    .auth-form input[type="password"]:focus,
    .auth-form input[type="text"]:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .btn-block {
        width: 100%;
        margin-top: 1rem;
    }

    .auth-links {
        margin-top: 1.5rem;
        text-align: center;
        font-size: 0.9rem;
    }

    .auth-links p {
        margin: 0.5rem 0;
    }

    .auth-links a {
        color: #2563eb;
        text-decoration: none;
    }

    .auth-links a:hover {
        text-decoration: underline;
    }

    .alert {
        padding: 0.75rem;
        border-radius: 4px;
        margin-bottom: 1rem;
    }

    .alert-success {
        background-color: #d1fae5;
        color: #065f46;
        border: 1px solid #6ee7b7;
    }

    .alert-danger {
        background-color: #fee2e2;
        color: #7f1d1d;
        border: 1px solid #fca5a5;
    }

    .social-login-divider {
        margin: 1.5rem 0;
        text-align: center;
        position: relative;
    }

    .social-login-divider::before {
        content: "";
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 1px;
        background: #e2e8f0;
        z-index: 1;
    }

    .social-login-divider span {
        background: white;
        padding: 0 0.75rem;
        color: #64748b;
        font-size: 0.85rem;
        position: relative;
        z-index: 2;
    }

    .social-login-buttons {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .btn-google {
        background: #fff;
        color: #1e293b;
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
    }

    .btn-google:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .btn-facebook {
        background: #1877f2;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
    }

    .btn-facebook:hover {
        background: #166fe5;
    }

    .password-input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .password-input-wrapper input {
        padding-right: 3rem;
    }

    .password-toggle {
        position: absolute;
        right: 0.5rem;
        background: none;
        border: none;
        cursor: pointer;
        padding: 0.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        transition: color 0.2s ease;
    }

    .password-toggle:hover {
        color: #2563eb;
    }

    .password-toggle svg {
        width: 20px;
        height: 20px;
    }
</style>

<script>
document.querySelectorAll('.password-toggle').forEach(button => {
    button.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target');
        const input = document.getElementById(targetId);
        const eyeIcon = this.querySelector('.eye-icon');
        const eyeOffIcon = this.querySelector('.eye-off-icon');
        
        if (input.type === 'password') {
            input.type = 'text';
            eyeIcon.style.display = 'none';
            eyeOffIcon.style.display = 'block';
        } else {
            input.type = 'password';
            eyeIcon.style.display = 'block';
            eyeOffIcon.style.display = 'none';
        }
    });
});
</script>
