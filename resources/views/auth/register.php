<div class="auth-container">
    <div class="auth-box">
        <h1><?php echo function_exists('__') ? __('register') : 'Register'; ?></h1>

        <?php if (!empty($_SESSION['errors'])): ?>
            <div class="alert alert-danger">
                <ul style="margin: 0; padding-left: 1.5rem;">
                    <?php foreach ($_SESSION['errors'] as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php unset($_SESSION['errors']); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($_SESSION['error']); ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/register" class="auth-form">
            <div class="form-group">
                <label for="first_name"><?php echo function_exists('__') ? __('first_name') : 'First Name'; ?></label>
                <input type="text" id="first_name" name="first_name" placeholder="John">
            </div>

            <div class="form-group">
                <label for="last_name"><?php echo function_exists('__') ? __('last_name') : 'Last Name'; ?></label>
                <input type="text" id="last_name" name="last_name" placeholder="Doe">
            </div>

            <div class="form-group">
                <label for="email"><?php echo function_exists('__') ? __('email') : 'Email'; ?></label>
                <input type="email" id="email" name="email" required placeholder="your@email.com">
            </div>

            <div class="form-group">
                <label for="password"><?php echo function_exists('__') ? __('password') : 'Password'; ?></label>
                <input type="password" id="password" name="password" required placeholder="••••••••">
                <small style="color: #666; display: block; margin-top: 0.25rem;">
                    <?php echo function_exists('__') ? __('password_must_be_at_least_6_characters') : 'Must be at least 6 characters'; ?>
                </small>
            </div>

            <div class="form-group">
                <label for="password_confirm"><?php echo function_exists('__') ? __('confirm_password') : 'Confirm Password'; ?></label>
                <input type="password" id="password_confirm" name="password_confirm" required placeholder="••••••••">
            </div>

            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">

            <button type="submit" class="btn btn-primary btn-block">
                <?php echo function_exists('__') ? __('register') : 'Register'; ?>
            </button>
        </form>

        <div class="auth-links">
            <p><?php echo function_exists('__') ? __('already_have_account') : 'Already have an account?'; ?> 
                <a href="/login"><?php echo function_exists('__') ? __('login_here') : 'Login here'; ?></a>
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

    .alert-danger ul {
        margin: 0;
        padding-left: 1.5rem;
    }

    .alert-danger li {
        margin: 0.25rem 0;
    }
</style>
