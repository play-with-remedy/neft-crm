# Production-интеграция акции «Осеннее дело»

Памятка содержит обязательные файлы, порядок выкладки и проверки после запуска.

## Обязательные файлы

### Логика акции

```text
app/Enums/AutumnCaseStatus.php
app/Models/AutumnCampaign.php
app/Models/AutumnCase.php
app/Models/EveningParticipant.php
app/Models/Player.php
app/Services/AutumnCaseService.php
app/Providers/AppServiceProvider.php
routes/console.php
```

### Интерфейс

```text
app/Filament/Pages/AutumnCampaignDashboard.php
app/Filament/Resources/Players/Pages/PlayerAutumnCase.php
app/Filament/Resources/Players/Pages/ViewPlayer.php
app/Filament/Resources/Players/PlayerResource.php
resources/views/filament/pages/autumn-campaign-dashboard.blade.php
public/images/autumn-leaf.svg
```

### Миграции

```text
database/migrations/2026_08_28_120000_create_autumn_campaigns_table.php
database/migrations/2026_08_28_120100_create_autumn_cases_table.php
database/migrations/2026_08_28_120200_add_autumn_case_fields_to_evening_participants_table.php
database/migrations/2026_08_28_120300_seed_autumn_campaign_2026.php
```

Также изменён файл:

```text
app/Filament/Pages/PlayerFunnel.php
```

В нём исправлена совместимость SQL между MySQL, PostgreSQL и SQLite. Изменение нужно включить в коммит.

## Необязательные для работы production файлы

Эти файлы желательно хранить в Git, но приложение может работать без них:

```text
docs/autumn-case-promotion.md
docs/autumn-case-production-deployment.md
tests/Feature/AutumnCaseServiceTest.php
tests/Feature/FinancialReportsTest.php
```

## Порядок деплоя

Перед началом обязательно создать резервную копию базы данных.

```bash
php artisan down
git pull
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan optimize:clear
php artisan migrate --force
php artisan optimize
php artisan up
```

Если frontend собирается локально и готовая папка `public/build` доставляется на сервер, команды `npm ci` и `npm run build` на production выполнять не нужно.

Миграция автоматически создаёт активную кампанию:

```text
Осеннее дело 2026
01.09.2026–30.11.2026
```

## Расчёт статусов

Laravel Scheduler для акции не требуется. Статусы не хранятся в базе и всегда
вычисляются из дедлайна, даты пятого посещения, даты использования награды и даты
окончания кампании.

## Проверка после выкладки

```bash
php artisan migrate:status
php artisan about
```

Проверить страницы в браузере:

```text
/admin/autumn-campaign
/admin/players/{id}/autumn-case
```

На общей странице должна отображаться кампания с датами 01.09.2026–30.11.2026. До первого осеннего посещения таблица дел будет пустой — это ожидаемо.

## Важные замечания

- Локальный `.env` нельзя переносить на production.
- SQLite нужен только для локальных автоматических тестов и не требуется для работы акции на production.
- Акция не изменяет форму вечера и платёжные данные.
- Администраторы самостоятельно контролируют оформление бесплатного посещения.
- Программа рассчитывает дела и отображает их состояния.
