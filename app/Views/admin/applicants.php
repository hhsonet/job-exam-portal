<?php
$adminSection = 'applicants';
$username = session()->get('admin_username');
$usertype = session()->get('admin_usertype');
$errorMessage = service('request')->getGet('error');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Applicants - UIU Recruitment Portal Admin</title>
  <?= $this->include('admin/_styles') ?>
  <style>
    .edit-btn{display:inline-block;padding:8px 11px;border-radius:7px;font-size:12px;font-weight:700;margin-right:5px;text-decoration:none}
    .edit-btn{background:#eaf6ff;color:#168fd4}
    .delete-btn{padding:8px 10px;border:1px solid #f0cbd2;border-radius:7px;background:#fff6f7;color:#b8324b;font-size:12px;cursor:pointer}
    .error-box{margin-bottom:18px;padding:12px 14px;border-radius:8px;background:#fff0f0;color:#a32121}
  </style>
</head>
<body>
<div class="admin-app">
  <?= $this->include('admin/_sidebar') ?>
  <main class="admin-main">
    <header class="admin-topbar">
      <div class="admin-top-title">Applicant Management</div>
      <div class="admin-search">⌕ <span>Search...</span><span style="margin-left:auto">⌘ K</span></div>
      <div style="font-size:20px;color:#778092">☼</div>
    </header>
    <div class="admin-content">
      <div class="admin-heading">
        <div><h1>Applicant Management</h1><p>Create, edit, and manage applicant access for technical assessments.</p></div>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
          <form method="post" action="<?= site_url('admin/applicants/credentials') ?>" onsubmit="return confirm('Generate new 6-character passwords for all applicants and download their credentials? Existing applicant passwords will stop working.');">
            <button class="admin-btn" type="submit">Download all credentials</button>
          </form>
          <a class="admin-btn" href="<?= site_url('admin/applicants/create') ?>">+ Add applicant</a>
        </div>
      </div>
      <?php if ($errorMessage): ?><div class="error-box"><?= esc($errorMessage) ?></div><?php endif; ?>
      <?php if (!$applicants): ?>
        <div class="admin-card admin-empty">No applicants have been created yet.</div>
      <?php else: ?>
        <div class="admin-table">
          <table>
            <thead><tr><th>Name</th><th>Applicant ID</th><th>Position</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($applicants as $applicant): ?>
              <tr>
                <td><strong><?= esc($applicant['full_name']) ?></strong></td>
                <td><?= esc($applicant['applicant_code']) ?></td>
                <td><?= esc($applicant['position']) ?></td>
                <td><?= esc($applicant['created_at']) ?></td>
                <td>
                  <a class="edit-btn" href="<?= site_url('admin/applicants/' . (int) $applicant['id'] . '/edit') ?>">Edit</a>
                  <form style="display:inline" method="post" action="<?= site_url('admin/applicants/' . (int) $applicant['id'] . '/delete') ?>" onsubmit="return confirm('Delete this applicant? This cannot be undone.');">
                    <button class="delete-btn" type="submit">Delete</button>
                  </form>
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
