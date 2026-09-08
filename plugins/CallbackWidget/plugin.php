<?php

use App\Core\Plugin\PluginInterface;
use App\Core\Plugin\PluginManager;

return new class implements PluginInterface {

    public function getName(): string
    {
        return 'CallbackWidget';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function register(PluginManager $pluginManager): void
    {
        // Додаємо віджет на всі сторінки сайту
        $pluginManager->addAction('theme.footer', [$this, 'renderWidget']);
    }

    public function renderWidget(): void
    {
        // Визначаємо поточну мову (за замовчуванням 'uk')
        $lang = $_SESSION['lang'] ?? 'uk';
        if (!in_array($lang, ['uk', 'en'])) {
            $lang = 'uk';
        }

        // Шлях до мовного файлу
        $langFile = __DIR__ . '/lang/' . $lang . '.json';
        $translations = [];
        
        if (file_exists($langFile)) {
            $translations = json_decode(file_get_contents($langFile), true) ?? [];
        }

        // Якщо переклади не завантажилися, використовуємо українські за замовчуванням
        if (empty($translations)) {
            $defaultLangFile = __DIR__ . '/lang/uk.json';
            if (file_exists($defaultLangFile)) {
                $translations = json_decode(file_get_contents($defaultLangFile), true) ?? [];
            }
        }

        // Виводимо віджет
        echo $this->getWidgetHTML($translations, $lang);
    }

    private function getWidgetHTML(array $translations, string $lang): string
    {
        $t = function($key) use ($translations) {
            return $translations[$key] ?? $key;
        };

        $pluginUrl = '/plugins/CallbackWidget';

        ob_start();
        ?>
        <!-- Callback Widget -->
        <div id="callback-widget">
            <!-- Кнопка -->
            <button id="callback-btn" class="callback-btn" title="<?php echo htmlspecialchars($t('button_title')); ?>">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                </svg>
            </button>

            <!-- Модальне вікно -->
            <div id="callback-modal" class="callback-modal">
                <div class="callback-modal-content">
                    <button class="callback-modal-close">&times;</button>
                    <h2 class="callback-modal-title"><?php echo htmlspecialchars($t('modal_title')); ?></h2>
                    <form id="callback-form" class="callback-form">
                        <input type="hidden" name="lang" value="<?php echo htmlspecialchars($lang); ?>">
                        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(\App\Core\Http\Csrf::token()); ?>">
                        
                        <div class="callback-form-group">
                            <input type="text" name="name" id="callback-name" class="callback-input" placeholder="<?php echo htmlspecialchars($t('placeholder_name')); ?>" required>
                        </div>
                        
                        <div class="callback-form-group">
                            <input type="tel" name="phone" id="callback-phone" class="callback-input" placeholder="<?php echo htmlspecialchars($t('placeholder_phone')); ?>" required>
                        </div>
                        
                        <button type="submit" class="callback-submit"><?php echo htmlspecialchars($t('submit_button')); ?></button>
                        
                        <div id="callback-status" class="callback-status"></div>
                    </form>
                </div>
            </div>
        </div>

        <!-- CSS -->
        <style>
            .callback-btn {
                position: fixed;
                top: 180px;
                right: 20px;
                width: 60px;
                height: 60px;
                border-radius: 50%;
                background: rgba(59, 130, 246, 0.8);
                color: white;
                border: none;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 999;
                transition: all 0.3s ease;
                box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
            }

            .callback-btn:hover {
                background: rgba(59, 130, 246, 1);
                transform: scale(1.1);
                box-shadow: 0 6px 16px rgba(59, 130, 246, 0.6);
            }

            .callback-modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(4px);
                z-index: 1000;
                align-items: center;
                justify-content: center;
            }

            .callback-modal.active {
                display: flex;
            }

            .callback-modal-content {
                background: white;
                border-radius: 12px;
                padding: 30px;
                max-width: 400px;
                width: 90%;
                position: relative;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            }

            .callback-modal-close {
                position: absolute;
                top: 15px;
                right: 15px;
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #64748b;
                transition: color 0.2s;
            }

            .callback-modal-close:hover {
                color: #0f172a;
            }

            .callback-modal-title {
                margin: 0 0 20px 0;
                font-size: 24px;
                color: #0f172a;
            }

            .callback-form-group {
                margin-bottom: 15px;
            }

            .callback-input {
                width: 100%;
                padding: 12px 16px;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                font-size: 16px;
                transition: border-color 0.2s;
                box-sizing: border-box;
            }

            .callback-input:focus {
                outline: none;
                border-color: #3b82f6;
            }

            .callback-submit {
                width: 100%;
                padding: 12px;
                background: #3b82f6;
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: background 0.2s;
            }

            .callback-submit:hover {
                background: #2563eb;
            }

            .callback-submit:disabled {
                background: #94a3b8;
                cursor: not-allowed;
            }

            .callback-status {
                margin-top: 15px;
                padding: 12px;
                border-radius: 8px;
                font-size: 14px;
                display: none;
            }

            .callback-status.success {
                display: block;
                background: #dcfce7;
                color: #166534;
            }

            .callback-status.error {
                display: block;
                background: #fee2e2;
                color: #991b1b;
            }

            @media (max-width: 1000px) {
                .callback-btn {
                    top: 250px;
                }
            }

            @media (max-width: 768px) {
                .callback-btn {
                    top: 280px;
                }
            }

            @media (max-width: 480px) {
                .callback-btn {
                    width: 50px;
                    height: 50px;
                    right: 15px;
                    top: 280px;
                }

                .callback-modal-content {
                    padding: 20px;
                }
            }
        </style>

        <!-- JavaScript -->
        <script>
            (function() {
                const btn = document.getElementById('callback-btn');
                const modal = document.getElementById('callback-modal');
                const closeBtn = document.querySelector('.callback-modal-close');
                const form = document.getElementById('callback-form');
                const status = document.getElementById('callback-status');
                const submitBtn = form.querySelector('.callback-submit');

                // Відкриття модального вікна
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    modal.classList.add('active');
                });

                // Закриття модального вікна
                function closeModal() {
                    modal.classList.remove('active');
                    status.className = 'callback-status';
                    status.style.display = 'none';
                    form.reset();
                }

                closeBtn.addEventListener('click', closeModal);

                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeModal();
                    }
                });

                // Відправка форми
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(form);
                    const data = Object.fromEntries(formData.entries());
                    
                    // Валідація на клієнті
                    if (!data.name.trim() || !data.phone.trim()) {
                        showStatus('error', 'error');
                        return;
                    }

                    submitBtn.disabled = true;
                    submitBtn.textContent = '...';

                    try {
                        const response = await fetch('<?php echo $pluginUrl; ?>/callback.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(data)
                        });

                        const result = await response.json();

                        if (result.success) {
                            showStatus(result.message, 'success');
                            form.reset();
                            setTimeout(closeModal, 2000);
                        } else {
                            showStatus(result.message || 'error', 'error');
                        }
                    } catch (error) {
                        showStatus('error', 'error');
                    } finally {
                        submitBtn.disabled = false;
                        submitBtn.textContent = '<?php echo htmlspecialchars($t('submit_button')); ?>';
                    }
                });

                function showStatus(message, type) {
                    status.textContent = message;
                    status.className = 'callback-status ' + type;
                    status.style.display = 'block';
                }
            })();
        </script>
        <!-- End Callback Widget -->
        <?php
        return ob_get_clean();
    }

    public function getSettingsSchema(): array
    {
        return [];
    }
};
