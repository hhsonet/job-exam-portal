<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Submission details · UIU Recruitment Portal Admin</title>
  <?php $adminSection = 'submissions'; ?>
  <?= $this->include('admin/_styles') ?>
  <style>
    .summary{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px}.summary .admin-card{padding:16px}.label{font-size:11px;color:#798196;text-transform:uppercase}.value{font-size:18px;font-weight:700;margin-top:7px}.back{color:#168fd4;font-size:13px}.notice{padding:12px 14px;border-radius:8px;margin-bottom:18px}.notice.error{background:#fff0f0;color:#a32121}.notice.success{background:#eaf8ef;color:#187345}.submission-nav{display:flex;gap:8px;align-items:center}.submission-nav a,.submission-nav span{padding:9px 12px;border-radius:7px;border:1px solid #dfe4eb;font-size:13px;font-weight:700;text-decoration:none}.submission-nav a{color:#168fd4;background:#fff}.submission-nav span{color:#a9b1bf;background:#f7f9fc}.activity-log{background:#fff;border:1px solid #e8eaf0;border-radius:14px;padding:18px 20px;margin-bottom:20px}.activity-item{padding:12px 0;border-bottom:1px solid #edf0f4}.activity-item:last-child{border-bottom:0}.activity-title{font-weight:700}.activity-meta{color:#69778d;font-size:12px;margin-top:5px}.mark-table{background:#fff;border:1px solid #e8eaf0;border-radius:14px;overflow:auto}.mark-table table{border-collapse:collapse;width:100%;min-width:900px}.mark-table th,.mark-table td{text-align:left;padding:15px;border-bottom:1px solid #edf0f4;vertical-align:top}.mark-table th{font-size:12px;text-transform:uppercase;color:#69778d;letter-spacing:.04em;background:#fbfcfe}.mark-table tr:last-child td{border-bottom:0}.question-number{font-weight:800;color:#168fd4;white-space:nowrap}.question-body{font-weight:400;text-align:justify;line-height:1.5}.question-type{font-size:12px;color:#798196;margin-top:6px}.answer-box{padding:12px;border-radius:8px;background:#f7f9fc;white-space:pre-wrap;line-height:1.5;min-width:220px}.answer-box.unanswered{color:#a36b1b;background:#fff7e5}.file-answer{display:flex;flex-direction:column;gap:8px}.file-answer a{color:#168fd4;font-weight:700}.marks-cell{min-width:150px}.marks-input{width:110px;padding:10px;border:1px solid #b9c2d0;border-radius:7px;font:inherit}.marks-input:focus{outline:2px solid #bceff7;border-color:#168fd4}.marks-input.invalid{border-color:#b8324b;background:#fff6f7}.max-mark{display:block;margin-top:6px;font-size:12px;color:#798196}.mark-footer{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-top:20px;padding:18px 20px;background:#fff;border:1px solid #e8eaf0;border-radius:14px}.overall-label{color:#69778d;font-size:13px}.overall-score{font-size:24px;font-weight:800;color:#182033}.notes{margin-top:20px}.notes label{display:block;font-weight:700;margin-bottom:8px}.notes textarea{width:100%;min-height:100px;box-sizing:border-box;padding:12px;border:1px solid #b9c2d0;border-radius:7px;font:inherit}.save-button{padding:12px 20px;border:0;border-radius:8px;background:#168fd4;color:#fff;font-weight:700;cursor:pointer}@media(max-width:750px){.summary{grid-template-columns:repeat(2,1fr)}.mark-footer{align-items:flex-start;flex-direction:column}.save-button{width:100%}.submission-nav{width:100%;justify-content:space-between}}
  </style>
</head>
<body>
<div class="admin-app">
  <?php $username = session()->get('admin_username'); $usertype = session()->get('admin_usertype'); ?>
  <?= $this->include('admin/_sidebar') ?>
  <main class="admin-main">
    <header class="admin-topbar"><div class="admin-top-title">Submission details</div><div class="admin-search">⌕ <span>Search...</span><span style="margin-left:auto">⌘ K</span></div><div class="admin-pill">✦ AI insights</div><div style="font-size:20px;color:#778092">☼</div></header>
    <div class="admin-content">
      <a class="back" href="<?= site_url('admin/submissions') ?>">← Back to submissions</a>
      <div class="admin-heading">
        <div><h1>Submission #<?= esc($submission['id']) ?></h1><p><?php if ($submission['exam_title']): ?><?= esc($submission['exam_title']) ?><?php else: ?>Exam submission<?php endif; ?></p></div>
        <div class="submission-nav">
          <?php if ($previousSubmissionId): ?><a href="<?= site_url('admin/submissions/' . $previousSubmissionId) ?>">← Previous</a><?php else: ?><span>← Previous</span><?php endif; ?>
          <?php if ($nextSubmissionId): ?><a href="<?= site_url('admin/submissions/' . $nextSubmissionId) ?>">Next →</a><?php else: ?><span>Next →</span><?php endif; ?>
        </div>
      </div>

      <?php if (!empty($markError)): ?><div class="notice error"><?= esc($markError) ?></div><?php endif; ?>
      <?php if (!empty($markSuccess)): ?><div class="notice success"><?= esc($markSuccess) ?></div><?php endif; ?>

      <div class="summary">
        <div class="admin-card"><div class="label">Reference</div><div class="value" style="font-size:14px"><?= esc($submission['reference']) ?></div></div>
        <div class="admin-card"><div class="label">Answered</div><div class="value"><?= esc($submission['answered_count']) ?> / <?= esc($submission['total_count']) ?></div></div>
        <div class="admin-card"><div class="label">Current score</div><div class="value"><?= $submission['score'] !== null ? esc($submission['score']) . ' / ' . esc($submission['max_score']) : 'Not marked' ?></div></div>
        <div class="admin-card"><div class="label">Submitted</div><div class="value" style="font-size:14px"><?= esc($submission['submitted_at']) ?></div></div>
      </div>

      <form id="markForm" method="post" action="<?= site_url('admin/submissions/' . $submission['id'] . '/mark') ?>">
        <section class="mark-table">
          <table>
            <thead><tr><th style="width:70px">SL</th><th>Question body</th><th>Answer / file</th><th style="width:170px">Obtain marks</th></tr></thead>
            <tbody>
            <?php foreach ($questions as $index => $question): ?>
              <?php $answer = $question['answer']; $isFileAnswer = is_array($answer) && !empty($answer['file']); $isUnanswered = $answer === null || $answer === '' || (is_array($answer) && $answer === []); $displayMark = $isUnanswered && $question['obtain_mark'] === '' ? 0 : $question['obtain_mark']; ?>
              <tr>
                <td><span class="question-number">Q<?= $index + 1 ?></span></td>
                <td><div class="question-body"><?= nl2br(esc($question['prompt'])) ?></div><div class="question-type"><?= esc($question['type']) ?> · Maximum <?= esc($question['points']) ?> marks</div></td>
                <td>
                  <?php if ($isFileAnswer): ?>
                    <div class="answer-box file-answer"><span>File: <?= esc($answer['name'] ?? basename((string) $answer['file'])) ?></span><a download="<?= esc($answer['name'] ?? basename((string) $answer['file'])) ?>" href="<?= site_url('admin/submissions/' . $submission['id'] . '/question/' . $question['id'] . '/file') ?>">Download file</a></div>
                  <?php elseif ($isUnanswered): ?>
                    <div class="answer-box unanswered">Not answered</div>
                  <?php else: ?>
                    <div class="answer-box"><?= esc(is_array($answer) ? implode(', ', $answer) : (string) $answer) ?></div>
                  <?php endif; ?>
                </td>
                <td class="marks-cell"><input class="marks-input" name="marks[<?= (int) $question['id'] ?>]" type="number" min="0" max="<?= esc($question['points']) ?>" step="0.01" value="<?= esc($displayMark) ?>" data-max="<?= esc($question['points']) ?>"><span class="max-mark">Max <?= esc($question['points']) ?></span></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </section>

        <div class="notes"><label for="marker_notes">Reviewer notes</label><textarea id="marker_notes" name="marker_notes" placeholder="Add notes for this submission..."><?= esc($submission['marker_notes'] ?? '') ?></textarea></div>
        <div class="mark-footer"><div><div class="overall-label">Overall score</div><div class="overall-score"><span id="overallScore">0</span> / <?= esc(rtrim(rtrim(number_format($maxScore, 2, '.', ''), '0'), '.')) ?></div></div><button class="save-button" type="submit">Save marks</button></div>
      </form>
      <?php if (!empty($activityLogs)): ?><section class="activity-log"><div class="label" style="margin-bottom:5px">Activity log</div><?php foreach ($activityLogs as $activity): ?><div class="activity-item"><div class="activity-title"><?= esc($activity['event']) ?></div><div class="activity-meta"><?= esc($activity['created_at']) ?> · Actor: <?= esc($activity['actor_name'] ?: ($activity['actor_username'] ?: ($activity['user_id'] ? 'User #' . $activity['user_id'] : 'System'))) ?><?php if ($activity['description']): ?> · <?= esc($activity['description']) ?><?php endif; ?></div></div><?php endforeach; ?></section><?php endif; ?>
    </div>
  </main>
</div>
<script>
const markForm = document.getElementById('markForm');
const markInputs = Array.from(document.querySelectorAll('.marks-input'));
const overallScore = document.getElementById('overallScore');
const formatMark = (value) => String(Number(value.toFixed(2))).replace(/\.0+$/, '');
function updateOverall() {
  let total = 0;
  markInputs.forEach((input) => {
    const value = Number(input.value || 0);
    const maximum = Number(input.dataset.max);
    if (Number.isFinite(value) && value >= 0 && value <= maximum) total += value;
  });
  overallScore.textContent = formatMark(total);
}
markInputs.forEach((input) => input.addEventListener('input', () => {
  const value = Number(input.value);
  const maximum = Number(input.dataset.max);
  input.classList.toggle('invalid', input.value !== '' && (!Number.isFinite(value) || value < 0 || value > maximum));
  updateOverall();
}));
markForm.addEventListener('submit', (event) => {
  const invalid = markInputs.find((input) => {
    const value = Number(input.value || 0);
    const maximum = Number(input.dataset.max);
    return !Number.isFinite(value) || value < 0 || value > maximum;
  });
  if (invalid) { event.preventDefault(); invalid.classList.add('invalid'); invalid.focus(); alert('Each obtained mark must be between 0 and the question maximum.'); }
});
updateOverall();
</script>
</body>
</html>
