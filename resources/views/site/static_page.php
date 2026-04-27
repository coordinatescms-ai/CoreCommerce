<article class="static-page">
    <div class="container">
        <h1><?= htmlspecialchars($page['title']) ?></h1>
        
        <hr>

        <div class="page-content">
            <!-- Виводимо HTML як є -->
            <?= $page['content'] ?>
        </div>
    </div>
</article>

<style>
    .static-page { padding: 40px 0; line-height: 1.6; }
    .page-content ul { margin-left: 25px; list-style: disc; }
    .page-content h2 { margin-top: 25px; }
</style>
