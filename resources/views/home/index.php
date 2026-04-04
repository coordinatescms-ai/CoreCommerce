<section style="padding: 2rem 0;">
    <h1 style="margin-bottom: 0.5rem;"><?= __('welcome') ?></h1>
    <p style="font-size: 1.1rem; color: #555; margin-bottom: 1.5rem;">
        Обирайте найкращі товари для дому, роботи та відпочинку в одному каталозі.
    </p>

    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 2rem;">
        <a href="/products" style="background: #2563eb; color: #fff; text-decoration: none; padding: 0.7rem 1.1rem; border-radius: 0.5rem;">
            Перейти до каталогу
        </a>
        <a href="#new-arrivals" style="background: #f3f4f6; color: #111827; text-decoration: none; padding: 0.7rem 1.1rem; border-radius: 0.5rem;">
            Дивитись новинки
        </a>
    </div>
</section>

<section style="margin-bottom: 2rem;">
    <h2 style="margin-bottom: 1rem;">Топ-категорії</h2>
    <?php if (empty($topCategories)): ?>
        <p>Категорії ще не додано.</p>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem;">
            <?php foreach ($topCategories as $category): ?>
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
    <h2 style="margin-bottom: 1rem;">Новинки</h2>
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
    <h2 style="margin-bottom: 1rem;">Популярні товари</h2>
    <?php if (empty($popularProducts)): ?>
        <p>Популярні товари ще формуються.</p>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1rem;">
            <?php foreach ($popularProducts as $product): ?>
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
