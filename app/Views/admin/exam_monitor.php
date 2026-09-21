<?php
$data = $monitorData;
$exam = $data['exam'];
$success = $success ?? null;
$error = $error ?? null;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Live Monitor - <?= esc($exam['title']) ?></title>
  <style>
    :root{--ink:#182033;--muted:#778196;--line:#e4e8ef;--panel:#fff;--page:#f5f7fa;--blue:#168fd4;--cyan:#21bde1;--green:#1e9b69;--amber:#b26b05;--red:#b8324b;--navy:#0b1f3a}
    *{box-sizing:border-box}html,body{margin:0;min-height:100%;background:var(--page);color:var(--ink);font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif}button,input,select{font:inherit}button{cursor:pointer}body{overflow-x:hidden}
    .monitor-shell{min-height:100vh;display:flex;flex-direction:column}.monitor-header{background:#fff;border-bottom:1px solid var(--line);padding:16px 24px 14px;display:flex;align-items:center;gap:18px;flex-wrap:wrap}.monitor-back{color:var(--blue);font-size:13px;font-weight:700;text-decoration:none;white-space:nowrap}.monitor-title-block{min-width:230px;flex:1}.monitor-title{font-size:20px;line-height:1.2;font-weight:800;letter-spacing:-.03em}.monitor-subtitle{font-size:12px;color:var(--muted);margin-top:5px}.monitor-header-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}.live-indicator{display:flex;align-items:center;gap:7px;color:var(--green);font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.06em}.live-dot{width:9px;height:9px;background:var(--green);border-radius:50%;box-shadow:0 0 0 4px #e2f5eb}.monitor-clock{font-variant-numeric:tabular-nums;color:var(--muted);font-size:12px;white-space:nowrap}.monitor-button{border:1px solid #ccd5e2;background:#fff;color:#30415a;border-radius:8px;padding:9px 12px;font-size:12px;font-weight:750}.monitor-button.primary{border-color:var(--blue);background:var(--blue);color:#fff}.monitor-content{width:100%;padding:18px 24px 0}.monitor-summary{display:grid;grid-template-columns:repeat(6,minmax(105px,1fr));gap:10px}.summary-card{background:var(--panel);border:1px solid var(--line);padding:13px 14px;border-radius:10px}.summary-label{font-size:10px;text-transform:uppercase;letter-spacing:.06em;color:var(--muted)}.summary-value{font-size:23px;font-weight:800;color:var(--navy);margin-top:4px;font-variant-numeric:tabular-nums}.summary-card.active .summary-value{color:var(--blue)}.summary-card.submitted .summary-value{color:var(--green)}.summary-card.offline .summary-value,.summary-card.attention .summary-value{color:var(--red)}
    .monitor-toolbar{display:flex;gap:9px;align-items:center;flex-wrap:wrap;margin:16px 0 14px}.monitor-search{height:38px;min-width:240px;flex:1;border:1px solid #ccd5e2;border-radius:8px;padding:0 12px;background:#fff;color:var(--ink);outline:none}.monitor-search:focus{border-color:var(--blue);box-shadow:0 0 0 3px #d9f2fb}.filter-group{display:flex;gap:4px;padding:3px;background:#e9edf3;border-radius:9px}.filter-button{border:0;background:transparent;border-radius:7px;padding:8px 10px;color:#5e6c82;font-size:12px;font-weight:700}.filter-button.active{background:#fff;color:var(--navy);box-shadow:0 1px 3px #cfd6e0}.monitor-select{height:38px;border:1px solid #ccd5e2;border-radius:8px;background:#fff;padding:0 10px;color:#41516a;font-size:12px}.attention-button{color:var(--red);border-color:#edc5ce}.attention-button.active{background:#fff0f3}.force-submit-button{color:#fff;background:var(--red);border-color:var(--red)}.force-submit-button:disabled{opacity:.45;cursor:not-allowed}.toolbar-spacer{flex:1}.refresh-state{font-size:11px;color:var(--muted);white-space:nowrap}.monitor-notice{margin:0 0 14px;padding:12px 14px;border-radius:8px;font-size:13px}.monitor-notice.success{background:#e8f7ef;color:#176a45}.monitor-notice.error{background:#fff0f0;color:#a32121}
    .monitor-panel{background:#fff;border:1px solid var(--line);border-radius:12px;overflow:hidden}.grid-head{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:13px 16px;border-bottom:1px solid var(--line)}.grid-title{font-size:13px;font-weight:800;color:var(--navy)}.grid-count{font-size:12px;color:var(--muted)}.applicant-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(185px,1fr));gap:8px;padding:12px}.applicant-grid.dense{grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:6px;padding:9px}.applicant-tile{min-width:0;text-align:left;border:1px solid #e1e6ee;background:#fff;border-radius:9px;padding:11px 11px 10px;color:var(--ink);transition:border-color .15s,box-shadow .15s,transform .15s}.applicant-tile:hover{border-color:var(--blue);box-shadow:0 3px 12px #cfdae6;transform:translateY(-1px)}.applicant-tile.active{border-left:3px solid var(--blue)}.applicant-tile.submitted{border-left:3px solid var(--green)}.applicant-tile.offline,.applicant-tile.expired{border-left:3px solid var(--red)}.applicant-tile.attention{background:#fffaf9}.tile-top{display:flex;align-items:center;justify-content:space-between;gap:7px}.tile-status{display:flex;align-items:center;gap:5px;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.04em}.status-dot{width:7px;height:7px;border-radius:50%;background:#aeb7c5}.status-dot.active{background:var(--blue)}.status-dot.submitted{background:var(--green)}.status-dot.offline,.status-dot.expired{background:var(--red)}.status-dot.not_started{background:#b9a05e}.tile-alert{font-size:11px;color:var(--red);font-weight:800}.tile-id{font-size:16px;font-weight:850;color:var(--navy);letter-spacing:.01em;margin-top:9px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.tile-name{font-size:12px;color:#45536a;margin-top:3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.tile-tasks{display:flex;gap:5px;flex-wrap:wrap;margin-top:10px;min-height:19px}.task-chip{padding:3px 5px;border-radius:4px;background:#f0f5fa;color:#50617a;font-size:10px;white-space:nowrap}.task-chip.complete{background:#e8f7ef;color:var(--green)}.task-chip.working{background:#e6f6fb;color:var(--blue)}.tile-bottom{display:flex;justify-content:space-between;gap:6px;margin-top:11px;padding-top:8px;border-top:1px solid #eef1f5;color:var(--muted);font-size:10px;font-variant-numeric:tabular-nums}.tile-time{font-weight:800;color:var(--navy)}.empty-grid{padding:45px 20px;text-align:center;color:var(--muted);font-size:13px}
    .event-strip{margin:14px 0 18px;background:#fff;border:1px solid var(--line);border-radius:12px;overflow:hidden}.event-head{display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-bottom:1px solid var(--line)}.event-head strong{font-size:13px;color:var(--navy)}.event-head span{font-size:11px;color:var(--muted)}.event-list{display:grid;grid-template-columns:repeat(auto-fit,minmax(290px,1fr))}.event-item{display:flex;gap:9px;padding:10px 14px;border-bottom:1px solid #eef1f5;font-size:11px}.event-time{color:var(--muted);font-variant-numeric:tabular-nums;white-space:nowrap}.event-id{font-weight:800;color:var(--navy);white-space:nowrap}.event-label{color:#516078}.event-label.alert{color:var(--red);font-weight:750}.no-events{padding:14px;color:var(--muted);font-size:12px}
    .drawer-backdrop{display:none;position:fixed;inset:0;background:#0b1f3a66;z-index:10}.drawer-backdrop.open{display:block}.monitor-drawer{position:fixed;z-index:11;top:0;right:0;width:min(440px,94vw);height:100vh;background:#fff;box-shadow:-10px 0 30px #0b1f3a22;transform:translateX(100%);transition:transform .2s ease;overflow:auto}.monitor-drawer.open{transform:translateX(0)}.drawer-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;padding:20px;border-bottom:1px solid var(--line)}.drawer-kicker{font-size:11px;text-transform:uppercase;letter-spacing:.07em;color:var(--muted)}.drawer-id{font-size:23px;font-weight:850;color:var(--navy);margin-top:5px}.drawer-name{font-size:13px;color:#52627a;margin-top:3px}.drawer-close{border:1px solid #d7dee8;background:#fff;border-radius:7px;width:32px;height:32px;font-size:18px;color:#52627a}.drawer-body{padding:18px 20px}.drawer-status{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:11px 12px;background:#f6f9fc;border:1px solid #e9edf3;border-radius:8px}.drawer-status strong{font-size:12px}.drawer-alert{color:var(--red);font-size:11px;margin-top:6px}.drawer-summary{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:16px 0}.drawer-stat{padding:10px;border:1px solid var(--line);border-radius:8px}.drawer-stat-label{font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:var(--muted)}.drawer-stat-value{font-size:15px;font-weight:800;color:var(--navy);margin-top:4px}.drawer-section{margin-top:20px}.drawer-section h3{margin:0 0 9px;font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:var(--muted)}.drawer-task{display:flex;justify-content:space-between;gap:12px;padding:9px 0;border-bottom:1px solid #eef1f5;font-size:12px}.drawer-task:last-child{border-bottom:0}.drawer-task-status{font-weight:750}.drawer-task-status.completed{color:var(--green)}.drawer-task-status.working{color:var(--blue)}.drawer-activity{border-left:2px solid #dce5ef;padding-left:12px}.drawer-event{position:relative;padding:0 0 13px;font-size:11px}.drawer-event:before{content:"";position:absolute;left:-17px;top:3px;width:7px;height:7px;border-radius:50%;background:var(--blue)}.drawer-event time{display:block;color:var(--muted);font-variant-numeric:tabular-nums;margin-bottom:3px}.drawer-event strong{color:var(--navy)}.session-grid{display:grid;grid-template-columns:80px 1fr;gap:7px 10px;font-size:11px}.session-grid dt{color:var(--muted)}.session-grid dd{margin:0;color:#41516a;overflow-wrap:anywhere}.drawer-actions{display:flex;gap:8px;margin-top:20px}.drawer-actions a{flex:1;text-align:center;text-decoration:none}.drawer-actions .monitor-button{width:100%}
    .monitor-fullscreen .monitor-back{display:none}.monitor-fullscreen .monitor-content{padding:14px 16px 0}.monitor-fullscreen .event-strip{margin-bottom:10px}.monitor-fullscreen .applicant-grid{grid-template-columns:repeat(auto-fill,minmax(160px,1fr))}
    @media(max-width:900px){.monitor-summary{grid-template-columns:repeat(3,minmax(105px,1fr))}.monitor-content{padding:15px}.monitor-header{padding:14px 15px}}
    @media(max-width:560px){.monitor-summary{grid-template-columns:repeat(2,minmax(105px,1fr))}.monitor-search{min-width:100%}.filter-group{order:3;width:100%;overflow:auto}.filter-button{flex:1}.toolbar-spacer{display:none}.monitor-select{flex:1}.applicant-grid{grid-template-columns:repeat(2,minmax(0,1fr));padding:8px}.applicant-grid.dense{grid-template-columns:repeat(2,minmax(0,1fr))}.tile-id{font-size:14px}.monitor-header-actions{width:100%;justify-content:space-between}}
  </style>
</head>
<body>
<div class="monitor-shell">
  <header class="monitor-header">
    <a class="monitor-back" href="<?= site_url('admin/exams/' . (int) $exam['id'] . '/edit') ?>">← Back to Exam</a>
    <div class="monitor-title-block">
      <div class="monitor-title" id="examTitle"><?= esc($exam['title']) ?></div>
      <div class="monitor-subtitle"><span id="examSchedule"></span> · <span id="applicantCountLabel"></span> applicants</div>
    </div>
    <div class="monitor-header-actions">
      <div class="live-indicator"><span class="live-dot"></span><span id="liveLabel">Live</span></div>
      <div class="monitor-clock" id="currentClock">Loading BDT...</div>
      <button class="monitor-button primary" id="fullscreenButton" type="button">⛶ Fullscreen Monitor</button>
    </div>
  </header>

  <main class="monitor-content">
    <?php if ($success): ?><div class="monitor-notice success"><?= esc($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="monitor-notice error"><?= esc($error) ?></div><?php endif; ?>
    <section class="monitor-summary" aria-label="Exam summary">
      <div class="summary-card"><div class="summary-label">Total</div><div class="summary-value" id="summaryTotal">0</div></div>
      <div class="summary-card active"><div class="summary-label">Active</div><div class="summary-value" id="summaryActive">0</div></div>
      <div class="summary-card submitted"><div class="summary-label">Submitted</div><div class="summary-value" id="summarySubmitted">0</div></div>
      <div class="summary-card"><div class="summary-label">Not started</div><div class="summary-value" id="summaryNotStarted">0</div></div>
      <div class="summary-card offline"><div class="summary-label">Offline</div><div class="summary-value" id="summaryOffline">0</div></div>
      <div class="summary-card attention"><div class="summary-label">Attention</div><div class="summary-value" id="summaryAttention">0</div></div>
    </section>

    <section class="monitor-toolbar" aria-label="Monitor controls">
      <input class="monitor-search" id="searchInput" type="search" placeholder="Search applicant ID or name..." autocomplete="off">
      <div class="filter-group" role="group" aria-label="Applicant status filter">
        <button class="filter-button active" data-filter="all" type="button">All</button>
        <button class="filter-button" data-filter="active" type="button">Active</button>
        <button class="filter-button" data-filter="submitted" type="button">Submitted</button>
        <button class="filter-button" data-filter="offline" type="button">Offline</button>
      </div>
      <button class="monitor-button attention-button" id="attentionButton" type="button">⚠ Attention Only</button>
      <form id="forceSubmitForm" method="post" action="<?= site_url('admin/exams/' . (int) $exam['id'] . '/monitor/force-submit') ?>"><button class="monitor-button force-submit-button" id="forceSubmitButton" type="submit" disabled>Force submit unsubmitted</button></form>
      <select class="monitor-select" id="sortSelect" aria-label="Sort applicants"><option value="id">Sort: Applicant ID</option><option value="name">Sort: Name</option><option value="progress">Sort: Progress</option><option value="time">Sort: Time remaining</option></select>
      <select class="monitor-select" id="densitySelect" aria-label="Grid density"><option value="standard">Standard</option><option value="dense">Dense</option></select>
      <button class="monitor-button" id="refreshButton" type="button">↻ Refresh</button>
      <span class="refresh-state" id="refreshState">Updated just now</span>
    </section>

    <section class="monitor-panel">
      <div class="grid-head"><div class="grid-title">Applicant monitoring grid</div><div class="grid-count" id="gridCount"></div></div>
      <div class="applicant-grid" id="applicantGrid"></div>
    </section>

    <section class="event-strip">
      <div class="event-head"><strong>Live events</strong><span id="eventUpdated">Updated just now</span></div>
      <div class="event-list" id="eventList"></div>
    </section>
  </main>
</div>

<div class="drawer-backdrop" id="drawerBackdrop"></div>
<aside class="monitor-drawer" id="monitorDrawer" aria-label="Applicant details" aria-hidden="true">
  <div class="drawer-head"><div><div class="drawer-kicker">Applicant</div><div class="drawer-id" id="drawerId">-</div><div class="drawer-name" id="drawerName">-</div></div><button class="drawer-close" id="drawerClose" type="button" aria-label="Close applicant details">×</button></div>
  <div class="drawer-body" id="drawerBody"></div>
</aside>

<script>
const DATA_URL = <?= json_encode(site_url('admin/exams/' . (int) $exam['id'] . '/monitor/data')) ?>;
let monitorData = <?= json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
let state = { filter: "all", attentionOnly: false, search: "", sort: "id", density: "standard", selectedId: null };
let serverNowMs = Date.parse(monitorData.server_now) || Date.now();
let clientSyncMs = Date.now();

const byId = (id) => document.getElementById(id);
const escapeHtml = (value) => String(value ?? "").replace(/[&<>'"]/g, (char) => ({"&":"&amp;","<":"&lt;",">":"&gt;","'":"&#39;","\"":"&quot;"}[char]));
const statusLabel = (status) => ({active:"Active",submitted:"Submitted",not_started:"Not started",offline:"Offline",expired:"Time expired"}[status] || status);
const eventTime = (value) => { if (!value) return "-"; const date = new Date(String(value).replace(" ", "T") + (String(value).includes("Z") ? "" : "+06:00")); return Number.isNaN(date.getTime()) ? value : date.toLocaleTimeString("en-GB", {timeZone:"Asia/Dhaka", hour:"2-digit", minute:"2-digit", second:"2-digit", hour12:true}); };
const relativeTime = (value) => { if (!value) return "No activity"; const date = new Date(String(value).replace(" ", "T") + (String(value).includes("Z") ? "" : "+06:00")); const seconds = Math.max(0, Math.floor((Date.now() - date.getTime()) / 1000)); if (seconds < 10) return "just now"; if (seconds < 60) return seconds + "s ago"; if (seconds < 3600) return Math.floor(seconds / 60) + "m ago"; return Math.floor(seconds / 3600) + "h ago"; };
const formatDuration = (seconds) => { if (seconds === null || seconds === undefined) return "-"; const value = Math.max(0, Math.floor(seconds)); return String(Math.floor(value / 3600)).padStart(2,"0") + ":" + String(Math.floor((value % 3600) / 60)).padStart(2,"0") + ":" + String(value % 60).padStart(2,"0"); };
const liveRemaining = (applicant) => { if (applicant.time_remaining === null || applicant.time_remaining === undefined) return null; return Math.max(0, Number(applicant.time_remaining) - Math.floor((Date.now() - clientSyncMs) / 1000)); };
const applicantById = (id) => monitorData.applicants.find((applicant) => String(applicant.user_id) === String(id));

function updateClock() {
  const now = new Date(serverNowMs + (Date.now() - clientSyncMs));
  byId("currentClock").textContent = new Intl.DateTimeFormat("en-GB", {timeZone:"Asia/Dhaka", day:"2-digit", month:"short", year:"numeric", hour:"2-digit", minute:"2-digit", second:"2-digit", hour12:true}).format(now) + " BDT";
  document.querySelectorAll("[data-time-remaining]").forEach((node) => { const applicant = applicantById(node.dataset.timeRemaining); if (applicant) node.textContent = formatDuration(liveRemaining(applicant)); });
}

function renderSummary() {
  const summary = monitorData.summary || {};
  byId("summaryTotal").textContent = summary.total || 0;
  byId("summaryActive").textContent = summary.active || 0;
  byId("summarySubmitted").textContent = summary.submitted || 0;
  byId("summaryNotStarted").textContent = summary.not_started || 0;
  byId("summaryOffline").textContent = summary.offline || 0;
  byId("summaryAttention").textContent = summary.attention || 0;
  const unsubmittedCount = (monitorData.applicants || []).filter((applicant) => applicant.status !== "submitted" && applicant.status !== "not_started").length;
  const forceSubmitButton = byId("forceSubmitButton");
  forceSubmitButton.disabled = unsubmittedCount === 0;
  forceSubmitButton.textContent = unsubmittedCount ? "Force submit " + unsubmittedCount + " unsubmitted" : "No unsubmitted attempts";
  byId("applicantCountLabel").textContent = summary.total || 0;
  const exam = monitorData.exam;
  byId("examSchedule").textContent = (exam.start_at || "Available now") + " - " + (exam.end_at || "No closing time") + " · " + Math.round(Number(exam.duration_seconds || 0) / 60) + " min";
  byId("liveLabel").textContent = exam.status === "Open" ? "Live" : exam.status;
  document.querySelector(".live-indicator").style.color = exam.status === "Open" ? "var(--green)" : "var(--muted)";
}

function filteredApplicants() {
  const query = state.search.trim().toLowerCase();
  const list = monitorData.applicants.filter((applicant) => {
    if (state.filter !== "all" && applicant.status !== state.filter) return false;
    if (state.attentionOnly && !applicant.attention) return false;
    if (query && !(String(applicant.applicant_id).toLowerCase().includes(query) || String(applicant.name).toLowerCase().includes(query))) return false;
    return true;
  });
  list.sort((left, right) => {
    if (state.sort === "name") return String(left.name).localeCompare(String(right.name));
    if (state.sort === "progress") return Number(right.progress) - Number(left.progress);
    if (state.sort === "time") return (liveRemaining(left) ?? 999999) - (liveRemaining(right) ?? 999999);
    return String(left.applicant_id).localeCompare(String(right.applicant_id), undefined, {numeric:true});
  });
  return list;
}

function taskMarkup(tasks) {
  return (tasks || []).slice(0, 3).map((task) => '<span class="task-chip ' + (task.status === "Completed" ? "complete" : task.status === "Working" ? "working" : "") + '">' + (task.status === "Completed" ? "✓ " : task.status === "Working" ? "● " : "") + escapeHtml(task.label) + '</span>').join("");
}

function renderGrid() {
  const list = filteredApplicants();
  const grid = byId("applicantGrid");
  grid.classList.toggle("dense", state.density === "dense");
  byId("gridCount").textContent = list.length + " of " + monitorData.applicants.length + " applicants";
  if (!list.length) { grid.innerHTML = '<div class="empty-grid">No applicants match the current filters.</div>'; return; }
  grid.innerHTML = list.map((applicant) => {
    const remaining = liveRemaining(applicant);
    const statusClass = applicant.status;
    const attention = applicant.attention ? '<span class="tile-alert">⚠</span>' : '';
    const timeLabel = remaining === null ? (applicant.status === "submitted" ? "Submitted" : "-") : '<span class="tile-time" data-time-remaining="' + applicant.user_id + '">' + formatDuration(remaining) + '</span>';
    return '<button class="applicant-tile ' + statusClass + (applicant.attention ? ' attention' : '') + '" data-applicant-id="' + applicant.user_id + '" type="button">' +
      '<div class="tile-top"><span class="tile-status"><i class="status-dot ' + statusClass + '"></i>' + statusLabel(statusClass) + '</span>' + attention + '</div>' +
      '<div class="tile-id">' + escapeHtml(applicant.applicant_id) + '</div><div class="tile-name">' + escapeHtml(applicant.name) + '</div>' +
      '<div class="tile-tasks">' + taskMarkup(applicant.tasks) + '</div>' +
      '<div class="tile-bottom"><span>' + applicant.answered_count + '/' + applicant.total_count + ' answered</span><span>' + timeLabel + '</span></div></button>';
  }).join("");
  grid.querySelectorAll("[data-applicant-id]").forEach((tile) => tile.addEventListener("click", () => openDrawer(tile.dataset.applicantId)));
}

function renderEvents() {
  const events = monitorData.events || [];
  byId("eventList").innerHTML = events.length ? events.slice(0, 12).map((event) => '<div class="event-item"><span class="event-time">' + eventTime(event.created_at) + '</span><span class="event-id">#' + escapeHtml(event.applicant_id) + '</span><span class="event-label ' + (/LOST|HIDDEN|FOCUS/.test(event.event) ? 'alert' : '') + '">' + escapeHtml(event.label) + '</span></div>').join("") : '<div class="no-events">No live events recorded for this exam yet.</div>';
}

function renderDrawer() {
  const applicant = applicantById(state.selectedId);
  if (!applicant) return;
  byId("drawerId").textContent = applicant.applicant_id;
  byId("drawerName").textContent = applicant.name;
  const taskRows = (applicant.tasks || []).map((task) => '<div class="drawer-task"><span>' + escapeHtml(task.label) + ' (' + task.answered + '/' + task.total + ')</span><span class="drawer-task-status ' + task.status.toLowerCase().replace(" ", "-") + '">' + escapeHtml(task.status) + '</span></div>').join("");
  const activityRows = (applicant.events || []).map((event) => '<div class="drawer-event"><time>' + eventTime(event.created_at) + '</time><strong>' + escapeHtml(event.label) + '</strong><div>' + escapeHtml(event.description || '') + '</div></div>').join("") || '<div style="font-size:12px;color:var(--muted)">No activity events recorded.</div>';
  const reasons = applicant.attention_reasons && applicant.attention_reasons.length ? '<div class="drawer-alert">' + applicant.attention_reasons.map(escapeHtml).join(' · ') + '</div>' : '';
  const session = applicant.session || {};
  byId("drawerBody").innerHTML = '<div class="drawer-status"><strong><span class="status-dot ' + applicant.status + '" style="display:inline-block;margin-right:6px"></span>' + statusLabel(applicant.status) + '</strong><span>' + applicant.progress + '% complete</span></div>' + reasons +
    '<div class="drawer-summary"><div class="drawer-stat"><div class="drawer-stat-label">Started</div><div class="drawer-stat-value" style="font-size:12px">' + escapeHtml(applicant.started_at ? eventTime(applicant.started_at) : '-') + '</div></div><div class="drawer-stat"><div class="drawer-stat-label">Time remaining</div><div class="drawer-stat-value" data-time-remaining="' + applicant.user_id + '">' + formatDuration(liveRemaining(applicant)) + '</div></div><div class="drawer-stat"><div class="drawer-stat-label">Last activity</div><div class="drawer-stat-value" style="font-size:12px">' + relativeTime(applicant.last_activity) + '</div></div><div class="drawer-stat"><div class="drawer-stat-label">Answered</div><div class="drawer-stat-value">' + applicant.answered_count + ' / ' + applicant.total_count + '</div></div></div>' +
    '<section class="drawer-section"><h3>Tasks</h3>' + taskRows + '</section>' +
    '<section class="drawer-section"><h3>Activity</h3><div class="drawer-activity">' + activityRows + '</div></section>' +
    '<section class="drawer-section"><h3>Session</h3><dl class="session-grid"><dt>IP</dt><dd>' + escapeHtml(session.ip || '-') + '</dd><dt>Browser</dt><dd>' + escapeHtml(session.browser || '-') + '</dd><dt>OS</dt><dd>' + escapeHtml(session.os || '-') + '</dd><dt>Device</dt><dd>' + escapeHtml(session.device || '-') + '</dd></dl></section>' +
    '<div class="drawer-actions"><a class="monitor-button" href="<?= site_url('admin/submissions') ?>?exam_id=' + encodeURIComponent(monitorData.exam.id) + '">View submissions</a></div>';
}

function openDrawer(id) { state.selectedId = id; renderDrawer(); byId("drawerBackdrop").classList.add("open"); byId("monitorDrawer").classList.add("open"); byId("monitorDrawer").setAttribute("aria-hidden", "false"); }
function closeDrawer() { state.selectedId = null; byId("drawerBackdrop").classList.remove("open"); byId("monitorDrawer").classList.remove("open"); byId("monitorDrawer").setAttribute("aria-hidden", "true"); }

function renderAll() { renderSummary(); renderGrid(); renderEvents(); updateClock(); }
async function refreshData() {
  byId("refreshState").textContent = "Refreshing...";
  try {
    const response = await fetch(DATA_URL, {headers:{"Accept":"application/json"}, cache:"no-store"});
    const fresh = await response.json();
    if (!response.ok || fresh.error) throw new Error(fresh.error || "Refresh failed");
    monitorData = fresh; serverNowMs = Date.parse(monitorData.server_now) || Date.now(); clientSyncMs = Date.now(); renderAll(); if (state.selectedId) renderDrawer();
    byId("refreshState").textContent = "Updated just now";
  } catch (error) { byId("refreshState").textContent = "Reconnect failed"; }
}

document.querySelectorAll("[data-filter]").forEach((button) => button.addEventListener("click", () => { state.filter = button.dataset.filter; document.querySelectorAll("[data-filter]").forEach((item) => item.classList.toggle("active", item === button)); renderGrid(); }));
byId("searchInput").addEventListener("input", (event) => { state.search = event.target.value; renderGrid(); });
byId("attentionButton").addEventListener("click", () => { state.attentionOnly = !state.attentionOnly; byId("attentionButton").classList.toggle("active", state.attentionOnly); renderGrid(); });
byId("sortSelect").addEventListener("change", (event) => { state.sort = event.target.value; renderGrid(); });
byId("densitySelect").addEventListener("change", (event) => { state.density = event.target.value; renderGrid(); });
byId("refreshButton").addEventListener("click", refreshData);
byId("forceSubmitForm").addEventListener("submit", (event) => {
  const count = (monitorData.applicants || []).filter((applicant) => applicant.status !== "submitted" && applicant.status !== "not_started").length;
  if (!count || !window.confirm("Force-submit " + count + " unsubmitted attempt(s)? Applicants who have not started will not be submitted.")) event.preventDefault();
});
byId("drawerClose").addEventListener("click", closeDrawer); byId("drawerBackdrop").addEventListener("click", closeDrawer);
document.addEventListener("keydown", (event) => { if (event.key === "Escape") closeDrawer(); });
byId("fullscreenButton").addEventListener("click", () => { const root = document.documentElement; if (!document.fullscreenElement && root.requestFullscreen) root.requestFullscreen().catch(() => {}); else if (document.exitFullscreen) document.exitFullscreen().catch(() => {}); });
document.addEventListener("fullscreenchange", () => { document.body.classList.toggle("monitor-fullscreen", !!document.fullscreenElement); byId("fullscreenButton").textContent = document.fullscreenElement ? "× Exit Fullscreen" : "⛶ Fullscreen Monitor"; });
setInterval(updateClock, 1000); setInterval(refreshData, 15000);
renderAll();
</script>
</body>
</html>
