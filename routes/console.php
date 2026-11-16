<?php

use Illuminate\Support\Facades\Schedule;

// Run by the server's crontab every minute (deploy/crontab), with nobody logged in (REQ-DB-16).
// Times are Jordan time (APP_TIMEZONE).

// Database and photos to separate storage every night, encrypted, kept 14 days (REQ-SAF-01 to 03, REQ-SEC-28).
Schedule::command('backup:clean')->dailyAt('01:00');
Schedule::command('backup:run')->dailyAt('01:30')->withoutOverlapping();
// Emails a problem if no backup was made in the last day.
Schedule::command('backup:monitor')->dailyAt('09:00');

// Emails the owner when the disk is 80% full (REQ-CST-10).
Schedule::command('jude:check-disk')->hourly();
