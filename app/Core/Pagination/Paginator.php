<?php

namespace App\Core\Pagination;

use App\Core\Database\DB;

/**
 * Універсальна пагінація для фреймворку.
 *
 * Використання:
 *
 *   $pager = Paginator::fromRequest('products', 20);
 *   $rows  = DB::query("SELECT * FROM products LIMIT {$pager->limit} OFFSET {$pager->offset}")
 *              ->fetchAll(PDO::FETCH_ASSOC);
 *
 *   // або через хелпер з автоматичним COUNT:
 *   [$rows, $pager] = Paginator::paginate(
 *       'SELECT * FROM products WHERE is_visible = 1 ORDER BY id DESC',
 *       [],
 *       'SELECT COUNT(*) FROM products WHERE is_visible = 1',
 *       20
 *   );
 */
class Paginator
{
    public readonly int $page;
    public readonly int $perPage;
    public readonly int $total;
    public readonly int $totalPages;
    public readonly int $offset;
    public readonly int $limit;

    private string $pageParam;

    private function __construct(
        int    $page,
        int    $perPage,
        int    $total,
        string $pageParam = 'page'
    ) {
        $this->perPage    = max(1, $perPage);
        $this->total      = max(0, $total);
        $this->totalPages = max(1, (int) ceil($this->total / $this->perPage));
        $this->page       = min(max(1, $page), $this->totalPages);
        $this->offset     = ($this->page - 1) * $this->perPage;
        $this->limit      = $this->perPage;
        $this->pageParam  = $pageParam;
    }

    // ── Фабричні методи ──────────────────────────────────────────────────────

    /**
     * Створити пагінатор з GET-параметра запиту.
     * Total поки невідомий — встановлюється через setTotal().
     */
    public static function fromRequest(
        string $pageParam = 'page',
        int    $perPage   = 20,
        int    $total     = 0
    ): self {
        $page = max(1, (int)($_GET[$pageParam] ?? 1));
        return new self($page, $perPage, $total, $pageParam);
    }

    /**
     * Зручний хелпер: виконує запит з LIMIT/OFFSET і окремий COUNT.
     *
     * @param  string $sql        Основний запит БЕЗ LIMIT/OFFSET
     * @param  array  $params     Параметри основного запиту
     * @param  string $countSql   COUNT-запит (якщо порожній — генерується автоматично)
     * @param  array  $countParams Параметри COUNT-запиту
     * @param  int    $perPage    Кількість записів на сторінці
     * @param  string $pageParam  Назва GET-параметра
     * @return array{0: array, 1: self}  [рядки, пагінатор]
     */
    public static function paginate(
        string $sql,
        array  $params      = [],
        string $countSql    = '',
        array  $countParams = [],
        int    $perPage     = 20,
        string $pageParam   = 'page'
    ): array {
        // Автогенерація COUNT якщо не передано
        if ($countSql === '') {
            $countSql    = 'SELECT COUNT(*) FROM (' . $sql . ') AS _pag_count';
            $countParams = $params;
        }

        $total = (int) DB::query($countSql, $countParams)->fetchColumn();

        $page   = max(1, (int)($_GET[$pageParam] ?? 1));
        $pager  = new self($page, $perPage, $total, $pageParam);

        $rows = DB::query(
            $sql . ' LIMIT ' . $pager->limit . ' OFFSET ' . $pager->offset,
            $params
        )->fetchAll(\PDO::FETCH_ASSOC);

        return [$rows, $pager];
    }

    /**
     * Встановити загальну кількість записів після окремого COUNT-запиту.
     */
    public function setTotal(int $total): self
    {
        return new self($this->page, $this->perPage, $total, $this->pageParam);
    }

    // ── Стан ─────────────────────────────────────────────────────────────────

    public function hasPages(): bool
    {
        return $this->totalPages > 1;
    }

    public function hasPrev(): bool
    {
        return $this->page > 1;
    }

    public function hasNext(): bool
    {
        return $this->page < $this->totalPages;
    }

    public function from(): int
    {
        return $this->total === 0 ? 0 : ($this->page - 1) * $this->perPage + 1;
    }

    public function to(): int
    {
        return min($this->page * $this->perPage, $this->total);
    }

    // ── URL-генерація ─────────────────────────────────────────────────────────

    /**
     * Побудувати URL для конкретної сторінки зі збереженням усіх поточних GET-параметрів.
     */
    public function url(int $page): string
    {
        $params = array_merge($_GET, [$this->pageParam => $page]);

        // Прибираємо page=1 щоб URL був чистішим
        if ($page === 1) {
            unset($params[$this->pageParam]);
        }

        $params = array_filter($params, fn($v) => $v !== null && $v !== '');
        $query  = $params ? '?' . http_build_query($params) : '';

        return strtok($_SERVER['REQUEST_URI'] ?? '/', '?') . $query;
    }

    public function prevUrl(): ?string
    {
        return $this->hasPrev() ? $this->url($this->page - 1) : null;
    }

    public function nextUrl(): ?string
    {
        return $this->hasNext() ? $this->url($this->page + 1) : null;
    }

    // ── HTML-рендер ──────────────────────────────────────────────────────────

    /**
     * Згенерувати HTML блоку пагінації.
     *
     * @param array $options  ['class' => '...', 'show_info' => true, 'window' => 2]
     */
    public function render(array $options = []): string
    {
        if (!$this->hasPages() && $this->total === 0) {
            return '';
        }

        $showInfo = $options['show_info'] ?? true;
        $window   = max(1, (int)($options['window'] ?? 2));
        $class    = $options['class'] ?? 'pag-wrap';

        $info = '';
        if ($showInfo && $this->total > 0) {
            $info = sprintf(
                '<div class="pag-info">Показано %d–%d з %s</div>',
                $this->from(),
                $this->to(),
                number_format($this->total)
            );
        }

        if (!$this->hasPages()) {
            return $info ? "<div class=\"{$class}\">{$info}</div>" : '';
        }

        $links = '<div class="pag-links">';

        // ← Prev
        $links .= $this->hasPrev()
            ? '<a href="' . $this->url($this->page - 1) . '" class="pag-btn"><i class="fas fa-chevron-left"></i></a>'
            : '<span class="pag-btn pag-disabled"><i class="fas fa-chevron-left"></i></span>';

        // Перша сторінка + крапки
        $start = max(1, $this->page - $window);
        $end   = min($this->totalPages, $this->page + $window);

        if ($start > 1) {
            $links .= '<a href="' . $this->url(1) . '" class="pag-btn">1</a>';
            if ($start > 2) {
                $links .= '<span class="pag-dots">…</span>';
            }
        }

        for ($p = $start; $p <= $end; $p++) {
            $active  = $p === $this->page ? ' active' : '';
            $links  .= '<a href="' . $this->url($p) . '" class="pag-btn' . $active . '">' . $p . '</a>';
        }

        // Остання сторінка + крапки
        if ($end < $this->totalPages) {
            if ($end < $this->totalPages - 1) {
                $links .= '<span class="pag-dots">…</span>';
            }
            $links .= '<a href="' . $this->url($this->totalPages) . '" class="pag-btn">' . $this->totalPages . '</a>';
        }

        // → Next
        $links .= $this->hasNext()
            ? '<a href="' . $this->url($this->page + 1) . '" class="pag-btn"><i class="fas fa-chevron-right"></i></a>'
            : '<span class="pag-btn pag-disabled"><i class="fas fa-chevron-right"></i></span>';

        $links .= '</div>';

        return "<div class=\"{$class}\">{$info}{$links}</div>";
    }
}
