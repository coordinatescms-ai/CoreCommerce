<!-- 
<section style="padding: 2rem 0;">
    <h1 style="margin-bottom: 0.5rem;"><?= __('homepage_hero_title') ?></h1>
    <p style="font-size: 1.1rem; color: #555; margin-bottom: 1.5rem;">
        <?= __('homepage_hero_subtitle') ?>
    </p>
</section>
-->

<section style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap;">
        <h2 style="margin: 0;">
            <svg style="width: 25px; height: 25px;" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <!-- Популярні категорії (Тренд / Вогонь) -->
                <path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0z" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M15 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <?= __('popular_categories') ?>
        </h2>
    </div>

    <?php if (empty($popularCategories)): ?>
        <p><?= __('categories_not_found') ?></p>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem;">
            <?php foreach ($popularCategories as $category): ?>
                <a
                    href="/category/<?= htmlspecialchars(ltrim($category['path'] ?? $category['slug'], '/')) ?>"
                    style="display: block; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem; text-decoration: none; color: #111827; background: #fff;"
                >
                    <strong style="display: block; margin-bottom: 0.35rem;"><?= htmlspecialchars($category['name']) ?></strong>
                    <span style="color: #6b7280; font-size: 0.95rem;"><?= __('products_count') ?> <?= (int)($category['products_count'] ?? 0) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section id="new-arrivals" style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap;">
        <h2 style="margin: 0;">
            <svg style="width: 25px; height: 25px;" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <!-- Новинки (Ярлик / Бірка) -->
                <path d="M21 3H13.414A2 2 0 0 0 12 3.586L3.586 12a2 2 0 0 0 0 2.828l5.586 5.586a2 2 0 0 0 2.828 0L20.414 12A2 2 0 0 0 21 10.586V3Z" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M16.5 7.5H16.51" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <?= __('new_arrivals') ?>
        </h2>
    </div>

    <?php if (empty($newArrivals)): ?>
        <p><?= __('new_arrivals_empty') ?></p>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1rem;">
            <?php foreach ($newArrivals as $product): ?>
                <article style="border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem; background: #fff;">
                    <?php if (!empty($product['image'])): ?>
                        <img src="<?= htmlspecialchars(product_image_variant_path((string) $product['image'], 'medium')) ?>" alt="<?= htmlspecialchars($product['name']) ?>" style="width: 100%; height: 180px; object-fit: cover; border-radius: 0.5rem; margin-bottom: 0.75rem;">
                    <?php endif; ?>
                    <h3 style="margin: 0 0 0.5rem;">
                        <a href="/product/<?= htmlspecialchars($product['slug']) ?>" style="text-decoration: none; color: #111827;"><?= htmlspecialchars($product['name']) ?></a>
                    </h3>
                    <p style="margin: 0 0 0.75rem;"><strong><?= format_price($product['price']) ?></strong></p>
                    <form action="/cart/add/<?= (int)$product['id'] ?>" method="POST" style="display: inline-block; margin: 0;">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
                        <input type="hidden" name="return_url" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/products') ?>">
                        <button type="submit" style="display: inline-block; padding: 0.5rem 0.85rem; background: #111827; color: #fff; text-decoration: none; border-radius: 0.45rem; border: 0; cursor: pointer;">
                            <?= __('add_to_cart') ?>
                        </button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap;">
        <h2 style="margin: 0;">
            <svg style="width: 25px; height: 25px;" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <!-- Рекомендовані товари (Лайк / Схвалення) -->
                <path d="M7 11V21M7 11H4C2.89543 11 2 11.8954 2 13V19C2 20.1046 2.89543 21 4 21H7M7 11L11.45 4.325C11.794 3.809 12.394 3.5 13.033 3.5H14.5C15.328 3.5 16 4.172 16 5V9.5H20.21C21.19 9.5 21.94 10.37 21.82 11.35L20.62 20.35C20.51 21.28 19.72 22 18.78 22H7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <?= __('recommended_products') ?>
        </h2>
        <a href="/products" style="display: inline-block; padding: 0.5rem 0.9rem; border: 1px solid #d1d5db; border-radius: 0.45rem; color: #111827; text-decoration: none;">
            <?= __('view_all') ?>
        </a>
    </div>

    <?php if (empty($recommendedProducts)): ?>
        <p><?= __('recommended_empty') ?></p>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1rem;">
            <?php foreach ($recommendedProducts as $product): ?>
                <article style="border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem; background: #fff;">
                    <h3 style="margin: 0 0 0.5rem;">
                        <a href="/product/<?= htmlspecialchars($product['slug']) ?>" style="text-decoration: none; color: #111827;"><?= htmlspecialchars($product['name']) ?></a>
                    </h3>
                    <p style="margin: 0 0 0.5rem;"><strong><?= format_price($product['price']) ?></strong></p>
                    <?php if (isset($product['orders_count'])): ?>
                        <p style="margin: 0; color: #6b7280; font-size: 0.9rem;"><?= __('orders_count') ?> <?= (int)$product['orders_count'] ?></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
