<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc($formTitle ?? 'Add question') ?> · UIU Recruitment Portal Admin</title>
  <?php $adminSection = 'exams'; ?>
  <?= $this->include('admin/_styles') ?>
  <style>
    .existing-files{display:grid;gap:8px;margin:8px 0 12px}.existing-file{display:flex;justify-content:space-between;gap:12px;align-items:center;padding:10px 12px;border:1px solid #dfe4ec;border-radius:8px;background:#f8fafc;font-size:13px}.existing-file a{color:#168fd4;font-weight:700}
  </style>
</head>
<body>
<div class="admin-app">
  <?php $username = session()->get('admin_username'); $usertype = session()->get('admin_usertype'); ?>
  <?= $this->include('admin/_sidebar') ?>
  <main class="admin-main">
    <header class="admin-topbar"><div class="admin-top-title"><?= esc($formTitle ?? 'Add question') ?></div><div class="admin-search">⌕ <span>Search...</span><span style="margin-left:auto">⌘ K</span></div><div class="admin-pill">✦ AI insights</div><div style="font-size:20px;color:#778092">☼</div></header>
    <div class="admin-content">
      <div class="admin-heading"><div><h1><?= esc($formTitle ?? 'Add question to an exam') ?></h1><p>Questions belong to an exam and appear when that exam is active.</p></div><a href="<?= site_url('admin/questions') ?>" style="color:#168fd4;font-size:13px">← Back to question library</a></div>
      <div class="admin-card admin-form-card">
        <form method="post" enctype="multipart/form-data" action="<?= esc($formAction ?? site_url('admin/questions')) ?>">
          <?php if ($error): ?><div class="admin-error"><?= esc($error) ?></div><?php endif; ?>
          <label for="exam_id">Exam</label>
          <select id="exam_id" name="exam_id" required><option value="">Select an exam</option><?php foreach (($exams ?? []) as $exam): ?><option value="<?= esc($exam['id']) ?>" <?= ((int) ($data['exam_id'] ?? 0) === (int) $exam['id']) ? 'selected' : '' ?>><?= esc($exam['title']) ?></option><?php endforeach; ?></select>

          <label for="type">Question type</label>
          <select id="type" name="type">
            <?php foreach (['single' => 'Single choice', 'multi' => 'Multiple choice', 'bool' => 'True / false', 'written' => 'Written answer', 'typing' => 'Typing test', 'upload' => 'File upload'] as $type => $label): ?><option value="<?= $type ?>" <?= ($data['type'] ?? 'single') === $type ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
          </select>
          <div id="typingAnswerField">
            <label for="typing_answer">Reference text for verification</label>
            <textarea id="typing_answer" name="typing_answer" placeholder="Enter the exact text the applicant must type."><?= esc($data['typing_answer'] ?? '') ?></textarea>
            <p style="font-size:13px;color:#798196">The applicant sees the question prompt, not this reference text. Matching is checked securely on the server when the exam is submitted.</p>
          </div>
          <div id="allowedFileTypesField">
            <label for="allowed_file_types">Allowed file types</label>
            <select id="allowed_file_types" name="allowed_file_types[]" multiple size="3" required>
              <?php $selectedFileTypes = $data['allowed_file_types'] ?? ['pdf']; ?>
              <?php foreach (['pdf' => 'PDF', 'excel' => 'Excel', 'all' => 'All file types'] as $fileType => $label): ?><option value="<?= $fileType ?>" <?= in_array($fileType, $selectedFileTypes, true) ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
            </select>
            <p style="font-size:13px;color:#798196">Only used for File upload questions. Choose one or more types; selecting All overrides the other choices.</p>
          </div>

          <label for="prompt">Question prompt</label>
          <textarea id="prompt" name="prompt" required><?= esc($data['prompt'] ?? '') ?></textarea>
          <label for="hint">Hint / instructions</label>
          <input id="hint" name="hint" value="<?= esc($data['hint'] ?? '') ?>">
          <label for="points">Points</label>
          <input id="points" name="points" type="number" min="1" value="<?= esc($data['points'] ?? 1) ?>">
          <label for="options">Options</label>
          <textarea id="options" name="options" placeholder="One option per line"><?= esc($data['options'] ?? '') ?></textarea>
          <p style="font-size:13px;color:#798196">Required for choice-based questions; options are automatically labelled A, B, C, and so on.</p>

          <label>Question attachments</label>
          <?php if (!empty($existingAttachments)): ?>
            <div class="existing-files">
              <?php foreach ($existingAttachments as $attachment): ?><div class="existing-file"><span><?= esc($attachment['original_name']) ?></span><a download="<?= esc($attachment['original_name']) ?>" href="<?= site_url('admin/questions/' . ($questionId ?? 0) . '/attachment/' . $attachment['id']) ?>">Download</a></div><?php endforeach; ?>
            </div>
            <p style="font-size:13px;color:#798196">Existing attachments are kept. Add more files below if needed.</p>
          <?php endif; ?>
          <div id="attachmentList" style="display:grid;gap:8px;margin-bottom:10px"></div>
          <button id="attachmentAddButton" type="button" style="padding:10px 14px;border:1px solid #b9c2d0;border-radius:7px;background:#fff;color:#168fd4;font-weight:700;cursor:pointer">＋ Add file</button>
          <p style="font-size:13px;color:#798196">Add files one at a time. Each file can be up to 10 MB.</p>

          <div id="attachmentModal" hidden style="display:none;position:fixed;inset:0;z-index:20;background:#17203366;place-items:center;padding:20px"><div style="width:min(460px,100%);background:#fff;border-radius:14px;padding:24px;box-shadow:0 20px 60px #17203333"><div style="font-size:18px;font-weight:700;margin-bottom:18px">Add question attachment</div><label for="attachmentName">File name</label><input id="attachmentName" type="text" placeholder="Reference document.pdf" style="width:100%;box-sizing:border-box;padding:12px;border:1px solid #b9c2d0;border-radius:7px;font:inherit"><label for="attachmentFile" style="display:block;font-weight:600;margin:17px 0 7px">File</label><div id="attachmentFileSlot"><input id="attachmentFile" type="file" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg" style="width:100%"></div><div id="attachmentModalError" style="display:none;margin-top:12px;color:#a32121;font-size:13px"></div><div style="display:flex;justify-content:flex-end;gap:8px;margin-top:20px"><button id="attachmentCancelButton" type="button" style="padding:10px 14px;border:1px solid #dfe4ec;border-radius:7px;background:#fff;cursor:pointer">Cancel</button><button id="attachmentSaveButton" type="button" style="padding:10px 14px;border:0;border-radius:7px;background:#168fd4;color:#fff;font-weight:700;cursor:pointer">Add file</button></div></div></div>
          <button class="admin-btn" type="submit" style="margin-top:22px"><?= esc($submitLabel ?? 'Add question') ?></button>
        </form>
      </div>
    </div>
  </main>
</div>
<script>
(() => {
  const modal = document.getElementById('attachmentModal');
  const list = document.getElementById('attachmentList');
  const nameInput = document.getElementById('attachmentName');
  const fileSlot = document.getElementById('attachmentFileSlot');
  const error = document.getElementById('attachmentModalError');
  let fileInput;
  const showError = (message) => { error.textContent = message; error.style.display = 'block'; };
  const bindPicker = () => { fileInput = fileSlot.querySelector('input[type=file]'); fileInput.addEventListener('change', () => { if (fileInput.files[0] && !nameInput.value) nameInput.value = fileInput.files[0].name; }); };
  const preparePicker = () => { const picker = document.createElement('input'); picker.type = 'file'; picker.accept = '.pdf,.doc,.docx,.png,.jpg,.jpeg'; picker.style.width = '100%'; fileSlot.replaceChildren(picker); bindPicker(); };
  const closeModal = () => { modal.hidden = true; modal.style.display = 'none'; nameInput.value = ''; error.style.display = 'none'; preparePicker(); };
  document.getElementById('attachmentAddButton').addEventListener('click', () => { modal.hidden = false; modal.style.display = 'grid'; nameInput.focus(); });
  document.getElementById('attachmentCancelButton').addEventListener('click', closeModal);
  document.getElementById('attachmentSaveButton').addEventListener('click', () => {
    if (!fileInput.files[0]) { showError('Choose a file first.'); return; }
    const row = document.createElement('div'); row.style = 'display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid #dfe4ec;border-radius:8px;background:#f8fafc';
    const label = document.createElement('span'); label.style = 'flex:1;font-size:13px'; label.textContent = nameInput.value.trim() || fileInput.files[0].name;
    const hiddenName = document.createElement('input'); hiddenName.type = 'hidden'; hiddenName.name = 'attachment_names[]'; hiddenName.value = label.textContent;
    const remove = document.createElement('button'); remove.type = 'button'; remove.textContent = 'Remove'; remove.style = 'border:0;background:none;color:#b8324b;font-weight:700;cursor:pointer'; remove.addEventListener('click', () => row.remove());
    fileInput.name = 'attachments[]'; fileInput.hidden = true; row.append(label, hiddenName, fileInput, remove); list.append(row); closeModal();
  });
  const typeSelect = document.getElementById('type');
  const typingAnswerField = document.getElementById('typingAnswerField');
  const typingAnswerInput = document.getElementById('typing_answer');
  const fileTypesField = document.getElementById('allowedFileTypesField');
  const fileTypesSelect = document.getElementById('allowed_file_types');
  const syncFileTypes = () => { const visible = typeSelect.value === 'upload'; fileTypesField.hidden = !visible; fileTypesSelect.disabled = !visible; fileTypesSelect.required = visible; };
  const syncTypingAnswer = () => { const visible = typeSelect.value === 'typing'; typingAnswerField.hidden = !visible; typingAnswerInput.disabled = !visible; typingAnswerInput.required = visible; };
  typeSelect.addEventListener('change', syncFileTypes);
  typeSelect.addEventListener('change', syncTypingAnswer);
  fileTypesSelect.addEventListener('change', () => { const all = Array.from(fileTypesSelect.options).find((option) => option.value === 'all'); if (all && all.selected) Array.from(fileTypesSelect.options).forEach((option) => { if (option.value !== 'all') option.selected = false; }); });
  syncFileTypes();
  syncTypingAnswer();
  bindPicker();
})();
</script>
</body>
</html>
