<form method="POST" action="/admin/products/store" enctype="multipart/form-data">
    <input name="name" placeholder="Name"><br>
    <input name="slug" placeholder="Slug"><br>
    <input name="price" placeholder="Price"><br>
    <input type="file" name="image"><br>
    <button>Create</button>
    <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
</form>