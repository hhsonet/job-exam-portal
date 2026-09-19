<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Submissions · UIU Recruitment Portal Admin</title>
  <?php $adminSection = 'submissions'; ?>
  <?= $this->include('admin/_styles') ?>
  <style>
    .submission-table form{display:grid;grid-template-columns:75px 85px;gap:6px}.submission-table input,.submission-table textarea{width:100%;box-sizing:border-box;padding:8px;border:1px solid #b9c2d0;border-radius:5px;font:inherit}.submission-table textarea{grid-column:1/3;min-height:55px}.submission-table button{grid-column:1/3;padding:8px;border:0;border-radius:5px;background:#168fd4;color:white;font-weight:600}.filter{display:flex;gap:8px;margin-bottom:18px}.filter select,.filter input{height:38px;border:1px solid #dfe4ec;border-radius:8px;padding:0 10px;background:#fff}.filter button{height:38px;border:0;border-radius:8px;padding:0 15px;background:#168fd4;color:#fff;font-weight:600}.detail-link{color:#168fd4;font-size:12px}
  </style>
</head>
<body>
<div class="admin-app">
  <?= $this->include('admin/_sidebar') ?>
  <main class="admin-main">
    <header class="admin-topbar">
      <div class="admin-top-title">Submissions &amp; marks</div>
      <div class="admin-search">⌕ <span>Search...</span><span style="margin-left:auto">⌘ K</span></div>
      <div class="admin-pill">✦ AI insights</div>
      <div style="font-size:20px;color:#778092">☼</div>
    </header>
    <div class="admin-content">
      <div class="admin-heading"><div><h1>Submissions by applicant</h1><p>Review each applicant's answers, score, and reviewer notes.</p></div><div style="display:flex;gap:8px;align-items:center"><form method="post" action="<?= site_url('admin/submissions/delete-all') ?>" onsubmit="return confirm('Remove all submissions and saved attempts? This cannot be undone.');"><button class="admin-btn" type="submit" style="background:#fff0f0;color:#b8324b;border:1px solid #f0cbd2">Remove all submissions</button></form><a class="admin-btn" href="<?= site_url('admin/exams') ?>">Exam management</a></div></div>
      <?php if (!empty($success)): ?><div style="margin-bottom:18px;padding:12px 14px;border-radius:8px;background:#eaf8ef;color:#187345"><?= esc($success) ?></div><?php endif; ?>
      <form class="filter" method="get" action="<?= site_url('admin/submissions') ?>">
        <select name="exam_id"><option value="">All exams</option><?php foreach (($exams ?? []) as $exam): ?><option value="<?= (int) $exam['id'] ?>" <?= (int) ($examFilter ?? 0) === (int) $exam['id'] ? 'selected' : '' ?>><?= esc($exam['title']) ?></option><?php endforeach; ?></select>
        <select name="applicant_filter"><option value="">All applicants</option><?php foreach ($applicants as $applicant): ?><option value="<?= esc($applicant['applicant_code']) ?>" <?= $applicantFilter === $applicant['applicant_code'] ? 'selected' : '' ?>><?= esc($applicant['full_name']) ?> · <?= esc($applicant['applicant_code']) ?></option><?php endforeach; ?></select>
        <input name="applicant" placeholder="Search name or ID" value="<?= esc($applicantFilter) ?>">
        <button type="submit">Filter</button>
      </form>
      <?php if (!$submissions): ?>
        <div class="admin-card admin-empty">No submissions found for the selected filters.</div>
      <?php else: ?>
        <div class="admin-table submission-table">
          <table>
            <thead><tr><th>Submission</th><th>Exam</th><th>Applicant</th><th>Answered</th><th>Time used</th><th>Submitted</th><th>Mark</th><th>Details</th></tr></thead>
            <tbody>
            <?php foreach ($submissions as $submission): ?>
              <tr>
                <td><strong><a class="detail-link" href="<?= site_url('admin/submissions/' . $submission['id']) ?>"><?= esc($submission['reference']) ?></a></strong><br><small><?= esc($submission['status']) ?></small></td>
                <td><?= esc($submission['exam_title'] ?: 'Unassigned exam') ?></td>
                <td><strong><?= esc($submission['applicant_name'] ?: 'Unknown applicant') ?></strong><br><small><?= esc($submission['applicant_id']) ?></small><?php if ($submission['applicant_position']): ?><br><small><?= esc($submission['applicant_position']) ?></small><?php endif; ?></td>
                <td><?= esc($submission['answered_count']) ?> / <?= esc($submission['total_count']) ?></td>
                <td><?= sprintf('%02d:%02d', intdiv((int) $submission['time_used'], 60), (int) $submission['time_used'] % 60) ?></td>
                <td><?= esc($submission['submitted_at']) ?></td>
                <td><form method="post" action="<?= site_url('admin/submissions/' . $submission['id'] . '/mark') ?>"><input name="score" type="number" min="0" step="0.01" placeholder="Score" value="<?= esc($submission['score'] ?? '') ?>"><input name="max_score" type="number" min="0" step="0.01" placeholder="Max" value="<?= esc($submission['max_score'] ?? '') ?>"><textarea name="marker_notes" placeholder="Reviewer notes"><?= esc($submission['marker_notes'] ?? '') ?></textarea><button type="submit">Save mark</button></form></td>
                <td><a class="detail-link" href="<?= site_url('admin/submissions/' . $submission['id']) ?>">View answers →</a></td>
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
