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
                <input type="password" id="password" name="password" required placeholder="••••••••">
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

            <div class="social-login-divider">
                <span><?php echo function_exists('__') ? __('or_login_with') : 'Or login with'; ?></span>
            </div>

            <div class="social-login-buttons">
                <a href="/auth/google" class="btn btn-google btn-block">
                    <img src="https://www.google.com/favicon.ico" alt="Google" width="20"> Google
                </a>
                <a href="/auth/facebook" class="btn btn-facebook btn-block">
                    <img src="https://www.facebook.com/favicon.ico" alt="Facebook" width="20"> Facebook
                </a>
            </div>
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
</style>
