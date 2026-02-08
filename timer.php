<?php
// /ai-timer-api/timer/<minutes>
// PoC: returns a page that schedules a local Chrome notification + sound after N minutes.

date_default_timezone_set('America/New_York');
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$m = isset($_GET['m']) ? (int)$_GET['m'] : 0;
if ($m < 1) $m = 15;
if ($m > 180) $m = 180; // hard cap (PoC)

$now = new DateTimeImmutable('now');
$end = $now->add(new DateInterval('PT' . ($m * 60) . 'S'));
$nowIso = $now->format('Y-m-d\TH:i:sP');
$endIso = $end->format('Y-m-d\TH:i:sP');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Timer <?= htmlspecialchars((string)$m) ?>m</title>
  <style>
    body { font-family: system-ui, sans-serif; margin: 24px; line-height: 1.35; }
    code { background: #f2f2f2; padding: 2px 6px; border-radius: 6px; }
    .row { margin: 10px 0; }
    button { padding: 10px 14px; cursor: pointer; }
  </style>
</head>
<body>
  <h1>Timer: <?= htmlspecialchars((string)$m) ?> minutes</h1>
  <div class="row">Start: <code id="start"><?= htmlspecialchars($nowIso) ?></code></div>
  <div class="row">End: <code id="end"><?= htmlspecialchars($endIso) ?></code></div>
  <div class="row">Remaining: <code id="remain">...</code></div>

  <div class="row">
    <button id="perm">Enable notifications</button>
    <button id="startBtn">Start timer</button>
  </div>

  <div class="row" id="status"></div>

<script>
(() => {
  const minutes = <?= (int)$m ?>;
  const startIso = <?= json_encode($nowIso) ?>;
  const endIso   = <?= json_encode($endIso) ?>;

  const $remain = document.getElementById('remain');
  const $status = document.getElementById('status');
  const $perm   = document.getElementById('perm');
  const $start  = document.getElementById('startBtn');

  function setStatus(s) { $status.textContent = s; }

  function playBeep() {
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const o = ctx.createOscillator();
      const g = ctx.createGain();
      o.type = "sine";
      o.frequency.value = 880;
      g.gain.value = 0.08;
      o.connect(g); g.connect(ctx.destination);
      o.start();
      setTimeout(() => { o.stop(); ctx.close(); }, 500);
    } catch (_) {}
  }

  function notifyDone() {
    playBeep();
    document.title = "⏰ Timer done";
    if ("Notification" in window && Notification.permission === "granted") {
      new Notification(`Timer done (${minutes}m)`, {
        body: `Started: ${startIso}\nEnded: ${endIso}`,
        requireInteraction: true
      });
    } else {
      alert(`Timer done (${minutes}m)`);
    }
  }

  function tick(endMs) {
    const now = Date.now();
    const left = Math.max(0, endMs - now);
    const s = Math.floor(left / 1000);
    const mm = Math.floor(s / 60);
    const ss = s % 60;
    $remain.textContent = `${mm}:${String(ss).padStart(2, "0")}`;
    if (left <= 0) return;
    requestAnimationFrame(() => tick(endMs));
  }

  async function ensurePermission() {
    if (!("Notification" in window)) {
      setStatus("No Notification API (use Chrome/Chromium).");
      return false;
    }
    if (Notification.permission === "granted") return true;
    const p = await Notification.requestPermission();
    setStatus(`Notification permission: ${p}`);
    return p === "granted";
  }

  $perm.addEventListener("click", () => { ensurePermission(); });

  $start.addEventListener("click", async () => {
    await ensurePermission(); // best effort
    const endMs = Date.now() + minutes * 60 * 1000;
    setStatus(`Running: ${minutes}m`);
    tick(endMs);
    setTimeout(notifyDone, minutes * 60 * 1000);
  });

  // show countdown even before start (based on server end time) — informational only
  const endMsInfo = Date.parse(endIso);
  if (!Number.isNaN(endMsInfo)) {
    tick(endMsInfo);
  } else {
    $remain.textContent = "n/a";
  }
})();
</script>
</body>
</html>
