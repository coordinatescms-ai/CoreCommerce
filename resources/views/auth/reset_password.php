<div class="auth-container">
    <div class="auth-box">
        <h1><?php echo function_exists('__') ? __('reset_password') : 'Reset Password'; ?></h1>

        <p style="color: #666; margin-bottom: 1.5rem; text-align: center;">
            <?php echo function_exists('__') ? __('enter_new_password') : 'Enter your new password below.'; ?>
        </p>

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

        <form method="POST" action="/reset-password" class="auth-form">
            <div class="form-group">
                <label for="password"><?php echo function_exists('__') ? __('new_password') : 'New Password'; ?></label>
                <input type="password" id="password" name="password" required placeholder="••••••••">
                <small style="color: #666; display: block; margin-top: 0.25rem;">
                    <?php echo function_exists('__') ? __('password_must_be_at_least_6_characters') : 'Must be at least 6 characters'; ?>
                </small>
            </div>

            <div class="form-group">
                <label for="password_confirm"><?php echo function_exists('__') ? __('confirm_password') : 'Confirm Password'; ?></label>
                <input type="password" id="password_confirm" name="password_confirm" required placeholder="••••••••">
            </div>

            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token ?? ''); ?>">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">

            <button type="submit" class="btn btn-primary btn-block">
                <?php echo function_exists('__') ? __('reset_password') : 'Reset Password'; ?>
            </button>
        </form>

        <div class="auth-links">
            <p>
                <a href="/login"><?php echo function_exists('__') ? __('back_to_login') : 'Back to Login'; ?></a>
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
        box-sizing: border-box;
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
</style>
