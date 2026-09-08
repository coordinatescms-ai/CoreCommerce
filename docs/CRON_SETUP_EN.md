# Cron Job Setup — CoreCommerce

## Architecture: How It Works

CoreCommerce uses a **two-level Cron system**:
OS (every minute)
└── cron_manager.php ← single entry in OS crontab
├── generate_sitemap.php → schedule: "0 3 * * *" (daily at 03:00)
├── check_updates.php → schedule: "30 4 * * " (daily at 04:30)
└── prom_sync.php → schedule: "/30 * * * *" (every 30 minutes)

text

**The OS runs only one file** — `cron_manager.php` — every minute.
It reads the `cron_tasks` table and runs those tasks whose `next_run <= NOW()`.

**Each task's schedule** (`schedule`) is managed through the admin panel — no server crontab editing required.
After execution, `cron_manager.php` automatically calculates and updates the next `next_run`.

---

## Step 1 — OS Setup (done once)

### Linux / VPS

```bash
crontab -e
Add one line:

cron
* * * * * php /var/www/mysite/cron_manager.php >> /var/www/mysite/storage/logs/cron.log 2>&1
Replace /var/www/mysite/ with the actual path to your project.
Check PHP path: which php

Windows / OSPanel (local)
Task Scheduler (Win + S → "Task Scheduler")

Action → Create Basic Task

Name: CoreCommerce Cron

Trigger: Every minute

Action: Start a program

Program:

text
C:\OSPanel\modules\php\php-8.3\php.exe
Arguments:

text
C:\OSPanel\home\mysite.test\cron_manager.php
Step 2 — Managing Tasks via Admin Panel
Admin Panel → System → Cron Tasks

Here you manage each task's schedule without server access:

Button	Action
✏️	Change name, schedule, file
Enable / Disable	Activate or pause the task
▶️	Run immediately (without waiting for schedule)
🗑	Delete task
+ Add Task	Create a new task
Step 3 — Adding a New Task
Click + Add Task

Fill out the form:

Field	Description	Example
Name	Human-readable name	Generate Sitemap
Schedule	Cron string (5 parts)	0 3 * * *
Script File	Path from project root	tasks/generate_sitemap.php
Parameters	JSON (optional)	{"mode": "full"}
Status	active / disabled	active
Cron String Format (task schedule)
text
┌── minute    (0-59)
│ ┌─ hour     (0-23)
│ │ ┌ day of month (1-31)
│ │ │ ┌ month (1-12)
│ │ │ │ ┌ day of week (0-7, where 0 and 7 = Sunday)
│ │ │ │ │
* * * * *
Schedule	When it runs
* * * * *	Every minute
*/5 * * * *	Every 5 minutes
*/30 * * * *	Every 30 minutes
0 * * * *	Every hour
0 3 * * *	Daily at 03:00
30 4 * * *	Daily at 04:30
0 0 1 * *	1st of every month at 00:00
Existing Tasks in the Project
File	Purpose	Recommended Schedule
tasks/generate_sitemap.php	Generate Sitemap XML	0 3 * * *
tasks/check_updates.php	Check CoreCommerce updates	30 4 * * *
Verification
Via Admin Panel (easiest)
Admin Panel → System → Cron Tasks

Click ▶️ next to the task

Result column: success = OK, failed = error (text shown nearby)

Via Command Line
bash
# Linux
php /var/www/mysite/cron_manager.php

# Windows OSPanel
C:\OSPanel\modules\php\php-8.3\php.exe C:\OSPanel\home\mysite.test\cron_manager.php
View Logs
bash
# Linux
tail -f /var/www/mysite/storage/logs/cron.log

# Windows — open in Notepad++
storage\logs\cron.log
Security
cron_manager.php returns 403 Forbidden when accessed via browser

Task scripts in tasks/ are also protected — only accessible via CLI or cron_manager.php

Admin panel management is protected by admin authentication + CSRF token