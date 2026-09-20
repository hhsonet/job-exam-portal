<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Questions · UIU Recruitment Portal Admin</title>
  <?php $adminSection = 'questions'; ?>
  <?= $this->include('admin/_styles') ?>
  <style>
    .action-cell{display:flex;gap:7px;align-items:center}.edit-btn,.delete-btn{padding:7px 10px;border-radius:7px;font-size:12px;cursor:pointer}.edit-btn{border:1px solid #bce4f5;background:#eef8ff;color:#168fd4;text-decoration:none}.delete-btn{border:1px solid #f0cbd2;background:#fff6f7;color:#b8324b}
  </style>
</head>
<body>
<div class="admin-app">
  <?php $username = session()->get('admin_username'); $usertype = session()->get('admin_usertype'); ?>
  <?= $this->include('admin/_sidebar') ?>
  <main class="admin-main">
    <header class="admin-topbar"><div class="admin-top-title">Questions</div><div class="admin-search">⌕ <span>Search...</span><span style="margin-left:auto">⌘ K</span></div><div class="admin-pill">✦ AI insights</div><div style="font-size:20px;color:#778092">☼</div></header>
    <div class="admin-content">
      <div class="admin-heading"><div><h1>Question library</h1><p>Build and manage the questions used in your applicant assessments.</p></div><a class="admin-btn" href="<?= site_url('admin/questions/new') ?>">＋ Create question</a></div>
      <?php if (!$questions): ?>
        <div class="admin-card admin-empty">No questions yet.</div>
      <?php else: ?>
        <div class="admin-table">
          <table>
            <thead><tr><th>#</th><th>Question</th><th>Type</th><th>Points</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($questions as $index => $question): ?>
              <tr>
                <td><?= $index + 1 ?></td>
                <td><strong><?= esc($question['prompt']) ?></strong><?php if ($question['hint']): ?><br><small style="color:#798196"><?= esc($question['hint']) ?></small><?php endif; ?><?php if ($question['options']): ?><details style="margin-top:8px;color:#69778d"><summary>View options</summary><ol><?php foreach ($question['options'] as $option): ?><li><?= esc($option['text']) ?></li><?php endforeach; ?></ol></details><?php endif; ?></td>
                <td><?= esc(['single' => 'Single choice', 'multi' => 'Multiple choice', 'bool' => 'True / false', 'written' => 'Written answer', 'typing' => 'Typing test', 'upload' => 'File upload'][$question['type']] ?? $question['type']) ?></td>
                <td><?= esc($question['points']) ?></td>
                <td><?= $question['is_active'] ? 'Active' : 'Inactive' ?></td>
                <td><div class="action-cell"><a class="edit-btn" href="<?= site_url('admin/questions/' . $question['id'] . '/edit') ?>">Edit</a><form method="post" action="<?= site_url('admin/questions/' . $question['id'] . '/delete') ?>" onsubmit="return confirm('Delete this question? This cannot be undone.');"><button class="delete-btn" type="submit">Delete</button></form></div></td>
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
