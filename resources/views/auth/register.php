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
                <small style="color: #666; display: block; margin-top: 0.25rem;">
                    <?php echo function_exists('__') ? __('password_must_be_at_least_6_characters') : 'Must be at least 6 characters'; ?>
                </small>
            </div>

            <div class="form-group">
                <label for="password_confirm"><?php echo function_exists('__') ? __('confirm_password') : 'Confirm Password'; ?></label>
                <div class="password-input-wrapper">
                    <input type="password" id="password_confirm" name="password_confirm" required placeholder="••••••••">
                    <button type="button" class="password-toggle" data-target="password_confirm" aria-label="Toggle password visibility">
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
