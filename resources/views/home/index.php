<section style="padding: 2rem 0;">
    <h1 style="margin-bottom: 0.5rem;"><?= __('homepage_hero_title') ?></h1>
    <p style="font-size: 1.1rem; color: #555; margin-bottom: 1.5rem;">
        <?= __('homepage_hero_subtitle') ?>
    </p>

    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1rem;">
        <a href="<?= htmlspecialchars($heroCta['url']) ?>" style="background: #2563eb; color: #fff; text-decoration: none; padding: 0.7rem 1.1rem; border-radius: 0.5rem;">
            <?= htmlspecialchars($heroCta['label']) ?>
        </a>
    </div>
</section>

<section style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap;">
        <h2 style="margin: 0;">Популярні категорії</h2>
        <a href="/products" style="display: inline-block; padding: 0.5rem 0.9rem; border: 1px solid #d1d5db; border-radius: 0.45rem; color: #111827; text-decoration: none;">
            Переглянути всі
        </a>
    </div>

    <?php if (empty($popularCategories)): ?>
        <p>Категорії ще не додано.</p>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem;">
            <?php foreach ($popularCategories as $category): ?>
                <a
                    href="/category/<?= htmlspecialchars($category['slug']) ?>"
                    style="display: block; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem; text-decoration: none; color: #111827; background: #fff;"
                >
                    <strong style="display: block; margin-bottom: 0.35rem;"><?= htmlspecialchars($category['name']) ?></strong>
                    <span style="color: #6b7280; font-size: 0.95rem;">Товарів: <?= (int)($category['products_count'] ?? 0) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section id="new-arrivals" style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap;">
        <h2 style="margin: 0;">Новинки</h2>
        <a href="/products" style="display: inline-block; padding: 0.5rem 0.9rem; border: 1px solid #d1d5db; border-radius: 0.45rem; color: #111827; text-decoration: none;">
            Переглянути всі
        </a>
    </div>

    <?php if (empty($newArrivals)): ?>
        <p>Новинки з'являться найближчим часом.</p>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1rem;">
            <?php foreach ($newArrivals as $product): ?>
                <article style="border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem; background: #fff;">
                    <?php if (!empty($product['image'])): ?>
                        <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" style="width: 100%; height: 180px; object-fit: cover; border-radius: 0.5rem; margin-bottom: 0.75rem;">
                    <?php endif; ?>
                    <h3 style="margin: 0 0 0.5rem;">
                        <a href="/product/<?= htmlspecialchars($product['slug']) ?>" style="text-decoration: none; color: #111827;"><?= htmlspecialchars($product['name']) ?></a>
                    </h3>
                    <p style="margin: 0 0 0.75rem;"><strong><?= htmlspecialchars($product['price']) ?> грн</strong></p>
                    <a href="/cart/add/<?= (int)$product['id'] ?>" style="display: inline-block; padding: 0.5rem 0.85rem; background: #111827; color: #fff; text-decoration: none; border-radius: 0.45rem;">
                        <?= __('add_to_cart') ?>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap;">
        <h2 style="margin: 0;">Рекомендовані товари</h2>
        <a href="/products" style="display: inline-block; padding: 0.5rem 0.9rem; border: 1px solid #d1d5db; border-radius: 0.45rem; color: #111827; text-decoration: none;">
            Переглянути всі
        </a>
    </div>

    <?php if (empty($recommendedProducts)): ?>
        <p>Рекомендовані товари ще формуються.</p>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1rem;">
            <?php foreach ($recommendedProducts as $product): ?>
                <article style="border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem; background: #fff;">
                    <h3 style="margin: 0 0 0.5rem;">
                        <a href="/product/<?= htmlspecialchars($product['slug']) ?>" style="text-decoration: none; color: #111827;"><?= htmlspecialchars($product['name']) ?></a>
                    </h3>
                    <p style="margin: 0 0 0.5rem;"><strong><?= htmlspecialchars($product['price']) ?> грн</strong></p>
                    <?php if (isset($product['orders_count'])): ?>
                        <p style="margin: 0; color: #6b7280; font-size: 0.9rem;">Замовлень: <?= (int)$product['orders_count'] ?></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
