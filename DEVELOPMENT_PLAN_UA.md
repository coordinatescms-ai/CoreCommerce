# План розвитку CoreCommerce (аналіз + roadmap)

## 0) Короткий аналіз поточного стану

### Що вже є в проєкті
- **Мультимовність (база):** є менеджер локалізації, helper `__()`, мовні файли `lang/ua.php` і `lang/en.php`, роут для перемикання мови `/language/{lang}`. Це вже дає робочий foundation для i18n. 
- **Зміна дизайну (база):** є `ThemeManager`, підтримка активної теми в сесії/куках, теми `default` і `modern`, рендер через layout активної теми.
- **ЧПУ (база):** роути на slug (`/product/{slug}`, `/category/{slug}`), окремий `SlugHelper` з генерацією slug, перевіркою унікальності, історією змін та 301-редиректами.
- **Плагіни (MVP):** `PluginManager` підключає `plugins/*/plugin.php`.
- **Безпека (база):** CSRF-токен у сесії, перевірки в auth/checkout/cart, хешування паролів, reset-token flow, `session_regenerate_id(true)`.
- **Ліцензування:** в репозиторій додано файл `LICENSE` з ліцензією MIT для прозорого використання та внесків.

### Основні прогалини
- Немає повноцінної **системи перекладів контенту** (тільки UI-рядки; не вистачає i18n для товарів/категорій/SEO-метаданих).
- Немає **design-system** і чіткого контракту тем (slots/components/assets), відсутня валідація сумісності тем.
- **Адаптивність** частково присутня, але не стандартизована (немає єдиного mobile-first baseline, test matrix брейкпоінтів).
- **ЧПУ** не централізовані на рівні єдиної URL-стратегії (locale prefixes, canonical policy, sitemap generation).
- **Плагіни** без маніфестів, lifecycle hooks, прав доступу, sandbox/ізоляції та версіонування API.
- Відсутній **механізм автооновлень** ядра/плагінів/тем (канали релізів, rollback, підпис пакетів).
- **Security** потребує посилення: CSP/HSTS, rate limiting, audit logging, SAST/DAST/SCA у CI, централізована policy-архітектура.

---

## 1) Підтримка зміни мови інтерфейсу

### Ціль
Зробити i18n рівня production: швидке перемикання мови, fallback, переклад не лише UI, а й сутностей каталогу.

### План
1. **I18n-архітектура**
   - Ввести стандарт ключів (`section.screen.element`) і fallback chain (`user -> cookie -> accept-language -> default`).
   - Додати pluralization/context (через ICU MessageFormat або сумісну бібліотеку).
2. **Локалізація контенту**
   - Додати таблиці перекладів для продуктів, категорій, CMS-сторінок, SEO-полів.
   - API/адмінка для редагування перекладів.
3. **URL-локалізація**
   - Формат URL з префіксом мови: `/ua/...`, `/en/...`.
   - Хелпери генерації локалізованих посилань + `hreflang`.
4. **Якість і продуктивність**
   - Кеш словників (opcode + app cache), валідація відсутніх ключів у CI.

### KPI
- 100% UI-рядків з ключами перекладу.
- <= 50ms оверхеду на завантаження локалі (p95).
- 0 missing-key у CI-release.

---

## 2) Підтримка зміни дизайну веб-сайту

### Ціль
Перетворити тему з «папки з layout» у керовану платформу тем.

### План
1. **Контракт теми**
   - Стандартизувати `theme.json` (name, version, compat, assets, regions, settings-schema).
   - Ввести перевірку сумісності версій ядра.
2. **Design tokens**
   - Впровадити CSS variables/tokens (кольори, типографіка, spacing, radius).
   - Runtime-перемикання палітри без перезавантаження сторінки (за потреби).
3. **Компонентна структура**
   - Виділити спільні компоненти (header, card, buttons, forms) з override-механізмом у темі.
4. **Адмін-UX**
   - Каталог тем, прев’ю, активація, валідація перед застосуванням.

### KPI
- Час інтеграції нової теми: < 1 день.
- 100% тем проходять contract validation.

---

## 3) Адаптивний дизайн

### Ціль
Mobile-first UI для eCommerce сценаріїв із стабільним UX на всіх екранах.

### План
1. **Responsive baseline**
   - Єдині брейкпоінти (наприклад 360/768/1024/1440).
   - Сітка на CSS Grid/Flex, fluid typography, responsive images (`srcset`, `sizes`).
2. **Критичні user flows**
   - Каталог, картка товару, кошик, checkout, auth — оптимізація під mobile.
3. **Performance + UX**
   - LCP/CLS оптимізація, lazy loading зображень, skeleton states.
