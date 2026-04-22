<form action="/admin/settings/save" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
    <!-- Це поле вкаже контролеру куди повернутися -->
    <input type="hidden" name="current_tab" value="media">

    <div class="card">
        <div class="card-header">
            <i class="fas fa-images"></i> Налаштування мультимедіа
        </div>
        <div class="card-body">
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
                <div class="form-group">
                    <label for="media_thumb_width">Thumbnail Width (px)</label>
                    <input type="number" min="0" name="settings[media_thumb_width]" id="media_thumb_width" class="form-control" value="<?php echo htmlspecialchars(get_setting('media_thumb_width', '200')); ?>">
                </div>
                <div class="form-group">
                    <label for="media_thumb_height">Thumbnail Height (px)</label>
                    <input type="number" min="0" name="settings[media_thumb_height]" id="media_thumb_height" class="form-control" value="<?php echo htmlspecialchars(get_setting('media_thumb_height', '200')); ?>">
                </div>
                <div class="form-group">
                    <label for="media_medium_width">Medium Width (px)</label>
                    <input type="number" min="0" name="settings[media_medium_width]" id="media_medium_width" class="form-control" value="<?php echo htmlspecialchars(get_setting('media_medium_width', '800')); ?>">
                </div>
                <div class="form-group">
                    <label for="media_medium_height">Medium Height (px)</label>
                    <input type="number" min="0" name="settings[media_medium_height]" id="media_medium_height" class="form-control" value="<?php echo htmlspecialchars(get_setting('media_medium_height', '800')); ?>">
                </div>
                <div class="form-group">
                    <label for="media_large_width">Large/Zoom Width (px)</label>
                    <input type="number" min="0" name="settings[media_large_width]" id="media_large_width" class="form-control" value="<?php echo htmlspecialchars(get_setting('media_large_width', '1600')); ?>">
                </div>
                <div class="form-group">
                    <label for="media_large_height">Large/Zoom Height (px)</label>
                    <input type="number" min="0" name="settings[media_large_height]" id="media_large_height" class="form-control" value="<?php echo htmlspecialchars(get_setting('media_large_height', '1600')); ?>">
                </div>
            </div>

            <div class="form-group" style="margin-top:1rem;">
                <label for="media_quality">Якість стиснення WebP/JPEG: <strong id="quality-value"><?php echo (int) get_setting('media_quality', '82'); ?></strong>%</label>
                <input type="range" min="10" max="100" step="1" name="settings[media_quality]" id="media_quality" class="form-control" value="<?php echo (int) get_setting('media_quality', '82'); ?>">
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="settings[media_auto_webp]" value="1" <?php echo get_setting('media_auto_webp', '1') === '1' ? 'checked' : ''; ?>>
                    Автоматична конвертація в WebP
                </label>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-stamp"></i> Водяний знак (Watermark)
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="watermark_file">Логотип водяного знака (PNG з прозорістю)</label>
                <input type="file" name="watermark_file" id="watermark_file" class="form-control" accept=".png,image/png">
                <?php if (get_setting('media_watermark_path')): ?>
                    <div style="margin-top:0.5rem;">
                        <img src="<?php echo htmlspecialchars(get_setting('media_watermark_path')); ?>" alt="Watermark" style="max-height:48px; max-width:180px; border:1px solid #e5e7eb; padding:4px; border-radius:4px;">
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="media_watermark_position">Позиція водяного знака</label>
                <select name="settings[media_watermark_position]" id="media_watermark_position" class="form-control">
                    <?php $position = get_setting('media_watermark_position', 'bottom-right'); ?>
                    <option value="top-left" <?php echo $position === 'top-left' ? 'selected' : ''; ?>>Top-Left</option>
                    <option value="top-right" <?php echo $position === 'top-right' ? 'selected' : ''; ?>>Top-Right</option>
                    <option value="center" <?php echo $position === 'center' ? 'selected' : ''; ?>>Center</option>
                    <option value="bottom-left" <?php echo $position === 'bottom-left' ? 'selected' : ''; ?>>Bottom-Left</option>
                    <option value="bottom-right" <?php echo $position === 'bottom-right' ? 'selected' : ''; ?>>Bottom-Right</option>
                </select>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="settings[media_apply_watermark]" value="1" <?php echo get_setting('media_apply_watermark', '0') === '1' ? 'checked' : ''; ?>>
                    Накладати водяний знак при завантаженні нових фото
                </label>
            </div>
        </div>
    </div>

    <div style="margin-bottom: 2rem; display: flex; justify-content: flex-end;">
        <button type="submit" class="btn btn-primary btn-lg" style="padding: 0.75rem 2rem; font-size: 1rem;">
            <i class="fas fa-save"></i> Зберегти мультимедіа
        </button>
    </div>
</form>

<script>
(function () {
    const qualityInput = document.getElementById('media_quality');
    const qualityValue = document.getElementById('quality-value');
    if (!qualityInput || !qualityValue) {
        return;
    }

    qualityInput.addEventListener('input', function () {
        qualityValue.textContent = qualityInput.value;
    });
})();
</script>
