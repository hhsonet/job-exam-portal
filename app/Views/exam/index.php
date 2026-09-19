<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Applicant Assessment | UIU Recruitment Portal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,400;0,500;0,600;0,700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root {
    --blue: #1A56DB; --blue-dark: #123FA5; --navy: #0B1F3A;
    --ink-muted: #44536B; --ink-faint: #5B6B84;
    --border: #E2E6ED; --border-input: #C9D3E0;
    --bg-subtle: #F7F9FC; --bg-aside: #FBFCFE;
    --amber-bg: #FDF3E3; --amber-border: #F0D9B5; --amber-ink: #7A4A00; --amber-ink-strong: #8A4708;
    --amber-warn-bg: #FEF6EC; --amber-dot: #C2620A;
    --red-bg: #FDECEC; --red-border: #F3C4C4; --red-ink: #A31D1D; --red-strong: #B3261E; --red-deep: #C62828;
    --green-bg: #E8F4EE; --green-border: #BFE0CE; --green-ink: #0F7B4F;
  }
  html, body { margin: 0; padding: 0; background: #FFFFFF; color: var(--navy); font-family: 'Public Sans', Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; }
  * { box-sizing: border-box; }
  button { font-family: inherit; }
  a { color: var(--blue); }
  a:hover { color: var(--blue-dark); }
  :focus-visible { outline: 3px solid var(--blue); outline-offset: 2px; }
  @keyframes pulseWarn { 0%, 100% { opacity: 1; } 50% { opacity: 0.55; } }
  .mono { font-family: 'IBM Plex Mono', monospace; }
  .hidden { display: none !important; }
  .brand-badge { width: 34px; height: 34px; border-radius: 7px; background: var(--blue); display: flex; align-items: center; justify-content: center; color: #FFFFFF; font-weight: 700; font-size: 15px; letter-spacing: 0.5px; flex: none; }
  .btn { font-size: 16px; font-weight: 600; border-radius: 10px; cursor: pointer; padding: 13px 24px; }
  .btn:disabled { cursor: not-allowed; }
  #screenExam header { border-bottom: 1px solid var(--border); padding: 12px 24px; display: flex; align-items: center; gap: 20px; flex-wrap: wrap; position: sticky; top: 0; background: #FFFFFF; z-index: 20; }
  .option-btn { display: flex; align-items: center; gap: 16px; width: 100%; text-align: left; padding: 18px 20px; min-height: 64px; cursor: pointer; border-radius: 10px; background: #FFFFFF; border: 1px solid var(--border-input); }
  .option-btn.selected { background: #F2F6FE; border: 2px solid var(--blue); }
  .option-marker { flex: none; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; font-family: 'IBM Plex Mono', monospace; font-size: 14px; font-weight: 600; border-radius: 50%; background: #FFFFFF; color: var(--ink-muted); border: 1px solid var(--border-input); }
  .option-marker.selected { background: var(--blue); color: #FFFFFF; border-color: var(--blue); }
  .nav-btn { height: 44px; border-radius: 8px; cursor: pointer; font-size: 15px; font-weight: 600; font-family: 'IBM Plex Mono', monospace; background: #FFFFFF; color: var(--ink-muted); border: 1px solid var(--border-input); }
  .nav-btn.answered { background: var(--blue); color: #FFFFFF; border: 1px solid transparent; }
  .nav-btn.current { border: 2px solid var(--navy); }
  .dropzone { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; padding: 48px 28px; border-radius: 12px; cursor: pointer; text-align: center; border: 2px dashed var(--border-input); background: var(--bg-aside); }
  .dropzone.drag-active { border-color: var(--blue); background: #F2F6FE; }
  .modal-backdrop { position: fixed; inset: 0; background: rgba(11, 31, 58, 0.45); display: flex; align-items: center; justify-content: center; padding: 24px; z-index: 60; }
  .modal-card { width: 100%; background: #FFFFFF; border-radius: 14px; box-shadow: 0 24px 60px rgba(11, 31, 58, 0.25); padding: 32px; }
</style>
</head>
<body>
<div style="min-height: 100vh; display: flex; flex-direction: column; background: #FFFFFF;">

  <!-- INSTRUCTIONS -->
  <div id="screenInstructions" style="flex: 1; display: flex; flex-direction: column;">
    <div style="border-bottom: 1px solid var(--border); padding: 18px 28px; display: flex; align-items: center; gap: 14px;">
      <div class="brand-badge">UIU</div>
      <div style="font-size: 16px; font-weight: 600; letter-spacing: -0.01em;">UIU Recruitment Portal</div>
      <div class="mono" style="margin-left: auto; font-size: 12px; color: var(--ink-faint); letter-spacing: 0.04em;">SECURE ASSESSMENT PORTAL</div>
    </div>

    <div style="flex: 1; display: flex; justify-content: center; padding: 48px 28px 72px;">
      <div style="width: 100%; max-width: 860px;">
        <div class="mono" style="font-size: 12px; letter-spacing: 0.1em; color: var(--blue); text-transform: uppercase; margin-bottom: 14px;">Step 1 of 2 &nbsp;·&nbsp; Before you begin</div>
        <h1 style="margin: 0 0 12px; font-size: 38px; line-height: 1.15; font-weight: 700; letter-spacing: -0.025em; max-width: 20ch;">Applicant dashboard</h1>
        <p style="margin: 0 0 36px; font-size: 17px; line-height: 1.6; color: var(--ink-muted); max-width: 62ch;">Review your applicant details and choose from the available assessments below.</p>
        <?php if (! empty($workflowError)): ?><div style="border:1px solid #F3C4C4;background:#FDECEC;color:#8A2020;border-radius:10px;padding:14px 18px;margin-bottom:24px;font-size:14px;font-weight:600;"><?= esc($workflowError) ?></div><?php endif; ?>

        <?php $dashboardExam = $dashboardExam ?? null; ?>
        <div style="border:1px solid var(--border);border-radius:10px;padding:20px 22px;margin-bottom:36px;background:#FFFFFF;">
          <div style="font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:var(--ink-faint);margin-bottom:14px;">Assigned assessment</div>
          <?php if ($dashboardExam): ?>
            <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;margin-bottom:18px;">
              <div>
                <div style="font-size:20px;font-weight:700;color:var(--navy);line-height:1.3;">Position name: <?= esc($dashboardExam['title']) ?></div>
                <div style="font-size:14px;color:var(--ink-muted);margin-top:5px;"><?= esc($applicant['name']) ?> · ID <?= esc($applicant['id']) ?></div>
              </div>
              <span style="font-size:11px;font-weight:700;padding:5px 8px;border-radius:999px;background:<?= $dashboardExam['display_status'] === 'Open' ? '#E8F4EE' : ($dashboardExam['display_status'] === 'Closed' ? '#FDECEC' : '#FDF3E3') ?>;color:<?= $dashboardExam['display_status'] === 'Open' ? '#0F7B4F' : ($dashboardExam['display_status'] === 'Closed' ? '#B3261E' : '#8A5A08') ?>;white-space:nowrap;"><?= esc($dashboardExam['display_status']) ?></span>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:1px;background:var(--border);border:1px solid var(--border);border-radius:8px;overflow:hidden;">
              <div style="background:#FFFFFF;padding:14px 16px;"><div style="font-size:11px;text-transform:uppercase;letter-spacing:0.05em;color:var(--ink-faint);">Start time</div><div style="font-size:14px;font-weight:600;color:var(--navy);margin-top:5px;"><?= $dashboardExam['start_at'] ? esc($dashboardExam['start_at']) : 'Available now' ?></div></div>
              <div style="background:#FFFFFF;padding:14px 16px;"><div style="font-size:11px;text-transform:uppercase;letter-spacing:0.05em;color:var(--ink-faint);">End time</div><div style="font-size:14px;font-weight:600;color:var(--navy);margin-top:5px;"><?= $dashboardExam['end_at'] ? esc($dashboardExam['end_at']) : 'No closing time' ?></div></div>
              <div style="background:#FFFFFF;padding:14px 16px;"><div style="font-size:11px;text-transform:uppercase;letter-spacing:0.05em;color:var(--ink-faint);">Submission status</div><div style="font-size:14px;font-weight:600;color:<?= $dashboardExam['has_submission'] ? '#0F7B4F' : '#8A5A08' ?>;margin-top:5px;"><?= $dashboardExam['has_submission'] ? 'Submitted' : 'Not submitted' ?></div></div>
              <?php if ($dashboardExam['has_submission']): ?>
                <div style="background:#FFFFFF;padding:14px 16px;"><div style="font-size:11px;text-transform:uppercase;letter-spacing:0.05em;color:var(--ink-faint);">Time taken</div><div style="font-size:14px;font-weight:600;color:var(--navy);margin-top:5px;"><?= sprintf('%02d:%02d', intdiv((int) $dashboardExam['time_used'], 60), (int) $dashboardExam['time_used'] % 60) ?></div></div>
                <div style="background:#FFFFFF;padding:14px 16px;"><div style="font-size:11px;text-transform:uppercase;letter-spacing:0.05em;color:var(--ink-faint);">Answered</div><div style="font-size:14px;font-weight:600;color:var(--navy);margin-top:5px;"><?= (int) $dashboardExam['answered_count'] ?> / <?= (int) $dashboardExam['total_count'] ?></div></div>
              <?php endif; ?>
            </div>
            <?php if ($dashboardExam['submitted_at']): ?><div style="font-size:12px;color:var(--ink-faint);margin-top:12px;">Submitted at <?= esc($dashboardExam['submitted_at']) ?></div><?php endif; ?>
            <?php if ($dashboardExam['can_start'] || $dashboardExam['can_edit']): ?><a href="<?= site_url('exam?exam_token=' . urlencode($dashboardExam['exam_token'])) ?>" style="display:inline-block;border-radius:8px;padding:10px 15px;background:var(--blue);color:#fff;font-size:13px;font-weight:600;margin-top:14px;"><?= $dashboardExam['can_edit'] ? 'Edit submission' : 'Start exam' ?></a><?php endif; ?>
          <?php else: ?>
            <div style="font-size:14px;color:var(--ink-muted);">No assessment is assigned to this applicant yet.</div>
          <?php endif; ?>
        </div>


        <?php if (false): ?>

        <?php if (! empty($submissionSummary)): ?><div style="border: 1px solid <?= ! empty($canEditSubmission) ? '#BFE0CE' : '#D7DEE8' ?>; background: <?= ! empty($canEditSubmission) ? '#F1FBF5' : '#F7F9FC' ?>; border-radius: 10px; padding: 18px 20px; margin-bottom: 24px;"><div style="font-size: 15px; font-weight: 700; margin-bottom: 6px;">Submission status: <?= ! empty($canEditSubmission) ? 'Submitted — edits are still open' : 'Submitted — editing is closed' ?></div><div style="font-size: 14px; color: var(--ink-muted); margin-bottom: 13px;">Reference <?= esc($submissionSummary['reference']) ?> · <?= esc($submissionSummary['submittedAt']) ?></div><?php if (! empty($canEditSubmission)): ?><a href="<?= site_url('exam') ?>" style="display:inline-block; background: var(--blue); color:#fff; border-radius:8px; padding:10px 15px; font-size:14px; font-weight:600;">Edit submission</a><?php else: ?><a href="<?= site_url('exam/submitted') ?>" style="display:inline-block; color:var(--blue); font-size:14px; font-weight:600;">View submission confirmation →</a><?php endif; ?></div><?php endif; ?>

        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- EXAM -->
  <div id="screenExam" class="hidden" style="flex: 1; display: flex; flex-direction: column; min-height: 100vh;">

    <header>
      <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
        <div class="brand-badge" style="width: 30px; height: 30px; font-size: 13px;">UIU</div>
        <div style="min-width: 0;">
          <div style="font-size: 15px; font-weight: 600; letter-spacing: -0.01em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= esc($applicant['position']) ?></div>
          <div style="font-size: 13px; color: var(--ink-faint); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= esc($applicant['name']) ?> &nbsp;·&nbsp; <span class="mono"><?= esc($applicant['id']) ?></span></div>
        </div>
      </div>

      <div style="margin-left: auto; display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--ink-muted); padding: 6px 12px; border: 1px solid var(--border); border-radius: 999px;">
          <span id="statusDot" style="width: 9px; height: 9px; border-radius: 50%; background: var(--green-ink); flex: none; display: inline-block;"></span>
          <span id="statusText">Connected</span>
        </div>
        <button id="fullscreenBtn" style="font-size: 13px; font-weight: 500; color: var(--ink-muted); background: #FFFFFF; border: 1px solid var(--border); border-radius: 8px; padding: 8px 14px; cursor: pointer; white-space: nowrap; flex: none;">Full screen</button>
        <div role="timer" aria-live="off" id="timerBox" style="display: flex; flex-direction: column; align-items: flex-end; gap: 3px; padding: 7px 16px; border-radius: 10px; min-width: 132px;">
          <span style="font-size: 11px; letter-spacing: 0.1em; text-transform: uppercase; opacity: 0.85;">Time left</span>
          <span id="timeText" class="mono" style="font-size: 24px; font-weight: 600; letter-spacing: 0.02em; line-height: 1;">45:00</span>
        </div>
      </div>
    </header>

    <div style="height: 4px; background: #EDF1F6;">
      <div id="progressBar" style="width: 0%; height: 100%; background: var(--blue); transition: width 240ms ease;"></div>
    </div>

    <div id="offlineBanner" class="hidden" role="status" style="background: var(--amber-warn-bg); border-bottom: 1px solid var(--amber-border); padding: 12px 24px; display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
      <span style="width: 10px; height: 10px; border-radius: 50%; background: var(--amber-dot); animation: pulseWarn 1.4s ease-in-out infinite; flex: none;"></span>
      <div style="font-size: 15px; font-weight: 600; color: var(--amber-ink-strong);">Connection lost — reconnecting</div>
      <div style="font-size: 14px; color: var(--amber-ink-strong); opacity: 0.9;">Your answers are being saved on this device and will sync automatically. Keep the exam open; the timer continues.</div>
      <button id="retryBtn" style="margin-left: auto; font-size: 14px; font-weight: 600; color: var(--amber-ink-strong); background: #FFFFFF; border: 1px solid #E0BE8C; border-radius: 8px; padding: 8px 16px; cursor: pointer;">Retry now</button>
    </div>

    <div style="flex: 1; display: flex; align-items: stretch; gap: 0; flex-wrap: wrap;">

      <main style="flex: 1 1 520px; min-width: 0; padding: 36px 40px 120px; display: flex; justify-content: center;">
        <div style="width: 100%; max-width: 760px;">
          <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 18px; flex-wrap: wrap;">
            <div id="questionCounter" class="mono" style="font-size: 13px; font-weight: 600; color: var(--blue); letter-spacing: 0.04em;"></div>
            <div style="width: 1px; height: 14px; background: #D7DEE8;"></div>
            <div id="typeLabel" style="font-size: 13px; color: var(--ink-faint);"></div>
            <div style="width: 1px; height: 14px; background: #D7DEE8;"></div>
            <div id="pointsLabel" style="font-size: 13px; color: var(--ink-faint);"></div>
          </div>

          <h2 id="promptText" style="margin: 0 0 10px; font-size: 26px; line-height: 1.35; font-weight: 600; letter-spacing: -0.015em;"></h2>
          <p id="hintText" style="margin: 0 0 28px; font-size: 15px; color: var(--ink-faint); line-height: 1.55;"></p>
          <div id="attachmentInfo" class="hidden" style="margin: -12px 0 24px; padding: 13px 16px; border: 1px solid #D7E4F0; border-radius: 9px; background: #F5FAFF; font-size: 14px; color: #274B68;"></div>

          <div id="choiceBlock" class="hidden">
            <div id="optionsList" role="group" style="display: flex; flex-direction: column; gap: 12px;"></div>
          </div>

          <div id="writtenBlock" class="hidden">
            <textarea id="textAnswer" placeholder="Type your answer here." aria-label="Written answer" style="width: 100%; min-height: 220px; resize: vertical; padding: 18px 20px; font-family: 'Public Sans', sans-serif; font-size: 17px; line-height: 1.6; color: var(--navy); border: 1px solid var(--border-input); border-radius: 10px; background: #FFFFFF;"></textarea>
            <div style="display: flex; justify-content: space-between; gap: 16px; margin-top: 10px; font-size: 13px; color: var(--ink-faint);">
              <span>Suggested length: 80–150 words. Plain text only.</span>
              <span id="wordCount" class="mono">0 words</span>
            </div>
          </div>

          <div id="uploadBlock" class="hidden">
            <div id="uploadHasFile" class="hidden" style="display: flex; align-items: center; gap: 16px; padding: 20px; border: 1px solid var(--border-input); border-radius: 10px; background: #FFFFFF; flex-wrap: wrap;">
              <div id="uploadFileIcon" style="flex: none; width: 44px; height: 52px; border: 1px solid var(--border-input); border-radius: 6px; display: flex; align-items: center; justify-content: center; font-family: 'IBM Plex Mono', monospace; font-size: 11px; font-weight: 600; color: var(--red-strong); background: #FDF6F6;">FILE</div>
              <div style="min-width: 0; flex: 1 1 200px;">
                <div id="fileName" style="font-size: 17px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"></div>
                <div id="fileMeta" style="font-size: 14px; color: var(--ink-faint); margin-top: 4px;"></div>
                <div id="uploadStatus" class="mono" style="font-size: 13px; font-weight: 600; margin-top: 2px;"></div>
              </div>
              <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <label style="font-size: 15px; font-weight: 600; color: var(--blue); background: #FFFFFF; border: 1px solid var(--border-input); border-radius: 8px; padding: 11px 18px; cursor: pointer; white-space: nowrap;">
                  Replace file
                  <input id="replaceFileInput" type="file" style="display: none;">
                </label>
                <button id="removeFileBtn" style="font-size: 15px; font-weight: 600; color: var(--ink-muted); background: #FFFFFF; border: 1px solid var(--border-input); border-radius: 8px; padding: 11px 18px; cursor: pointer; white-space: nowrap;">Remove</button>
              </div>
            </div>

            <label id="dropZone" class="dropzone hidden">
              <div id="uploadPrompt" style="font-size: 18px; font-weight: 600; color: var(--navy);">Drop your file here, or choose a file</div>
              <div id="uploadRules" style="font-size: 15px; color: var(--ink-faint);">Allowed file types · maximum 10 MB · one file</div>
              <span style="margin-top: 10px; font-size: 16px; font-weight: 600; color: #FFFFFF; background: var(--blue); border-radius: 10px; padding: 13px 24px;">Choose file</span>
                  <input id="fileInput" type="file" style="display: none;">
            </label>

            <div id="uploadErrorBox" class="hidden" role="alert" style="margin-top: 14px; background: var(--red-bg); border: 1px solid var(--red-border); border-radius: 10px; padding: 14px 18px; font-size: 15px; font-weight: 600; color: var(--red-ink);"></div>
          </div>
        </div>
      </main>

      <aside style="flex: 0 0 300px; border-left: 1px solid var(--border); background: var(--bg-aside); padding: 28px 24px 120px;">
        <div style="font-size: 13px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--ink-faint); margin-bottom: 16px;">Question navigator</div>

        <div id="navGrid" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-bottom: 22px;"></div>

        <div style="display: flex; flex-direction: column; gap: 10px; padding: 16px 0 20px; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); margin-bottom: 20px;">
          <div style="display: flex; align-items: center; gap: 10px; font-size: 14px; color: var(--ink-muted);">
            <span style="width: 16px; height: 16px; border-radius: 4px; background: var(--blue); flex: none;"></span>Answered <span id="answeredCount" class="mono" style="margin-left: auto; font-weight: 600; color: var(--navy);">0</span>
          </div>
          <div style="display: flex; align-items: center; gap: 10px; font-size: 14px; color: var(--ink-muted);">
            <span style="width: 16px; height: 16px; border-radius: 4px; background: #FFFFFF; border: 1px solid var(--border-input); flex: none;"></span>Unanswered <span id="unansweredCount" class="mono" style="margin-left: auto; font-weight: 600; color: var(--navy);">0</span>
          </div>
          <div style="display: flex; align-items: center; gap: 10px; font-size: 14px; color: var(--ink-muted);">
            <span style="width: 16px; height: 16px; border-radius: 4px; background: #FFFFFF; border: 2px solid var(--navy); flex: none;"></span>Current question
          </div>
        </div>

        <div id="progressSentence" style="font-size: 14px; color: var(--ink-muted); line-height: 1.55; margin-bottom: 18px;"></div>

        <button id="openSubmitBtn" style="width: 100%; padding: 14px 18px; font-size: 16px; font-weight: 600; color: #FFFFFF; background: var(--navy); border: none; border-radius: 10px; cursor: pointer;">Submit exam</button>

        <div style="margin-top: 20px; font-size: 13px; color: var(--ink-faint); line-height: 1.6;">
          <div style="font-weight: 600; color: var(--ink-muted); margin-bottom: 6px;">Keyboard shortcuts</div>
          <div><span class="mono">←</span> / <span class="mono">→</span> previous / next</div>
          <div><span class="mono">1–4</span> choose an option</div>
        </div>
      </aside>
    </div>

    <div style="position: sticky; bottom: 0; background: #FFFFFF; border-top: 1px solid var(--border); padding: 14px 24px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; z-index: 20;">
      <button id="prevBtn" class="btn" style="background: #FFFFFF;">Previous</button>
      <div style="margin-left: auto; display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
        <div id="saveLine" style="font-size: 13px; color: var(--ink-faint);">All answers saved</div>
        <button id="nextBtn" class="btn" style="background: var(--blue); color: #FFFFFF; border: none;">Save &amp; next</button>
      </div>
    </div>
  </div>

  <!-- EXPIRED -->
  <div id="screenExpired" class="hidden" style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 48px 28px; background: #FFFFFF;">
    <div style="max-width: 560px; text-align: center;">
      <div class="mono" style="width: 64px; height: 64px; border-radius: 50%; border: 3px solid var(--red-deep); margin: 0 auto 26px; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 600; color: var(--red-deep);">0:00</div>
      <h1 style="margin: 0 0 14px; font-size: 32px; font-weight: 700; letter-spacing: -0.02em;">Exam time has ended</h1>
      <p style="margin: 0 0 30px; font-size: 17px; line-height: 1.6; color: var(--ink-muted);">Your exam is being submitted automatically. All saved answers are included, and further editing is disabled. Please do not close this window.</p>
      <div style="height: 6px; border-radius: 999px; background: #EDF1F6; overflow: hidden; margin-bottom: 14px;">
        <div id="autoSubmitBar" style="width: 0%; height: 100%; background: var(--red-deep); transition: width 1.2s ease;"></div>
      </div>
      <div id="autoSubmitStatus" class="mono" style="font-size: 13px; color: var(--ink-faint); letter-spacing: 0.04em;">Uploading answers…</div>
    </div>
  </div>

  <!-- SUCCESS -->
  <div id="screenSuccess" class="hidden" style="flex: 1; display: flex; flex-direction: column;">
    <div style="border-bottom: 1px solid var(--border); padding: 18px 28px; display: flex; align-items: center; gap: 14px;">
      <div class="brand-badge">UIU</div>
      <div style="font-size: 16px; font-weight: 600;">UIU Recruitment Portal</div>
    </div>
    <div style="flex: 1; display: flex; justify-content: center; padding: 64px 28px;">
      <div style="width: 100%; max-width: 680px;">
        <div style="width: 52px; height: 52px; border-radius: 50%; background: var(--green-bg); border: 1px solid var(--green-border); display: flex; align-items: center; justify-content: center; margin-bottom: 26px;">
          <span style="color: var(--green-ink); font-size: 24px; line-height: 1;">✓</span>
        </div>
        <h1 style="margin: 0 0 14px; font-size: 34px; font-weight: 700; letter-spacing: -0.025em;">Your exam has been submitted</h1>
        <p style="margin: 0 0 36px; font-size: 17px; line-height: 1.6; color: var(--ink-muted); max-width: 58ch;">Thank you, <?= esc(explode(' ', $applicant['name'])[0]) ?>. Your responses are recorded. The hiring team at UIU Recruitment Portal will be in touch within five working days.</p>

        <div style="border: 1px solid var(--border); border-radius: 10px; overflow: hidden; margin-bottom: 32px;">
          <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1px; background: var(--border);">
            <div style="background: #FFFFFF; padding: 20px;">
              <div style="font-size: 12px; letter-spacing: 0.06em; text-transform: uppercase; color: var(--ink-faint); margin-bottom: 6px;">Reference number</div>
              <div id="referenceNumber" class="mono" style="font-size: 18px; font-weight: 600;"></div>
            </div>
            <div style="background: #FFFFFF; padding: 20px;">
              <div style="font-size: 12px; letter-spacing: 0.06em; text-transform: uppercase; color: var(--ink-faint); margin-bottom: 6px;">Submitted at</div>
              <div id="submittedAt" style="font-size: 18px; font-weight: 600;"></div>
            </div>
            <div style="background: #FFFFFF; padding: 20px;">
              <div style="font-size: 12px; letter-spacing: 0.06em; text-transform: uppercase; color: var(--ink-faint); margin-bottom: 6px;">Questions answered</div>
              <div id="finalAnswered" style="font-size: 18px; font-weight: 600;"></div>
            </div>
            <div style="background: #FFFFFF; padding: 20px;">
              <div style="font-size: 12px; letter-spacing: 0.06em; text-transform: uppercase; color: var(--ink-faint); margin-bottom: 6px;">Time used</div>
              <div id="timeUsedLabel" class="mono" style="font-size: 18px; font-weight: 600;"></div>
            </div>
          </div>
        </div>

        <p style="margin: 0 0 28px; font-size: 15px; line-height: 1.6; color: var(--ink-faint); max-width: 62ch;">A copy of this confirmation has been sent to <?= esc($applicant['email']) ?>. Keep the reference number for any correspondence about your application. You may now close this window.</p>
        <button id="editSubmissionBtn" style="font-size: 15px; font-weight: 600; color: #FFFFFF; background: var(--blue); border: none; border-radius: 10px; padding: 12px 20px; cursor: pointer; white-space: nowrap;">Edit submission</button>
        <button id="restartBtn" style="font-size: 15px; font-weight: 600; color: var(--blue); background: #FFFFFF; border: 1px solid var(--border-input); border-radius: 10px; padding: 12px 20px; cursor: pointer; white-space: nowrap;">Return to dashboard</button>
      </div>
    </div>
  </div>

  <!-- SUBMIT MODAL -->
  <div id="submitModal" class="modal-backdrop hidden" role="dialog" aria-modal="true">
    <div class="modal-card" style="max-width: 520px;">
      <h2 style="margin: 0 0 12px; font-size: 24px; font-weight: 700; letter-spacing: -0.02em;">Submit your exam?</h2>
      <p style="margin: 0 0 22px; font-size: 16px; line-height: 1.6; color: var(--ink-muted);">Once submitted you cannot return to your answers or change them.</p>

      <div style="border: 1px solid var(--border); border-radius: 10px; padding: 18px 20px; display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; font-size: 15px; color: var(--ink-muted);">Answered <span id="modalAnswered" class="mono" style="font-weight: 600; color: var(--navy);"></span></div>
        <div style="display: flex; justify-content: space-between; font-size: 15px; color: var(--ink-muted);">Unanswered <span id="modalUnanswered" class="mono" style="font-weight: 600;"></span></div>
        <div style="display: flex; justify-content: space-between; font-size: 15px; color: var(--ink-muted);">Time remaining <span id="modalTime" class="mono" style="font-weight: 600; color: var(--navy);"></span></div>
      </div>

      <div id="unansweredWarning" class="hidden" style="background: var(--red-bg); border: 1px solid var(--red-border); border-radius: 10px; padding: 16px 18px; margin-bottom: 22px;">
        <div id="unansweredHeadline" style="font-size: 15px; font-weight: 700; color: var(--red-ink); margin-bottom: 6px;"></div>
        <div style="font-size: 15px; line-height: 1.55; color: #8A2020;">Unanswered questions are scored as zero. You still have time to go back.</div>
      </div>

      <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <button id="keepWorkingBtn" style="flex: 1 1 160px; padding: 14px 18px; font-size: 16px; font-weight: 600; color: var(--navy); background: #FFFFFF; border: 1px solid var(--border-input); border-radius: 10px; cursor: pointer;">Keep working</button>
        <button id="confirmSubmitBtn" style="flex: 1 1 160px; padding: 14px 18px; font-size: 16px; font-weight: 600; color: #FFFFFF; background: var(--blue); border: none; border-radius: 10px; cursor: pointer;">Submit exam</button>
      </div>
    </div>
  </div>

</div>

<script>
const APPLICANT = <?= json_encode($applicant) ?>;
const EXAM_ID = <?= (int) ($examId ?? 0) ?>;
const TOTAL_SECONDS = <?= (int) $totalSeconds ?>;
const INITIAL_SECONDS = <?= (int) ($secondsRemaining ?? 0) ?>;
const UPLOAD_URL = <?= json_encode(site_url('exam/upload')) ?>;
const AUTOSAVE_URL = <?= json_encode(site_url('exam/autosave')) ?>;
const SUBMIT_URL = <?= json_encode(site_url('exam/submit')) ?>;

const QUESTIONS = <?= json_encode($questions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const SAVED_ANSWERS = <?= json_encode($savedAnswers ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const CAN_EDIT_SUBMISSION = <?= !empty($canEditSubmission) ? 'true' : 'false' ?>;
const SUBMITTED_URL = <?= json_encode(site_url('exam/submitted')) ?>;
const DRAFT_KEY = "exam-draft-" + APPLICANT.id + "-" + EXAM_ID;
let LOCAL_DRAFT = {};
try { LOCAL_DRAFT = JSON.parse(localStorage.getItem(DRAFT_KEY) || "{}"); } catch (e) { LOCAL_DRAFT = {}; }
const RESTORED_INDEX = Math.min(
  Math.max(Number(LOCAL_DRAFT.index) || 0, 0),
  Math.max(QUESTIONS.length - 1, 0)
);

const state = {
  screen: <?= json_encode($initialScreen ?? 'instructions') ?>,
  index: RESTORED_INDEX,
  answers: Object.assign({}, SAVED_ANSWERS || {}, LOCAL_DRAFT.answers || {}),
  secondsLeft: INITIAL_SECONDS,
  offline: !navigator.onLine,
  saveState: "saved",
  showSubmitModal: false,
  fullscreen: false,
  autoSubmitStep: 0,
  submittedAt: <?= json_encode($submissionSummary['submittedAt'] ?? null) ?>,
  finalReference: <?= json_encode($submissionSummary['reference'] ?? null) ?>,
  timeUsed: <?= json_encode($submissionSummary['timeUsed'] ?? 0) ?>,
  uploading: false,
  uploadError: null,
  dragActive: false,
  isSubmitting: false
};

const el = (id) => document.getElementById(id);

function fmt(sec) {
  const m = Math.floor(sec / 60), s = sec % 60;
  return String(m).padStart(2, "0") + ":" + String(s).padStart(2, "0");
}

function isAnswered(q) {
  const v = state.answers[q.id];
  if (v === undefined || v === null) return false;
  if (Array.isArray(v)) return v.length > 0;
  if (typeof v === "object") return !!v.name;
  return String(v).trim().length > 0;
}

function allowedFileTypes(q) {
  const types = Array.isArray(q.allowedFileTypes) && q.allowedFileTypes.length ? q.allowedFileTypes : ["pdf"];
  return types.indexOf("all") >= 0 ? ["all"] : types;
}

function allowedFileLabel(q) {
  const types = allowedFileTypes(q);
  if (types.indexOf("all") >= 0) return "All file types";
  return types.map((type) => type === "excel" ? "Excel" : "PDF").join(" or ");
}

function fileAccept(q) {
  const types = allowedFileTypes(q);
  if (types.indexOf("all") >= 0) return "";
  const accepts = [];
  if (types.indexOf("pdf") >= 0) accepts.push(".pdf", "application/pdf");
  if (types.indexOf("excel") >= 0) accepts.push(".xls", ".xlsx", "application/vnd.ms-excel", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
  return accepts.join(",");
}

function isAllowedFile(q, file) {
  const types = allowedFileTypes(q);
  if (types.indexOf("all") >= 0) return true;
  const extension = String(file.name || "").split(".").pop().toLowerCase();
  return (types.indexOf("pdf") >= 0 && extension === "pdf") || (types.indexOf("excel") >= 0 && ["xls", "xlsx"].indexOf(extension) >= 0);
}

function cacheDraft() {
  if (!EXAM_ID) return;
  localStorage.setItem(DRAFT_KEY, JSON.stringify({ index: state.index, answers: state.answers, updatedAt: Date.now() }));
}

async function saveDraft() {
  if (!EXAM_ID || state.screen !== "exam") return;
  cacheDraft();
  if (!navigator.onLine) {
    state.offline = true;
    state.saveState = "queued";
    render();
    return;
  }
  try {
    const res = await fetch(AUTOSAVE_URL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ examId: EXAM_ID, answers: state.answers })
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || "Autosave failed.");
    state.offline = false;
    state.saveState = "saved";
    localStorage.setItem(DRAFT_KEY, JSON.stringify({ index: state.index, updatedAt: Date.now() }));
  } catch (err) {
    state.saveState = "queued";
  }
  render();
}

function markSaving() {
  state.saveState = "saving";
  cacheDraft();
  clearTimeout(markSaving._t);
  markSaving._t = setTimeout(saveDraft, 700);
  render();
}

function select(q, key) {
  const answers = Object.assign({}, state.answers);
  if (q.type === "multi") {
    const cur = Array.isArray(answers[q.id]) ? answers[q.id].slice() : [];
    const i = cur.indexOf(key);
    if (i >= 0) cur.splice(i, 1); else cur.push(key);
    answers[q.id] = cur;
  } else {
    answers[q.id] = key;
  }
  state.answers = answers;
  markSaving();
}

function go(i) {
  state.index = Math.min(QUESTIONS.length - 1, Math.max(0, i));
  cacheDraft();
  render();
}

async function uploadFile(q, file) {
  if (!isAllowedFile(q, file)) { state.uploadError = "This question accepts " + allowedFileLabel(q) + " files only."; render(); return; }
  if (file.size > 10 * 1024 * 1024) { state.uploadError = "That file is larger than 10 MB. Please upload a smaller file."; render(); return; }

  state.uploadError = null;
  state.uploading = true;
  render();

  const form = new FormData();
  form.append("file", file);
  form.append("exam_id", String(EXAM_ID));
  form.append("question_id", String(q.id).replace(/^q/, ""));

  try {
    const res = await fetch(UPLOAD_URL, { method: "POST", body: form });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || "Upload failed.");
    const answers = Object.assign({}, state.answers);
    answers[q.id] = { name: data.name, size: data.size, storedName: data.storedName };
    state.answers = answers;
    markSaving();
  } catch (err) {
    state.uploadError = err.message || "Upload failed. Please try again.";
  } finally {
    state.uploading = false;
    render();
  }
}

function removeFile(q) {
  const answers = Object.assign({}, state.answers);
  delete answers[q.id];
  state.answers = answers;
  state.uploadError = null;
  markSaving();
}

function beginAutoSubmit() {
  setTimeout(() => { state.autoSubmitStep = 1; render(); }, 900);
  setTimeout(() => { finish(); }, 2600);
}

async function finish() {
  state.showSubmitModal = false;
  state.isSubmitting = true;
  const timeEnded = state.screen === "expired" || state.secondsLeft === 0;
  const answeredCount = QUESTIONS.filter(isAnswered).length;
  const timeUsedSeconds = state.timeUsed || (TOTAL_SECONDS - state.secondsLeft);

  const payload = {
    examId: EXAM_ID,
    autoSubmit: timeEnded,
    answers: Object.fromEntries(Object.entries(state.answers).map(([k, v]) => {
      if (v && typeof v === "object" && v.storedName) return [k, { file: v.storedName, name: v.name }];
      return [k, v];
    })),
    answeredCount,
    totalCount: QUESTIONS.length,
    timeUsedSeconds
  };

  try {
    const res = await fetch(SUBMIT_URL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (!res.ok) {
      if (timeEnded) {
        state.isSubmitting = false;
        state.screen = "expired";
        state.autoSubmitStep = 2;
        render();
        setTimeout(() => finish(), 3000);
        return;
      }
      state.isSubmitting = false;
      state.screen = "exam";
      render();
      alert(data.error || "This assessment is no longer accepting changes.");
      return;
    }
    localStorage.removeItem(DRAFT_KEY);
    window.location.href = SUBMITTED_URL;
    return;
  } catch (err) {
    state.isSubmitting = false;
    if (timeEnded) {
      state.screen = "expired";
      state.autoSubmitStep = 2;
      render();
      setTimeout(() => finish(), 3000);
      return;
    }
    state.screen = "exam";
    cacheDraft();
    render();
    alert("Submission could not reach the server. Your answers are saved on this device; please reconnect and submit again.");
  }
}

function render() {
  el("screenInstructions").classList.toggle("hidden", state.screen !== "instructions");
  el("screenExam").classList.toggle("hidden", state.screen !== "exam");
  el("screenExpired").classList.toggle("hidden", state.screen !== "expired");
  el("screenSuccess").classList.toggle("hidden", state.screen !== "success");

  // Instructions

  if (state.screen !== "exam") return;

  if (!QUESTIONS.length) {
    window.location.href = <?= json_encode(site_url('dashboard?error=' . rawurlencode('This assessment has no questions yet.'))) ?>;
    return;
  }

  const q = QUESTIONS[state.index];
  const answeredCount = QUESTIONS.filter(isAnswered).length;
  const unansweredCount = QUESTIONS.length - answeredCount;
  const pct = Math.round((answeredCount / QUESTIONS.length) * 100);
  const secs = state.secondsLeft;
  const critical = secs < 300, low = secs < 600;
  const timerInk = critical ? "#B3261E" : low ? "#A85A06" : "#0F7B4F";
  const timerBg = critical ? "#FDECEC" : low ? "#FDF3E3" : "#E8F4EE";
  const timerBorder = critical ? "#F3C4C4" : low ? "#F0D9B5" : "#BFE0CE";

  el("statusText").textContent = state.offline ? "Offline — saving locally" : "Connected";
  el("statusDot").style.background = state.offline ? "#C2620A" : "#0F7B4F";
  el("offlineBanner").classList.toggle("hidden", !state.offline);
  el("fullscreenBtn").textContent = state.fullscreen ? "Exit full screen" : "Full screen";

  el("timeText").textContent = fmt(secs);
  el("timerBox").style.background = timerBg;
  el("timerBox").style.border = "1px solid " + timerBorder;
  el("timerBox").style.color = timerInk;

  el("progressBar").style.width = pct + "%";

  el("questionCounter").textContent = "Question " + (state.index + 1) + " of " + QUESTIONS.length;
  el("typeLabel").textContent = q.type === "single" ? "Multiple choice — one answer" : q.type === "multi" ? "Multiple choice — select all that apply" : q.type === "bool" ? "True or false" : q.type === "upload" ? "File upload — " + allowedFileLabel(q) : "Written answer";
  el("pointsLabel").textContent = q.points + (q.points === 1 ? " point" : " points");
  el("promptText").textContent = q.prompt;
  el("hintText").textContent = q.hint;
  const attachmentInfo = el("attachmentInfo");
  const attachments = q.attachments || (q.attachmentName ? [{name: q.attachmentName, url: q.attachmentUrl}] : []);
  attachmentInfo.classList.toggle("hidden", attachments.length === 0);
  attachmentInfo.innerHTML = "";
  if (attachments.length) {
    const attachmentTitle = document.createElement("span");
    attachmentTitle.textContent = "Question attachments:";
    attachmentTitle.style.cssText = "font-weight:600;display:block;margin-bottom:7px;";
    attachmentInfo.appendChild(attachmentTitle);
    const attachmentTable = document.createElement("table");
    attachmentTable.style.cssText = "width:100%;border-collapse:collapse;font-size:13px;";
    const tableHead = document.createElement("thead");
    tableHead.innerHTML = "<tr><th style=\"width:48px;text-align:left;padding:7px 6px;border-bottom:1px solid #D7E4F0;\">SL</th><th style=\"text-align:left;padding:7px 6px;border-bottom:1px solid #D7E4F0;\">File name</th><th style=\"text-align:right;padding:7px 6px;border-bottom:1px solid #D7E4F0;\">Action</th></tr>";
    attachmentTable.appendChild(tableHead);
    const tableBody = document.createElement("tbody");
    attachments.forEach((attachment, attachmentIndex) => {
      const row = document.createElement("tr");
      const serial = document.createElement("td");
      serial.textContent = attachmentIndex + 1;
      serial.style.cssText = "padding:8px 6px;border-bottom:1px solid #E5EDF5;";
      const fileName = document.createElement("td");
      fileName.textContent = attachment.name;
      fileName.style.cssText = "padding:8px 6px;border-bottom:1px solid #E5EDF5;word-break:break-word;";
      const action = document.createElement("td");
      action.style.cssText = "padding:8px 6px;border-bottom:1px solid #E5EDF5;text-align:right;";
      const link = document.createElement("a");
      link.href = attachment.url;
      link.target = "_blank";
      link.rel = "noopener";
      link.textContent = "Download";
      link.style.cssText = "color:#1769AA;font-weight:600;";
      action.appendChild(link);
      row.append(serial, fileName, action);
      tableBody.appendChild(row);
    });
    attachmentTable.appendChild(tableBody);
    attachmentInfo.appendChild(attachmentTable);
  }

  const isChoice = q.type !== "written" && q.type !== "upload";
  el("choiceBlock").classList.toggle("hidden", !isChoice);
  el("writtenBlock").classList.toggle("hidden", q.type !== "written");
  el("uploadBlock").classList.toggle("hidden", q.type !== "upload");

  if (isChoice) {
    const answerVal = state.answers[q.id];
    const optionsList = el("optionsList");
    optionsList.innerHTML = "";
    q.options.forEach((o) => {
      const selected = q.type === "multi" ? (Array.isArray(answerVal) && answerVal.indexOf(o.key) >= 0) : answerVal === o.key;
      const btn = document.createElement("button");
      btn.className = "option-btn" + (selected ? " selected" : "");
      btn.setAttribute("aria-pressed", selected ? "true" : "false");
      btn.innerHTML = '<span class="option-marker' + (selected ? " selected" : "") + '">' + o.key + '</span><span style="flex:1;font-size:17px;line-height:1.5;color:#0B1F3A;">' + o.text + '</span>';
      btn.addEventListener("click", () => select(q, o.key));
      optionsList.appendChild(btn);
    });
  }

  if (q.type === "written") {
    const v = typeof state.answers[q.id] === "string" ? state.answers[q.id] : "";
    if (el("textAnswer").value !== v) el("textAnswer").value = v;
    const words = v.trim().split(/\s+/).filter(Boolean).length;
    el("wordCount").textContent = words + (words === 1 ? " word" : " words");
  }

  if (q.type === "upload") {
    const fileLabel = allowedFileLabel(q);
    const accept = fileAccept(q);
    ["fileInput", "replaceFileInput"].forEach((id) => { const input = el(id); if (accept) input.setAttribute("accept", accept); else input.removeAttribute("accept"); });
    el("uploadFileIcon").textContent = fileLabel === "All file types" ? "FILE" : fileLabel === "Excel" ? "XLS" : "PDF";
    el("uploadPrompt").textContent = "Drop your file here, or choose a file";
    el("uploadRules").textContent = fileLabel + " · maximum 10 MB · one file";
    const answerVal = state.answers[q.id];
    const hasFile = !!(answerVal && answerVal.name);
    el("uploadHasFile").classList.toggle("hidden", !hasFile);
    el("dropZone").classList.toggle("hidden", hasFile);
    if (hasFile) {
      el("fileName").textContent = answerVal.name;
      el("fileMeta").textContent = (answerVal.size || "") + " · PDF";
      const statusText = state.uploading ? "Uploading…" : (state.offline ? "Stored on this device — will upload when you reconnect" : "Uploaded and saved");
      el("uploadStatus").textContent = statusText;
      el("uploadStatus").style.color = state.uploading ? "#44536B" : (state.offline ? "#8A4708" : "#0F7B4F");
    }
    if (hasFile) el("fileMeta").textContent = (answerVal.size || "") + " · " + fileLabel;
    el("dropZone").classList.toggle("drag-active", state.dragActive);
    el("uploadErrorBox").classList.toggle("hidden", !state.uploadError);
    el("uploadErrorBox").textContent = state.uploadError || "";
  }

  // Navigator
  const navGrid = el("navGrid");
  navGrid.innerHTML = "";
  QUESTIONS.forEach((x, i) => {
    const answered = isAnswered(x);
    const current = i === state.index;
    const btn = document.createElement("button");
    btn.className = "nav-btn" + (answered ? " answered" : "") + (current ? " current" : "");
    btn.textContent = String(i + 1);
    btn.setAttribute("aria-label", "Question " + (i + 1) + (answered ? ", answered" : ", unanswered"));
    btn.setAttribute("aria-current", current ? "true" : "false");
    btn.addEventListener("click", () => go(i));
    navGrid.appendChild(btn);
  });

  el("answeredCount").textContent = answeredCount;
  el("unansweredCount").textContent = unansweredCount;
  el("progressSentence").textContent = answeredCount + " of " + QUESTIONS.length + " questions answered (" + pct + "%).";

  const last = state.index === QUESTIONS.length - 1;
  el("prevBtn").disabled = state.index === 0;
  el("prevBtn").style.color = state.index === 0 ? "#A9B4C4" : "#0B1F3A";
  el("prevBtn").style.border = "1px solid " + (state.index === 0 ? "#E7EBF1" : "#C9D3E0");
  el("prevBtn").style.cursor = state.index === 0 ? "not-allowed" : "pointer";

  el("nextBtn").textContent = last ? "Save & review" : "Save & next";


  el("saveLine").textContent = state.offline ? "Saved on this device" : state.saveState === "saving" ? "Saving…" : "All answers saved";

  // Submit modal
  el("submitModal").classList.toggle("hidden", !state.showSubmitModal);
  el("modalAnswered").textContent = answeredCount + " of " + QUESTIONS.length;
  el("modalUnanswered").textContent = unansweredCount;
  el("modalUnanswered").style.color = unansweredCount > 0 ? "#B3261E" : "#0B1F3A";
  el("modalTime").textContent = fmt(secs);
  el("unansweredWarning").classList.toggle("hidden", unansweredCount === 0);
  el("unansweredHeadline").textContent = unansweredCount === 1 ? "1 question has no answer" : unansweredCount + " questions have no answer";

}

function renderExpired() {
  el("autoSubmitBar").style.width = state.autoSubmitStep === 0 ? "45%" : "100%";
  if (state.autoSubmitStep === 2) {
    el("autoSubmitStatus").textContent = "Connection issue - retrying automatically";
    return;
  }
  el("autoSubmitStatus").textContent = state.autoSubmitStep === 0 ? "Uploading answers…" : "Answers received — finalising";
}

function renderSuccess() {
  el("editSubmissionBtn").classList.toggle("hidden", !CAN_EDIT_SUBMISSION);
  el("referenceNumber").textContent = state.finalReference || "";
  el("submittedAt").textContent = state.submittedAt || "";
  const answeredCount = QUESTIONS.filter(isAnswered).length;
  el("finalAnswered").textContent = answeredCount + " of " + QUESTIONS.length;
  el("timeUsedLabel").textContent = fmt(state.timeUsed) + " of " + fmt(TOTAL_SECONDS);
}

const origRender = render;
render = function () {
  origRender();
  if (state.screen === "expired") renderExpired();
  if (state.screen === "success") renderSuccess();
};

// --- Event wiring ---

el("editSubmissionBtn").addEventListener("click", () => { state.screen = "exam"; state.showSubmitModal = false; render(); });

el("fullscreenBtn").addEventListener("click", () => {
  const doc = document.documentElement;
  if (!document.fullscreenElement && doc.requestFullscreen) doc.requestFullscreen().catch(() => {});
  else if (document.exitFullscreen) document.exitFullscreen().catch(() => {});
  state.fullscreen = !state.fullscreen;
  render();
});

el("retryBtn").addEventListener("click", () => { state.offline = !navigator.onLine; if (state.offline) { state.saveState = "queued"; render(); } else { saveDraft(); } });

el("textAnswer").addEventListener("input", (e) => {
  const q = QUESTIONS[state.index];
  const answers = Object.assign({}, state.answers);
  answers[q.id] = e.target.value;
  state.answers = answers;
  markSaving();
});

el("fileInput").addEventListener("change", (e) => { const f = e.target.files && e.target.files[0]; if (f) uploadFile(QUESTIONS[state.index], f); e.target.value = ""; });
el("replaceFileInput").addEventListener("change", (e) => { const f = e.target.files && e.target.files[0]; if (f) uploadFile(QUESTIONS[state.index], f); e.target.value = ""; });
el("removeFileBtn").addEventListener("click", () => removeFile(QUESTIONS[state.index]));

el("dropZone").addEventListener("dragover", (e) => { e.preventDefault(); if (!state.dragActive) { state.dragActive = true; render(); } });
el("dropZone").addEventListener("dragleave", (e) => { e.preventDefault(); state.dragActive = false; render(); });
el("dropZone").addEventListener("drop", (e) => {
  e.preventDefault();
  state.dragActive = false;
  const f = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
  if (f) uploadFile(QUESTIONS[state.index], f);
  render();
});

el("prevBtn").addEventListener("click", () => go(state.index - 1));
el("nextBtn").addEventListener("click", () => {
  if (state.index === QUESTIONS.length - 1) { state.showSubmitModal = true; render(); }
  else go(state.index + 1);
});

el("openSubmitBtn").addEventListener("click", () => { state.showSubmitModal = true; render(); });
el("keepWorkingBtn").addEventListener("click", () => { state.showSubmitModal = false; render(); });
el("confirmSubmitBtn").addEventListener("click", () => finish());

el("restartBtn").addEventListener("click", () => { window.location.href = <?= json_encode(site_url('exam/dashboard')) ?>; });

document.addEventListener("keydown", (e) => {
  if (state.screen !== "exam" || state.showSubmitModal) return;
  const tag = (e.target && e.target.tagName) || "";
  if (tag === "TEXTAREA" || tag === "INPUT") return;
  if (e.key === "ArrowLeft") go(state.index - 1);
  else if (e.key === "ArrowRight") go(state.index + 1);
  else if (/^[1-4]$/.test(e.key)) {
    const q = QUESTIONS[state.index];
    if (q.options && q.options[+e.key - 1]) select(q, q.options[+e.key - 1].key);
  }
});

window.addEventListener("beforeunload", (e) => {
  if (state.screen !== "exam" || state.isSubmitting) return;
  e.preventDefault();
  e.returnValue = "";
});

window.addEventListener("offline", () => { state.offline = true; state.saveState = "queued"; cacheDraft(); render(); });
window.addEventListener("online", () => { state.offline = false; saveDraft(); });

setInterval(() => {
  if (state.screen !== "exam") return;
  const next = Math.max(0, state.secondsLeft - 1);
  if (next === 0) {
    state.secondsLeft = 0;
    state.screen = "expired";
    state.showSubmitModal = false;
    state.timeUsed = TOTAL_SECONDS;
    render();
    beginAutoSubmit();
    return;
  }
  state.secondsLeft = next;
  render();
}, 1000);

render();
if (state.screen === "expired" && !state.finalReference) beginAutoSubmit();
</script>
</body>
</html>