4. **Тестування**
   - Візуальні regression-тести на ключових сторінках.

### KPI
- Mobile Lighthouse Performance >= 85.
- CLS < 0.1, LCP < 2.5s (p75).

---

## 4) ЧПУ адреси сторінок (посилань)

### Ціль
Єдина SEO-friendly URL-політика з прозорою міграцією slug.

### План
1. **URL policy**
   - Стандарти шляхів для всіх сутностей (products, categories, pages, brands).
   - Заборонені слова/reserved routes.
2. **Локалізовані slug**
   - Підтримка slug на мову + fallback на translit.
3. **Міграції URL**
   - Автостворення 301 при зміні slug, журнал історії, anti-chain редиректів.
4. **SEO інфраструктура**
   - XML sitemap, canonical, robots policy, 404/410 policy.

### KPI
- 0 битих внутрішніх URL після релізу.
- 100% змінених slug мають коректний 301.

---

## 5) Потужна система плагінів

### Ціль
Розширюваність як у платформ: hook-и, події, безпечне підключення, lifecycle.

### План
1. **Plugin manifest + lifecycle**
   - `plugin.json` (id, version, compat, permissions, entrypoints).
   - lifecycle: install/activate/deactivate/uninstall.
2. **Hooks/Event Bus**
   - Події ядра: `order.created`, `product.updated`, `user.registered` тощо.
3. **DI/API для плагінів**
   - Обмежений service-container, чіткий публічний API, semantic versioning API.
4. **Безпека плагінів**
   - Права доступу (DB, filesystem, external HTTP), підпис пакетів.

### KPI
- Можливість створити плагін без зміни ядра.
- < 1% падінь, пов’язаних з plugin runtime.

---

## 6) Автоматична система оновлень версій

### Ціль
Керовані безпечні оновлення ядра, тем і плагінів з rollback.

### План
1. **Release channels**
   - stable / beta / security-hotfix.
2. **Updater service**
   - Перевірка нових версій, download package, verify signature, pre-checks.
3. **Safe update pipeline**
   - Backup DB + файлів, maintenance mode, міграції, health-check, rollback.
4. **Observability**
   - Логи оновлення, алерти, статуси по інстансах.

### KPI
- Успішність автоматичних оновлень >= 99%.
- Rollback <= 5 хвилин.

---

## 7) Потужна система безпеки від зламів

### Ціль
Defense-in-depth з регулярною верифікацією безпеки.

### План
1. **Application security**
   - Централізовані валідація/санітизація, security headers (CSP, HSTS, X-Frame-Options, Referrer-Policy).
   - Rate limiting + brute-force protection + account lockout policy.
2. **Auth hardening**
   - Опційний MFA, короткоживучі токени reset/remember, secure/httponly/samesite cookies.
3. **Secrets & infra**
   - Секрети тільки з env/vault, ротація ключів, мінімальні привілеї DB-користувача.
4. **Security SDLC**
   - SAST/SCA/DAST у CI, dependency scanning, pentest перед major release.
5. **Audit & incident response**
   - Audit trail адмін-дій, детект аномалій входу, playbook інцидентів.

### KPI
- 0 критичних уразливостей в production.
- MTTR security-інцидентів < 2 год.

---

## Дорожня карта (пріоритезація)

### Фаза 1 (0–6 тижнів): фундамент
- Security headers, rate limiting, hardened cookies.
- Contract для тем + базовий plugin manifest.
- Єдина URL policy + 301 workflow.

### Фаза 2 (6–12 тижнів): масштабування
- Локалізація контенту (товари/категорії/SEO).
- Hooks/Event Bus + plugin lifecycle.
- Mobile-first refactor ключових шаблонів.

### Фаза 3 (12–20 тижнів): platform level
- Auto-update service з підписом пакетів і rollback.
- Повний security SDLC + аудит + моніторинг.
- Theme marketplace-ready UX (прев’ю/валідація/сумісність).

---

## Ризики та контроль
- **Ризик:** зворотна несумісність плагінів/тем.  
  **Контроль:** semver API, compat matrix, deprecation policy.
- **Ризик:** SEO-падіння при міграції URL.  
  **Контроль:** масові 301, canonical, sitemap regeneration, search console monitoring.
- **Ризик:** невдале автооновлення.  
  **Контроль:** staged rollout + health checks + атомарний rollback.

## Висновок
Поточний код вже має правильні зачатки під усі 7 напрямків. Наступний крок — перевести ці зачатки в стандартизовану платформну архітектуру: i18n + theme contract + plugin platform + secure updates + security-by-default.
