<h1>Admin Products</h1>

<a href="/admin/products/create">+ Add</a>

<?php foreach ($products as $p): ?>
<div style="border:1px solid #ccc; margin:10px; padding:10px;">
    <b><?= $p['name'] ?></b> — <?= $p['price'] ?>
    
    <?php if ($p['image']): ?>
        <br><img src="<?= $p['image'] ?>" width="100">
    <?php endif; ?>

    <br>
    <a href="/admin/products/delete/<?= $p['id'] ?>">Delete</a>
</div>
<?php endforeach; ?>