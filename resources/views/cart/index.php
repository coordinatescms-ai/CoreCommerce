<div class="container my-5" style="max-width: 1200px !important; margin: 0 auto !important; font-family: sans-serif !important;">
    <h2 class="mb-4" style="font-weight: 700 !important; color: #333 !important; text-align: left !important;"><?php echo __('cart'); ?></h2>

    <?php if (empty($items)) : ?>
        <div style="text-align: center; padding: 40px; background: #fff; border-radius: 10px; border: 1px solid #ddd;">
            <p><?php echo __('cart_is_empty'); ?></p>
            <a href="/products" class="btn btn-primary"><?php echo __('continue_shopping'); ?></a>
        </div>
    <?php else : ?>
        <div style="display: flex !important; flex-wrap: wrap !important; gap: 30px !important; align-items: flex-start !important;">
            
            <!-- ЛІВА ЧАСТИНА: ТОВАРИ -->
            <div style="flex: 1 1 650px !important;">
                
                <!-- Заголовки (Тільки для десктопа) -->
                <div style="display: flex; padding: 10px 0; border-bottom: 1px solid #eee; margin-bottom: 10px; color: #999; font-size: 12px; text-transform: uppercase; font-weight: bold;">
                    <div style="width: 80px;"></div>
                    <div style="flex: 2; padding-left: 10px;"><?php echo __('product'); ?></div>
                    <div style="width: 100px; text-align: center;"><?php echo __('options'); ?></div>
                    <div style="width: 80px; text-align: center;"><?php echo __('price'); ?></div>
                    <div style="width: 80px; text-align: center;"><?php echo __('quantity'); ?></div>
                    <div style="width: 100px; text-align: right;"><?php echo __('sum'); ?></div>
                </div>

                <?php foreach ($items as $item) : ?>
                    <div style="background: #fff !important; border: 1px solid #e0e0e0 !important; border-radius: 12px !important; padding: 20px !important; margin-bottom: 15px !important; display: flex !important; align-items: center !important; position: relative !important; min-height: 100px !important;">
                        
                        <!-- Кнопка видалення (Червоний хрестик) -->
                        <form action="/cart/remove/<?php echo (int) $item["cart_item_id"]; ?>" method="POST" style="position: absolute !important; top: 10px !important; right: 10px !important; margin: 0 !important;">
                            <input type="hidden" name="_method" value="DELETE">
                            <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
                            <button type="submit" style="background: #fff !important; color: #ff5c5c !important; border: 1px solid #ffeded !important; width: 26px !important; height: 26px !important; border-radius: 5px !important; cursor: pointer !important; font-weight: bold !important; line-height: 1 !important; display: flex !important; align-items: center !important; justify-content: center !important;">✕</button>
                        </form>

                        <!-- Фото -->
                        <div style="width: 80px !important; flex-shrink: 0 !important;">
                            <img src="<?php echo $item["image"]; ?>" style="width: 70px !important; height: 70px !important; object-fit: cover !important; border-radius: 8px !important; background: #f9f9f9 !important; border: 1px solid #eee !important;">
                        </div>

                        <!-- Назва та залишок -->
                        <div style="flex: 2 !important; padding: 0 15px !important; text-align: left !important;">
                            <h6 style="margin: 0 0 5px 0 !important; font-weight: 700 !important; font-size: 15px !important; color: #333 !important; line-height: 1.2 !important;"><?php echo $item["name"]; ?></h6>
                            <small style="color: #28a745 !important; font-size: 11px !important; background: #f0fff4 !important; padding: 2px 6px !important; border-radius: 4px !important; display: inline-block !important;">
                                <?php echo __('in_stock'); ?>: <?php echo $item["stock"]; ?>
                            </small>
                        </div>

                        <!-- Опції -->
                        <div style="width: 100px !important; text-align: center !important; font-size: 12px !important; color: #777 !important; border-left: 1px solid #f0f0f0 !important; border-right: 1px solid #f0f0f0 !important;">
                            <?php if (!empty($item['selected_options'])) : ?>
                                <?php foreach ($item['selected_options'] as $option) : ?>
                                    <div><b><?php echo htmlspecialchars((string) ($option['name'] ?? '')); ?>:</b> <?php echo htmlspecialchars((string) ($option['value'] ?? '')); ?></div>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <span style="color: #ccc;">—</span>
                            <?php endif; ?>
                        </div>

                        <!-- Ціна -->
                        <div style="width: 80px !important; text-align: center !important; font-weight: 500 !important; color: #666 !important; font-size: 14px !important;">
                            <?php echo number_format($item["price"], 2); ?>
                        </div>

                        <!-- Кількість -->
                        <div style="width: 80px !important; text-align: center !important;">
                            <form action="/cart/update" method="POST" style="margin: 0 !important;">
                                <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
                                <input type="hidden" name="cart_item_id" value="<?php echo (int) $item["cart_item_id"]; ?>">
                                <input type="number" name="quantity" value="<?php echo $item["quantity"]; ?>" min="1" max="<?php echo $item["stock"]; ?>" style="width: 50px !important; padding: 4px !important; border: 1px solid #ddd !important; border-radius: 6px !important; text-align: center !important; font-weight: bold !important;" onchange="this.form.submit()">
                            </form>
                        </div>

                        <!-- Сума -->
                        <div style="width: 100px !important; text-align: right !important; font-weight: 800 !important; font-size: 16px !important; color: #000 !important;">
                            <?php echo number_format($item["total_price"], 2); ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div style="display: flex; justify-content: space-between; margin-top: 15px;">
                    <a href="/products" style="text-decoration: none; color: #007bff; font-weight: 600; font-size: 14px;">← <?php echo __('continue_shopping'); ?></a>
                    <form action="/cart/clear" method="POST">
                        <input type="hidden" name="_method" value="DELETE"><input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
                        <button type="submit" style="background: none; border: none; color: #dc3545; cursor: pointer; font-size: 14px;"><?php echo __('clear_cart'); ?></button>
                    </form>
                </div>
            </div>

            <!-- ПРАВА ПАНЕЛЬ (Підсумок) -->
            <div style="flex: 0 0 350px !important;">
                <div style="background: #fff !important; border-radius: 15px !important; padding: 25px !important; border: 1px solid #e0e0e0 !important; box-shadow: 0 5px 15px rgba(0,0,0,0.05) !important;">
                    <h5 style="margin-bottom: 20px !important; font-weight: 700 !important;"><?php echo __('order_summary'); ?></h5>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px; color: #666;">
                        <span><?php echo __('subtotal'); ?></span>
                        <span style="font-weight: 600; color: #333;"><?php echo number_format($total, 2); ?> грн</span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 20px; color: #666;">
                        <span><?php echo __('shipping'); ?></span>
                        <span style="color: #28a745; font-weight: 700;"><?php echo __('free'); ?></span>
                    </div>

                    <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 20px;">

                    <div style="margin-bottom: 25px;">
                        <span style="display: block; font-size: 12px; color: #999; text-transform: uppercase;"><?php echo __('total'); ?></span>
                        <span style="font-size: 28px; font-weight: 800; color: #007bff;"><?php echo number_format($total, 2); ?> <small style="font-size: 14px;">грн</small></span>
                    </div>

                    <a href="/checkout" style="display: block !important; background: #007bff !important; color: #fff !important; text-align: center !important; padding: 15px !important; border-radius: 10px !important; text-decoration: none !important; font-weight: 700 !important; font-size: 16px !important; transition: background 0.2s !important;">
                        <?php echo __('proceed_to_checkout'); ?>
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>





