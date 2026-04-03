<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .header { background: #2563eb; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 20px; }
        .footer { font-size: 0.8rem; color: #777; text-align: center; margin-top: 20px; }
        .btn { display: inline-block; background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Ласкаво просимо до MySite!</h1>
        </div>
        <div class="content">
            <p>Вітаємо, <?php echo htmlspecialchars($first_name); ?>!</p>
            <p>Дякуємо за реєстрацію в нашому інтернет-магазині. Щоб активувати ваш акаунт, будь ласка, натисніть на кнопку нижче:</p>
            <p style="text-align: center;">
                <a href="<?php echo $confirmation_link; ?>" class="btn">Підтвердити реєстрацію</a>
            </p>
            <p>Якщо ви не реєструвалися на нашому сайті, просто проігноруйте цей лист.</p>
        </div>
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> MySite Store. Всі права захищено.</p>
        </div>
    </div>
</body>
</html>
