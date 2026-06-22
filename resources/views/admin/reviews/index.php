<?php
/**
 * @var array                          $reviews
 * @var \App\Core\Pagination\Paginator $pager
 * @var string                         $filterStatus
 * @var string                         $filterSearch
 * @var string                         $csrf
 */

function reviewStars(int|null $rating): string {
    if ($rating === null) return '<span style="color:#94a3b8; font-size:.8rem;">—</span>';
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $out .= '<i class="fas fa-star" style="font-size:.75rem; color:' . ($i <= $rating ? '#f59e0b' : '#e2e8f0') . ';"></i>';
    }
    return $out;
}
?>

<style>
/* ── Page header ── */
.rv-header          { display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem; }
.rv-header h1       { font-size:1.4rem; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:.5rem; }
.rv-header h1 i     { color:#6366f1; }
.rv-badge           { display:inline-flex; align-items:center; justify-content:center; min-width:22px; height:22px;
                      padding:0 6px; background:#6366f1; color:#fff; font-size:.72rem;
                      font-weight:700; border-radius:20px; }

/* ── Toolbar ── */
.rv-toolbar         { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:1rem 1.25rem;
                      margin-bottom:1.25rem; display:flex; align-items:center; gap:.75rem; flex-wrap:wrap; }
.rv-search          { flex:1; min-width:200px; display:flex; align-items:center; gap:.5rem;
                      border:1px solid #e2e8f0; border-radius:8px; padding:.45rem .75rem; background:#f8fafc; }
.rv-search i        { color:#94a3b8; font-size:.85rem; flex-shrink:0; }
.rv-search input    { border:none; background:none; outline:none; font-size:.9rem; width:100%; color:#0f172a; }
.rv-filter-tabs     { display:flex; gap:.35rem; }
.rv-tab             { padding:.4rem .9rem; border-radius:20px; font-size:.82rem; font-weight:600;
                      text-decoration:none; color:#64748b; background:#f1f5f9; border:1px solid transparent;
                      transition:.15s; white-space:nowrap; }
.rv-tab:hover       { background:#e2e8f0; }
.rv-tab.active      { background:#6366f1; color:#fff; }
.rv-tab.active.green { background:#10b981; }
.rv-tab.active.red   { background:#ef4444; }

/* ── Bulk actions ── */
.rv-bulk            { display:none; align-items:center; gap:.5rem; padding:.5rem .85rem;
                      background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; font-size:.85rem; }
.rv-bulk.visible    { display:flex; }
.rv-bulk strong     { color:#1d4ed8; }
.rv-bulk-sep        { width:1px; height:20px; background:#bfdbfe; }

/* ── Table ── */
.rv-card            { background:#fff; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; }
.rv-table           { width:100%; border-collapse:collapse; }
.rv-table thead th  { background:#f8fafc; color:#64748b; font-weight:700; font-size:.78rem;
                      text-transform:uppercase; letter-spacing:.04em;
                      padding:.7rem 1rem; text-align:left; border-bottom:2px solid #e2e8f0; white-space:nowrap; }
.rv-table thead th.th-check { width:44px; text-align:center; }
.rv-table tbody td  { padding:.75rem 1rem; border-bottom:1px solid #f1f5f9; vertical-align:top; font-size:.88rem; }
.rv-table tbody tr:last-child td { border-bottom:none; }
.rv-table tbody tr:hover { background:#fafbff; }
.rv-table tbody tr.rv-hidden td { opacity:.6; }

/* Author cell */
.rv-author          { display:flex; align-items:center; gap:.6rem; }
.rv-avatar          { width:32px; height:32px; border-radius:50%; background:#e0e7ff;
                      color:#6366f1; font-weight:700; font-size:.8rem;
                      display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.rv-author-info     { }
.rv-author-name     { font-weight:600; color:#0f172a; line-height:1.2; }
.rv-author-email    { font-size:.75rem; color:#94a3b8; }

/* Product cell */
.rv-product         { display:flex; flex-direction:column; gap:.15rem; }
.rv-product a       { color:#6366f1; text-decoration:none; font-weight:500; font-size:.85rem; }
.rv-product a:hover { text-decoration:underline; }
.rv-reply-badge     { display:inline-flex; align-items:center; gap:.25rem;
                      font-size:.72rem; color:#64748b; background:#f1f5f9;
                      border-radius:4px; padding:1px 6px; width:fit-content; margin-top:.15rem; }

/* Body cell */
.rv-body            { max-width:320px; }
.rv-body-text       { color:#334155; line-height:1.5; display:-webkit-box;
                      -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.rv-body-text.expanded { -webkit-line-clamp:unset; }
.rv-expand-btn      { font-size:.75rem; color:#6366f1; cursor:pointer; background:none;
                      border:none; padding:0; margin-top:.15rem; display:none; }

/* Status badge */
.rv-status          { display:inline-flex; align-items:center; gap:.3rem;
                      padding:.25rem .65rem; border-radius:20px; font-size:.75rem; font-weight:700; white-space:nowrap; }
.rv-status.visible  { background:#dcfce7; color:#166534; }
.rv-status.hidden   { background:#fee2e2; color:#991b1b; }

/* Actions */
.rv-actions         { display:flex; align-items:center; gap:.4rem; white-space:nowrap; }
.rv-btn             { display:inline-flex; align-items:center; justify-content:center;
                      width:32px; height:32px; border-radius:7px; border:1px solid #e2e8f0;
                      background:#fff; cursor:pointer; font-size:.85rem; transition:.15s;
                      text-decoration:none; color:#64748b; }
.rv-btn:hover       { border-color:#6366f1; color:#6366f1; }
.rv-btn.rv-btn-danger:hover { border-color:#ef4444; color:#ef4444; }
.rv-btn.rv-btn-toggle-on:hover  { border-color:#ef4444; color:#ef4444; }
.rv-btn.rv-btn-toggle-off:hover { border-color:#10b981; color:#10b981; }

/* Empty state */
.rv-empty           { text-align:center; padding:3rem; color:#94a3b8; }
.rv-empty i         { font-size:2.5rem; display:block; margin-bottom:.75rem; color:#e2e8f0; }

/* Pagination */
.rv-pagination      { display:flex; justify-content:space-between; align-items:center;
                      padding:1rem 1.25rem; border-top:1px solid #f1f5f9; flex-wrap:wrap; gap:.5rem; }
.rv-pag-info        { font-size:.85rem; color:#64748b; }
.rv-pag-links       { display:flex; gap:.3rem; flex-wrap:wrap; }
.rv-pag-btn         { display:inline-flex; align-items:center; justify-content:center;
                      min-width:34px; height:34px; padding:0 .5rem; border-radius:7px;
                      border:1px solid #e2e8f0; background:#fff; text-decoration:none;
                      color:#334155; font-size:.85rem; font-weight:500; transition:.15s; }
.rv-pag-btn:hover   { border-color:#6366f1; color:#6366f1; }
.rv-pag-btn.active  { background:#6366f1; color:#fff; border-color:#6366f1; }
.rv-pag-btn.disabled { opacity:.4; pointer-events:none; }
.rv-pag-dots        { display:inline-flex; align-items:center; padding:0 .4rem; color:#94a3b8; }

/* Toast */
.rv-toast           { position:fixed; bottom:1.5rem; right:1.5rem; z-index:9999;
                      background:#0f172a; color:#fff; padding:.7rem 1.2rem;
                      border-radius:8px; font-size:.88rem; max-width:320px;
                      box-shadow:0 8px 24px rgba(0,0,0,.18); opacity:0;
                      transform:translateY(10px); transition:all .2s ease; pointer-events:none; }
.rv-toast.show      { opacity:1; transform:translateY(0); }
.rv-toast.success   { background:#10b981; }
.rv-toast.error     { background:#ef4444; }
</style>

<!-- Header -->
<div class="rv-header">
    <h1>
        <i class="fas fa-comments"></i>
        Коментарі
        <span class="rv-badge"><?= number_format($pager->total) ?></span>
    </h1>
</div>

<!-- Toolbar: пошук + фільтри -->
<div class="rv-toolbar">
    <form method="GET" action="/admin/reviews" style="flex:1; display:flex; align-items:center; gap:.75rem; flex-wrap:wrap; min-width:0;">
        <?php if ($filterStatus !== 'all'): ?>
            <input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>">
        <?php endif; ?>

        <div class="rv-search">
            <i class="fas fa-search"></i>
            <input type="text" name="search" value="<?= htmlspecialchars($filterSearch) ?>"
                   placeholder="Пошук за автором, товаром, текстом…">
        </div>
        <?php if ($filterSearch !== ''): ?>
            <a href="<?= $pager->url(1) ?>" class="rv-btn" title="<?= __('reset') ?>">
                <i class="fas fa-times"></i>
            </a>
        <?php endif; ?>
        <button type="submit" class="rv-btn" title="<?= __('search') ?>" style="background:#6366f1; color:#fff; border-color:#6366f1;">
            <i class="fas fa-search"></i>
        </button>
    </form>

    <div class="rv-filter-tabs">
        <a href="<?= '/admin/reviews?' . http_build_query(array_filter(['search' => $filterSearch], fn($v) => $v !== '')) ?>"
           class="rv-tab <?= $filterStatus === 'all' ? 'active' : '' ?>">
            Усі
        </a>
        <a href="<?= '/admin/reviews?' . http_build_query(array_filter(['search' => $filterSearch, 'status' => 'visible'], fn($v) => $v !== '')) ?>"
           class="rv-tab <?= $filterStatus === 'visible' ? 'active green' : '' ?>">
            <i class="fas fa-eye" style="font-size:.75rem;"></i> Видимі
        </a>
        <a href="<?= '/admin/reviews?' . http_build_query(array_filter(['search' => $filterSearch, 'status' => 'hidden'], fn($v) => $v !== '')) ?>"
           class="rv-tab <?= $filterStatus === 'hidden' ? 'active red' : '' ?>">
            <i class="fas fa-eye-slash" style="font-size:.75rem;"></i> Заблоковані
        </a>
    </div>
</div>

<!-- Bulk action bar -->
<div class="rv-bulk" id="bulk-bar">
    <strong id="bulk-count">0</strong>&nbsp;обрано
    <div class="rv-bulk-sep"></div>
    <button class="btn btn-outline" style="font-size:.82rem; padding:.3rem .8rem; border:1px solid #bfdbfe; background:#fff;"
            onclick="bulkDo('show')">
        <i class="fas fa-eye"></i> Опублікувати
    </button>
    <button class="btn btn-outline" style="font-size:.82rem; padding:.3rem .8rem; border:1px solid #bfdbfe; background:#fff;"
            onclick="bulkDo('hide')">
        <i class="fas fa-eye-slash"></i> Заблокувати
    </button>
    <button class="btn btn-danger" style="font-size:.82rem; padding:.3rem .8rem;"
            onclick="bulkDo('delete')">
        <i class="fas fa-trash"></i> Видалити
    </button>
</div>

<!-- Table -->
<div class="rv-card">
    <table class="rv-table">
        <thead>
            <tr>
                <th class="th-check">
                    <input type="checkbox" id="check-all" title="Обрати всі">
                </th>
                <th><?= __('review_author') ?></th>
                <th>Товар</th>
                <th><?= __('review_comment') ?></th>
                <th><?= __('review_rating') ?></th>
                <th>Статус</th>
                <th>Дата</th>
                <th>Дії</th>
            </tr>
        </thead>
        <tbody id="reviews-tbody">
        <?php if (empty($reviews)): ?>
            <tr>
                <td colspan="8">
                    <div class="rv-empty">
                        <i class="fas fa-comments"></i>
                        <?= $filterSearch !== '' ? __('nothing_found') : __('reviews_empty') ?>
                    </div>
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($reviews as $r): ?>
            <?php
                $initials = mb_strtoupper(mb_substr($r['author_name'] ?? 'U', 0, 1));
                $isHidden = (int)$r['is_visible'] === 0;
                $isReply  = $r['parent_id'] !== null;
                $bodyFull = htmlspecialchars((string)$r['body']);
                $bodyShort = mb_strlen((string)$r['body']) > 120;
            ?>
            <tr class="<?= $isHidden ? 'rv-hidden' : '' ?>" id="row-<?= (int)$r['id'] ?>">
                <td style="text-align:center; vertical-align:middle;">
                    <input type="checkbox" class="rv-check" value="<?= (int)$r['id'] ?>">
                </td>
                <td>
                    <div class="rv-author">
                        <div class="rv-avatar"><?= $initials ?></div>
                        <div class="rv-author-info">
                            <div class="rv-author-name"><?= htmlspecialchars((string)$r['author_name']) ?></div>
                            <?php if ($r['user_email']): ?>
                                <div class="rv-author-email"><?= htmlspecialchars((string)$r['user_email']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="rv-product">
                        <?php if ($r['product_name']): ?>
                            <a href="/product/<?= htmlspecialchars((string)$r['product_slug']) ?>" target="_blank">
                                <?= htmlspecialchars((string)$r['product_name']) ?>
                            </a>
                        <?php else: ?>
                            <span style="color:#94a3b8;">—</span>
                        <?php endif; ?>
                        <?php if ($isReply): ?>
                            <span class="rv-reply-badge">
                                <i class="fas fa-reply" style="font-size:.65rem;"></i> відповідь
                            </span>
                        <?php endif; ?>
                    </div>
                </td>
                <td class="rv-body">
                    <div class="rv-body-text" id="body-<?= (int)$r['id'] ?>"><?= $bodyFull ?></div>
                    <?php if ($bodyShort): ?>
                        <button class="rv-expand-btn" id="expand-<?= (int)$r['id'] ?>"
                                onclick="expandBody(<?= (int)$r['id'] ?>)" style="display:block;">
                            розгорнути
                        </button>
                    <?php endif; ?>
                </td>
                <td style="white-space:nowrap;">
                    <?= reviewStars(isset($r['rating']) ? (int)$r['rating'] : null) ?>
                </td>
                <td>
                    <span class="rv-status <?= $isHidden ? 'hidden' : 'visible' ?>"
                          id="status-<?= (int)$r['id'] ?>">
                        <i class="fas <?= $isHidden ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                        <?= $isHidden ? __('block') : __('visible') ?>
                    </span>
                </td>
                <td style="white-space:nowrap; color:#64748b; font-size:.82rem;">
                    <?= date('d.m.Y', strtotime((string)$r['created_at'])) ?>
                    <div style="color:#94a3b8; font-size:.75rem;">
                        <?= date('H:i', strtotime((string)$r['created_at'])) ?>
                    </div>
                </td>
                <td>
                    <div class="rv-actions">
                        <!-- Блокувати / Розблокувати -->
                        <button class="rv-btn rv-btn-toggle-<?= $isHidden ? 'off' : 'on' ?>"
                                id="toggle-btn-<?= (int)$r['id'] ?>"
                                onclick="toggleReview(<?= (int)$r['id'] ?>)"
                                title="<?= $isHidden ? 'Опублікувати' : 'Заблокувати' ?>">
                            <i class="fas <?= $isHidden ? 'fa-eye' : 'fa-eye-slash' ?>"
                               id="toggle-icon-<?= (int)$r['id'] ?>"></i>
                        </button>
                        <!-- Видалити -->
                        <button class="rv-btn rv-btn-danger"
                                onclick="deleteReview(<?= (int)$r['id'] ?>)"
                                title="Видалити коментар">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?= $pager->render(['show_info' => true]) ?>
</div>

<div class="rv-toast" id="rv-toast"></div>

<script>
(function () {
    const CSRF = <?= json_encode($csrf) ?>;

    /* ── Toast ── */
    function toast(msg, type = 'info') {
        const el = document.getElementById('rv-toast');
        el.textContent = msg;
        el.className = 'rv-toast show ' + type;
        clearTimeout(el._t);
        el._t = setTimeout(() => { el.className = 'rv-toast'; }, 3200);
    }

    /* ── Expand body ── */
    window.expandBody = function (id) {
        const el  = document.getElementById('body-' + id);
        const btn = document.getElementById('expand-' + id);
        if (!el) return;
        el.classList.toggle('expanded');
        btn.textContent = el.classList.contains('expanded') ? window.LANG.collapse : window.LANG.expand;
    };

    /* ── Checkboxes ── */
    const checkAll = document.getElementById('check-all');
    const bulkBar  = document.getElementById('bulk-bar');
    const bulkCount = document.getElementById('bulk-count');

    function getChecked() {
        return [...document.querySelectorAll('.rv-check:checked')].map(el => +el.value);
    }

    function updateBulkBar() {
        const ids = getChecked();
        if (ids.length > 0) {
            bulkBar.classList.add('visible');
            bulkCount.textContent = ids.length;
        } else {
            bulkBar.classList.remove('visible');
        }
    }

    checkAll.addEventListener('change', function () {
        document.querySelectorAll('.rv-check').forEach(el => { el.checked = this.checked; });
        updateBulkBar();
    });

    document.getElementById('reviews-tbody').addEventListener('change', function (e) {
        if (e.target.classList.contains('rv-check')) {
            updateBulkBar();
            checkAll.checked = [...document.querySelectorAll('.rv-check')].every(el => el.checked);
        }
    });

    /* ── Toggle visibility ── */
    window.toggleReview = async function (id) {
        try {
            const res  = await fetch('/admin/reviews/visibility/' + id, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json; charset=utf-8' },
                body: JSON.stringify({ csrf: CSRF }),
            });
            const data = await res.json();
            if (!data.success) { toast(data.message || window.LANG.error, 'error'); return; }

            const row       = document.getElementById('row-' + id);
            const statusEl  = document.getElementById('status-' + id);
            const toggleBtn = document.getElementById('toggle-btn-' + id);
            const toggleIcon = document.getElementById('toggle-icon-' + id);

            if (data.is_visible) {
                row.classList.remove('rv-hidden');
                statusEl.className = 'rv-status visible';
                statusEl.innerHTML = '<i class="fas fa-eye"></i> Видимий';
                toggleBtn.className = 'rv-btn rv-btn-toggle-on';
                toggleBtn.title = 'Заблокувати';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                row.classList.add('rv-hidden');
                statusEl.className = 'rv-status hidden';
                statusEl.innerHTML = '<i class="fas fa-eye-slash"></i> Блок';
                toggleBtn.className = 'rv-btn rv-btn-toggle-off';
                toggleBtn.title = 'Опублікувати';
                toggleIcon.className = 'fas fa-eye';
            }

            toast(data.message, 'success');
        } catch { toast('Помилка мережі', 'error'); }
    };

    /* ── Delete ── */
    window.deleteReview = async function (id) {
        if (!confirm(window.LANG.confirm_delete_review)) return;
        try {
            const res  = await fetch('/admin/reviews/remove/' + id, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json; charset=utf-8' },
                body: JSON.stringify({ csrf: CSRF }),
            });
            const data = await res.json();
            if (!data.success) { toast(data.message || window.LANG.error, 'error'); return; }

            const row = document.getElementById('row-' + id);
            if (row) {
                row.style.transition = 'opacity .3s';
                row.style.opacity = '0';
                setTimeout(() => row.remove(), 300);
            }
            toast('Коментар видалено', 'success');
        } catch { toast('Помилка мережі', 'error'); }
    };

    /* ── Bulk actions ── */
    window.bulkDo = async function (action) {
        const ids = getChecked();
        if (!ids.length) return;

        const labels = { delete: window.LANG.delete_action, hide: window.LANG.block_action, show: window.LANG.publish_action };
        if (action === 'delete' && !confirm('Видалити ' + ids.length + ' коментар(і) разом з відповідями?')) return;

        try {
            const res  = await fetch('/admin/reviews/bulk', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json; charset=utf-8' },
                body: JSON.stringify({ csrf: CSRF, action, ids }),
            });
            const data = await res.json();
            if (!data.success) { toast(data.message || window.LANG.error, 'error'); return; }

            toast(data.message, 'success');
            setTimeout(() => location.reload(), 700);
        } catch { toast('Помилка мережі', 'error'); }
    };
})();
</script>
