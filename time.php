<?php
// /ai-timer-api/time/<random>
// Plain-text authoritative server time (UTC offset included).

date_default_timezone_set('America/New_York'); // change if you want
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$now = new DateTimeImmutable('now');
echo $now->format('Y-m-d\TH:i:sP') . "\n";     // ISO 8601, e.g. 2026-02-07T07:46:22-05:00
