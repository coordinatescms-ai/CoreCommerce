<!-- container -->
<div class="container my-5">
    <h1 class="mb-4"><?php echo __('cart'); ?></h1>

    <?php if (isset($_SESSION["success"])) : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION["success"];
            unset($_SESSION["success"]); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION["error"])) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION["error"];
            unset($_SESSION["error"]); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($items)) : ?>
        <div class="text-center py-5">
            <p class="lead text-muted"><?php echo __('cart_is_empty'); ?></p>
            <a href="/products" class="btn btn-primary"><?php echo __('continue_shopping'); ?></a>
        </div>
    <?php else : ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?php echo __('product'); ?></th>
                                    <th><?php echo __('price'); ?></th>
                                    <th><?php echo __('quantity'); ?></th>
                                    <th><?php echo __('sum'); ?></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item) : ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($item["image"]) : ?>
                                                    <img src="/uploads/products/<?php echo $item["image"]; ?>" alt="<?php echo $item["name"]; ?>" class="img-thumbnail me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                                <?php else : ?>
                                                    <div class="bg-light d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                                                        <i class="bi bi-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <h6 class="mb-0"><?php echo $item["name"]; ?></h6>
                                                    <small class="text-muted"><?php echo __('in_stock'); ?>: <?php echo $item["stock"]; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo number_format($item["price"], 2); ?> грн</td>
                                        <td>
                                            <form action="/cart/update" method="POST" class="d-flex align-items-center" style="max-width: 120px;">
                                                <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
                                                <input type="hidden" name="product_id" value="<?php echo $item["product_id"]; ?>">
                                                <input type="number" name="quantity" value="<?php echo $item["quantity"]; ?>" min="1" max="<?php echo $item["stock"]; ?>" class="form-control form-control-sm me-2" onchange="this.form.submit()">
                                            </form>
                                        </td>
                                        <td class="fw-bold"><?php echo number_format($item["total_price"], 2); ?> грн</td>
                                        <td class="text-end">
                                            <form action="/cart/remove/<?php echo $item["product_id"]; ?>" method="POST" class="d-inline">
                                                <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="<?php echo __('remove'); ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="/products" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i><?php echo __('continue_shopping'); ?>
                    </a>
                    <form action="/cart/clear" method="POST">
                        <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="bi bi-x-circle me-2"></i><?php echo __('clear_cart'); ?>
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="card-title mb-4"><?php echo __('order_summary'); ?></h5>
                        <div class="d-flex justify-content-between mb-3">
                            <span><?php echo __('subtotal'); ?></span>
                            <span><?php echo number_format($total, 2); ?> грн</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span><?php echo __('shipping'); ?></span>
                            <span class="text-success"><?php echo __('free'); ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <span class="h5 mb-0"><?php echo __('total'); ?></span>
                            <span class="h5 mb-0 text-primary"><?php echo number_format($total, 2); ?> грн</span>
                        </div>
                        <a href="/checkout" class="btn btn-primary btn-lg w-100 py-3">
                            <?php echo __('proceed_to_checkout'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
