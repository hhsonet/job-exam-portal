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
