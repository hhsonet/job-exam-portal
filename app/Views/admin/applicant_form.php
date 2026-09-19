<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc($formTitle) ?> · UIU Recruitment Portal Admin</title>
  <?php $adminSection = 'applicants'; ?>
  <?= $this->include('admin/_styles') ?>
</head>
<body>
<div class="admin-app">
  <?php $username = session()->get('admin_username'); $usertype = session()->get('admin_usertype'); ?>
  <?= $this->include('admin/_sidebar') ?>
  <main class="admin-main">
    <header class="admin-topbar">
      <div class="admin-top-title"><?= esc($formTitle) ?></div>
      <div class="admin-search">⌕ <span>Search...</span><span style="margin-left:auto">⌘ K</span></div>
      <div class="admin-pill">✦ AI insights</div>
      <div style="font-size:20px;color:#778092">☼</div>
    </header>
    <div class="admin-content">
      <div class="admin-heading">
        <div><h1><?= esc($formTitle) ?></h1><p>Manage the applicant's identity, access, and assessment assignment.</p></div>
        <a href="<?= site_url('admin/applicants') ?>" style="color:#168fd4;font-size:13px">← Back to applicants</a>
      </div>
      <div class="admin-card admin-form-card">
        <form method="post" action="<?= esc($formAction) ?>">
          <?php if ($error): ?><div class="admin-error"><?= esc($error) ?></div><?php endif; ?>
          <label for="full_name">Name</label>
          <input id="full_name" name="full_name" value="<?= esc($data['full_name'] ?? '') ?>" required>
          <label for="applicant_code">Applicant ID</label>
          <input id="applicant_code" name="applicant_code" placeholder="APP-2026-00001" value="<?= esc($data['applicant_code'] ?? '') ?>" required>
          <label for="password">Password <?= isset($data['applicant_code']) ? '<small style="color:#798196">(leave blank to keep current password)</small>' : '' ?></label>
          <input id="password" name="password" type="password" <?= isset($data['applicant_code']) ? '' : 'required' ?>>
          <label for="assigned_exam_id">Position / assessment</label>
          <select id="assigned_exam_id" name="assigned_exam_id" required>
            <option value="">Select an available exam</option>
            <?php foreach (($exams ?? []) as $exam): ?>
              <option value="<?= (int) $exam['id'] ?>" <?= (int) ($data['assigned_exam_id'] ?? 0) === (int) $exam['id'] ? 'selected' : '' ?>><?= esc($exam['title']) ?></option>
            <?php endforeach; ?>
          </select>
          <p style="font-size:13px;color:#798196">This controls which assessment the applicant can view and open.</p>
          <button class="admin-btn" type="submit" style="margin-top:22px"><?= esc($submitLabel) ?></button>
        </form>
      </div>
    </div>
  </main>
</div>
</body>
</html>
