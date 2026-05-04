<?php
$reviews = $reviews ?? [];
$filters = $filters ?? ['product' => '', 'author' => '', 'status' => ''];
$page = (int) ($page ?? 1);
$pages = (int) ($pages ?? 1);
?>
<div class="settings-card">
    <h3>Модерація відгуків</h3>
    <form method="GET" action="/admin/settings" class="mb-3" style="display:grid;grid-template-columns:2fr 2fr 1fr auto;gap:8px;">
        <input type="hidden" name="tab" value="reviews">
        <input type="text" name="product" value="<?= htmlspecialchars((string)$filters['product']) ?>" placeholder="Пошук товару">
        <input type="text" name="author" value="<?= htmlspecialchars((string)$filters['author']) ?>" placeholder="Пошук автора">
        <select name="status">
            <option value="" <?= $filters['status']===''?'selected':'' ?>>Всі</option>
            <option value="1" <?= $filters['status']==='1'?'selected':'' ?>>Видимі</option>
            <option value="0" <?= $filters['status']==='0'?'selected':'' ?>>Заблоковані</option>
        </select>
        <button class="btn btn-primary" type="submit">Фільтр</button>
    </form>

    <?php if (empty($reviews)): ?>
        <p>Відгуки не знайдені.</p>
    <?php else: ?>
        <?php foreach ($reviews as $review): ?>
            <div style="border:1px solid #e5e7eb;border-radius:8px;padding:10px;margin-bottom:10px;<?= !empty($review['parent_id']) ? 'margin-left:24px;' : '' ?>">
                <div><strong><?= htmlspecialchars((string)$review['author_name']) ?></strong> · <?= htmlspecialchars((string)$review['product_name']) ?> · <?= htmlspecialchars((string)$review['created_at']) ?></div>
                <?php if (!empty($review['parent_author'])): ?><div><small>Відповідь на: <?= htmlspecialchars((string)$review['parent_author']) ?></small></div><?php endif; ?>
                <form method="POST" action="/admin/reviews/update/<?= (int)$review['id'] ?>">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(\App\Core\Http\Csrf::token()) ?>">
                    <textarea name="body" rows="3" maxlength="2000" style="width:100%;margin-top:6px;"><?= htmlspecialchars((string)$review['body']) ?></textarea>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
                        <button class="btn btn-success" type="submit">Зберегти</button>
                </form>
                <form method="POST" action="/admin/reviews/toggle/<?= (int)$review['id'] ?>">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(\App\Core\Http\Csrf::token()) ?>">
                    <input type="hidden" name="is_visible" value="<?= !empty($review['is_visible']) ? '0' : '1' ?>">
                    <button class="btn btn-warning" type="submit"><?= !empty($review['is_visible']) ? 'Заблокувати' : 'Розблокувати' ?></button>
                </form>
                <form method="POST" action="/admin/reviews/delete/<?= (int)$review['id'] ?>" onsubmit="return confirm('Видалити відгук?');">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(\App\Core\Http\Csrf::token()) ?>">
                    <button class="btn btn-danger" type="submit">Видалити</button>
                </form>
                    </div>
            </div>
        <?php endforeach; ?>

        <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <?php for ($p = 1; $p <= $pages; $p++): ?>
                <a class="btn <?= $p === $page ? 'btn-primary' : 'btn-light' ?>" href="/admin/settings?tab=reviews&page=<?= $p ?>&product=<?= urlencode((string)$filters['product']) ?>&author=<?= urlencode((string)$filters['author']) ?>&status=<?= urlencode((string)$filters['status']) ?>"><?= $p ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>
