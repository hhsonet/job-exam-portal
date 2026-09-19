<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Exams · UIU Recruitment Portal Admin</title>
  <?php $adminSection = 'exams'; ?>
  <?= $this->include('admin/_styles') ?>
  <style>
    .delete-btn{padding:8px 10px;border:1px solid #f0cbd2;border-radius:7px;background:#fff6f7;color:#b8324b;font-size:12px;cursor:pointer}
    .edit-btn{display:inline-block;padding:8px 11px;border-radius:7px;background:#eaf6ff;color:#168fd4;font-size:12px;font-weight:700;margin-right:5px}
    .error-box{margin-bottom:18px;padding:12px 14px;border-radius:8px;background:#fff0f0;color:#a32121}
    .state-pill{display:inline-block;padding:5px 8px;border-radius:999px;font-size:11px;font-weight:700}
    .state-open{background:#e8f4ee;color:#0f7b4f}.state-closed{background:#fdecec;color:#b3261e}.state-scheduled{background:#fdf3e3;color:#8a5a08}.state-draft{background:#edf1f6;color:#52627a}
    .metric{font-weight:700;color:#0b1f3a}.muted{color:#798196;font-size:12px}
    .remove-btn{padding:8px 10px;border:1px solid #f0cbd2;border-radius:7px;background:#fff6f7;color:#b8324b;font-size:12px;cursor:pointer;margin-top:6px}
    .monitor-heading{display:flex;justify-content:space-between;align-items:end;gap:16px;margin:30px 0 14px}
    .monitor-heading h2{margin:0;font-size:18px;letter-spacing:-.02em}.monitor-heading p{margin:5px 0 0;color:#798196;font-size:13px}
    .monitor-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(390px,1fr));gap:16px}
    .monitor-card{background:#fff;border:1px solid #e8eaf0;border-radius:14px;padding:18px}
    .monitor-card-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:14px}
    .monitor-card-title{font-size:16px;font-weight:750;color:#0b1f3a}.monitor-card-meta{font-size:12px;color:#798196;margin-top:5px;line-height:1.5}
    .monitor-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;margin-bottom:16px}
    .monitor-stat{background:#f7f9fc;border:1px solid #edf0f4;border-radius:8px;padding:9px 10px}.monitor-stat-label{font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:#8a93a3}.monitor-stat-value{font-size:14px;font-weight:750;color:#182033;margin-top:4px}
    .applicant-progress{border-top:1px solid #edf0f4}.applicant-row{padding:12px 0;border-bottom:1px solid #edf0f4}.applicant-row:last-child{border-bottom:0;padding-bottom:0}.applicant-row-top{display:flex;justify-content:space-between;gap:10px;align-items:baseline}.applicant-name{font-size:13px;font-weight:700;color:#182033;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.applicant-id{font-size:11px;color:#8a93a3;margin-left:5px;font-weight:500}.applicant-status{font-size:11px;font-weight:700;white-space:nowrap}.status-submitted{color:#0f7b4f}.status-progress{color:#168fd4}.status-not-started{color:#8a5a08}.progress-track{height:8px;background:#edf1f6;border-radius:999px;overflow:hidden;margin-top:8px}.progress-fill{height:100%;border-radius:999px;background:linear-gradient(90deg,#21bde1,#168fd4)}.progress-fill.submitted{background:#35bd86}.progress-fill.not-started{background:#d6dce5}.applicant-row-bottom{display:flex;justify-content:space-between;gap:10px;margin-top:6px;color:#798196;font-size:11px}.monitor-empty{font-size:13px;color:#798196;padding:8px 0 2px}
    @media(max-width:680px){.monitor-grid{grid-template-columns:1fr}.monitor-stats{grid-template-columns:1fr 1fr}.monitor-stats .monitor-stat:last-child{grid-column:1/-1}}
  </style>
</head>
<body>
<div class="admin-app">
  <?= $this->include('admin/_sidebar') ?>
  <main class="admin-main">
    <header class="admin-topbar">
      <div class="admin-top-title">Exams</div>
      <div class="admin-search">⌕ <span>Search...</span><span style="margin-left:auto">⌘ K</span></div>
      <div class="admin-pill">✦ AI insights</div>
      <div style="font-size:20px;color:#778092">☼</div>
    </header>
    <div class="admin-content">
      <div class="admin-heading">
        <div><h1>Exam management</h1><p>Monitor assessment state, applicants, submissions, and question readiness.</p></div>
        <a class="admin-btn" href="<?= site_url('admin/exams/new') ?>">＋ Create exam</a>
      </div>
      <?php if ($error): ?><div class="error-box"><?= esc($error) ?></div><?php endif; ?>
      <?php if (! empty($success)): ?><div style="margin-bottom:18px;padding:12px 14px;border-radius:8px;background:#eaf8ef;color:#187345;"><?= esc($success) ?></div><?php endif; ?>
      <?php if (! $exams): ?>
        <div class="admin-card admin-empty">No exams have been created yet.</div>
      <?php else: ?>
        <div class="monitor-heading">
          <div><h2>Applicant completion monitor</h2><p>Track saved answers and final submissions for every assigned applicant.</p></div>
          <span class="muted">Progress is based on answered questions</span>
        </div>
        <div class="monitor-grid">
          <?php foreach ($exams as $exam): ?>
            <section class="monitor-card">
              <?php $monitorStateClass = strtolower($exam['display_status']) === 'open' ? 'state-open' : (strtolower($exam['display_status']) === 'closed' ? 'state-closed' : (strtolower($exam['display_status']) === 'scheduled' ? 'state-scheduled' : 'state-draft')); ?>
              <div class="monitor-card-head">
                <div><div class="monitor-card-title"><?= esc($exam['title']) ?></div><div class="monitor-card-meta"><?= (int) ($exam['duration_seconds'] / 60) ?> minutes · <?= $exam['start_at'] ? esc($exam['start_at']) : 'Available now' ?> to <?= $exam['end_at'] ? esc($exam['end_at']) : 'No closing time' ?></div></div>
                <span class="state-pill <?= $monitorStateClass ?>"><?= esc($exam['display_status']) ?></span>
              </div>
              <div class="monitor-stats">
                <div class="monitor-stat"><div class="monitor-stat-label">Applicants</div><div class="monitor-stat-value"><?= count($exam['applicants']) ?></div></div>
                <div class="monitor-stat"><div class="monitor-stat-label">Submitted</div><div class="monitor-stat-value"><?= (int) $exam['submission_count'] ?></div></div>
                <div class="monitor-stat"><div class="monitor-stat-label">Questions</div><div class="monitor-stat-value"><?= (int) $exam['question_count'] ?></div></div>
              </div>
              <?php if (! $exam['applicants']): ?>
                <div class="monitor-empty">No applicants are assigned to this exam.</div>
              <?php else: ?>
                <div class="applicant-progress">
                  <?php foreach ($exam['applicants'] as $applicant): ?>
                    <?php $statusClass = $applicant['status'] === 'Submitted' ? 'status-submitted' : ($applicant['status'] === 'In progress' ? 'status-progress' : 'status-not-started'); $fillClass = $applicant['status'] === 'Submitted' ? 'submitted' : ($applicant['status'] === 'Not started' ? 'not-started' : ''); ?>
                    <div class="applicant-row">
                      <div class="applicant-row-top"><div class="applicant-name"><?= esc($applicant['name']) ?><span class="applicant-id"><?= esc($applicant['applicant_id']) ?></span></div><span class="applicant-status <?= $statusClass ?>"><?= esc($applicant['status']) ?></span></div>
                      <div class="progress-track" aria-label="<?= esc($applicant['name']) ?> completion"><div class="progress-fill <?= $fillClass ?>" style="width:<?= (int) $applicant['progress'] ?>%"></div></div>
                      <div class="applicant-row-bottom"><span><?= (int) $applicant['answered_count'] ?> / <?= (int) $applicant['total_count'] ?> answered</span><span><?= (int) $applicant['progress'] ?>%</span></div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </section>
          <?php endforeach; ?>
        </div>

        <div class="monitor-heading"><div><h2>Exam configuration</h2><p>Manage schedule, questions, submissions, and exam settings.</p></div></div>
        <div class="admin-table">
          <table>
            <thead>
              <tr>
                <th>Exam</th>
                <th>State &amp; schedule</th>
                <th>Questions</th>
                <th>Applicants</th>
                <th>Submissions</th>
                <th>Latest submission</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($exams as $exam): ?>
              <?php $stateClass = strtolower($exam['display_status']) === 'open' ? 'state-open' : (strtolower($exam['display_status']) === 'closed' ? 'state-closed' : (strtolower($exam['display_status']) === 'scheduled' ? 'state-scheduled' : 'state-draft')); ?>
              <tr>
                <td>
                  <strong><?= esc($exam['title']) ?></strong><br>
                  <span class="muted"><?= esc($exam['description']) ?></span><br>
                  <span class="muted"><?= (int) ($exam['duration_seconds'] / 60) ?> minutes · <?= $exam['allow_multiple_submissions'] ? 'Multiple submissions' : 'Single submission' ?></span>
                </td>
                <td>
                  <span class="state-pill <?= $stateClass ?>"><?= esc($exam['display_status']) ?></span><br>
                  <span class="muted">Start: <?= $exam['start_at'] ? esc($exam['start_at']) : 'Available now' ?></span><br>
                  <span class="muted">End: <?= $exam['end_at'] ? esc($exam['end_at']) : 'No closing time' ?></span>
                </td>
                <td><span class="metric"><?= (int) $exam['question_count'] ?></span><br><span class="muted">active questions</span></td>
                <td><span class="metric"><?= (int) $exam['applicant_count'] ?></span><br><span class="muted">assigned</span></td>
                <td><span class="metric"><?= (int) $exam['submission_count'] ?></span><br><span class="muted"><?= (int) $exam['submission_rate'] ?>% completion</span></td>
                <td><?= $exam['latest_submission_at'] ? esc($exam['latest_submission_at']) : '<span class="muted">No submissions</span>' ?></td>
                <td>
                  <a class="edit-btn" href="<?= site_url('admin/exams/' . $exam['id'] . '/edit') ?>">Edit</a>
                  <a class="admin-btn" style="padding:8px 11px;font-size:12px;margin-right:5px" href="<?= site_url('admin/exams/' . $exam['id'] . '/questions') ?>">Questions</a>
                  <a class="admin-btn" style="padding:8px 11px;font-size:12px;margin-right:5px" href="<?= site_url('admin/submissions?exam_id=' . $exam['id']) ?>">Submissions</a>
                  <?php if ((int) $exam['submission_count'] > 0): ?><form method="post" action="<?= site_url('admin/exams/' . $exam['id'] . '/submissions/delete-all') ?>" onsubmit="return confirm('Remove all submissions and reset saved attempts for this exam? This cannot be undone.');"><button class="remove-btn" type="submit">Remove all submissions</button></form><?php endif; ?>
                  <form style="display:inline" method="post" action="<?= site_url('admin/exams/' . $exam['id'] . '/delete') ?>" onsubmit="return confirm('Delete this exam and its questions? This cannot be undone.');"><button class="delete-btn" type="submit">Delete</button></form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>
</body>
</html>
