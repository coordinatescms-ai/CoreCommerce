<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .header { background: #dc2626; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 20px; }
        .footer { font-size: 0.8rem; color: #777; text-align: center; margin-top: 20px; }
        .btn { display: inline-block; background: #dc2626; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Відновлення пароля</h1>
        </div>
        <div class="content">
            <p>Вітаємо, <?php echo htmlspecialchars($first_name); ?>!</p>
            <p>Ми отримали запит на відновлення пароля для вашого акаунту. Якщо це зробили ви, натисніть на кнопку нижче, щоб встановити новий пароль:</p>
            <p style="text-align: center;">
                <a href="<?php echo $reset_link; ?>" class="btn">Змінити пароль</a>
            </p>
            <p>Це посилання дійсне протягом 1 години. Якщо ви не надсилали запит на зміну пароля, просто проігноруйте цей лист.</p>
        </div>
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> MySite Store. Всі права захищено.</p>
        </div>
    </div>
</body>
</html>
