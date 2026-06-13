<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Сторінку не знайдено</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background:#f8fafc; color:#0f172a;
            display:flex; align-items:center; justify-content:center;
            min-height:100vh; padding:2rem;
        }
        .wrap  { text-align:center; max-width:480px; }
        .code  { font-size:8rem; font-weight:900; color:#e2e8f0;
                 line-height:1; letter-spacing:-.04em; }
        .title { font-size:1.5rem; font-weight:700; margin:.5rem 0 .75rem; }
        .desc  { color:#64748b; font-size:1rem; line-height:1.6; margin-bottom:2rem; }
        .btn   { display:inline-block; background:#6366f1; color:#fff;
                 text-decoration:none; padding:.75rem 1.75rem;
                 border-radius:8px; font-weight:600; font-size:.95rem;
                 transition:background .2s; }
        .btn:hover { background:#4f46e5; }
        .back  { display:block; margin-top:1rem; color:#94a3b8;
                 font-size:.875rem; text-decoration:none; }
        .back:hover { color:#6366f1; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="code">404</div>
        <h1 class="title">Сторінку не знайдено</h1>
        <p class="desc">
            Схоже, ця сторінка переїхала або ніколи не існувала.<br>
            Перевірте адресу або поверніться на головну.
        </p>
        <a href="/" class="btn">На головну</a>
        <a href="javascript:history.back()" class="back">← Назад</a>
    </div>
</body>
</html>
