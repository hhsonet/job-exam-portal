<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in | UIU Recruitment Portal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,400;0,500;0,600;0,700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root {
    --blue: #1A56DB; --blue-dark: #123FA5; --navy: #0B1F3A;
    --ink-muted: #44536B; --ink-faint: #5B6B84;
    --border: #E2E6ED; --border-input: #C9D3E0;
    --bg-aside: #FBFCFE;
    --red-bg: #FDECEC; --red-border: #F3C4C4; --red-ink: #A31D1D;
    --green-ink: #0F7B4F;
  }
  html, body { margin: 0; padding: 0; background: #FFFFFF; color: var(--navy); font-family: 'Public Sans', Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; }
  * { box-sizing: border-box; }
  button, input { font-family: inherit; }
  a { color: var(--blue); }
  a:hover { color: var(--blue-dark); }
  :focus-visible { outline: 3px solid var(--blue); outline-offset: 2px; }
  .mono { font-family: 'IBM Plex Mono', monospace; }
  .hidden { display: none !important; }
  .field-input { width: 100%; height: 56px; padding: 0 16px; font-size: 17px; color: var(--navy); background: #FFFFFF; border-radius: 10px; border: 1px solid var(--border-input); }
  .field-input.invalid { border-color: #D98A8A; }
  .field-input:focus-visible { border-color: var(--blue); }
</style>
</head>
<body>
<div style="min-height: 100vh; display: flex; flex-direction: column; background: #FFFFFF;">

  <div style="border-bottom: 1px solid var(--border); padding: 18px 28px; display: flex; align-items: center; gap: 14px;">
    <div style="width: 34px; height: 34px; border-radius: 7px; background: var(--blue); display: flex; align-items: center; justify-content: center; color: #FFFFFF; font-weight: 700; font-size: 15px; letter-spacing: 0.5px;">UIU</div>
    <div style="font-size: 16px; font-weight: 600; letter-spacing: -0.01em;">UIU Recruitment Portal</div>
    <div class="mono" style="margin-left: auto; font-size: 12px; color: var(--ink-faint); letter-spacing: 0.04em;">SECURE ASSESSMENT PORTAL</div>
    <div class="currentDateTime mono" style="font-size: 12px; color: var(--ink-faint); white-space: nowrap;" title="Bangladesh time">Loading…</div>
  </div>

  <div style="flex: 1; display: flex; flex-wrap: wrap; align-items: stretch;">

    <div style="flex: 1 1 420px; min-width: 0; display: flex; justify-content: center; padding: 64px 32px;">
      <div style="width: 100%; max-width: 420px;">
        <div class="mono" style="font-size: 12px; letter-spacing: 0.1em; color: var(--blue); text-transform: uppercase; margin-bottom: 14px;">Candidate sign in</div>
        <h1 style="margin: 0 0 12px; font-size: 34px; line-height: 1.15; font-weight: 700; letter-spacing: -0.025em;">Sign in to your assessment</h1>
        <p style="margin: 0 0 34px; font-size: 16px; line-height: 1.6; color: var(--ink-muted);">Use the Applicant ID and password provided by your invigilator. Both are case-sensitive.</p>

        <div id="errorBox" class="hidden" role="alert" style="background: var(--red-bg); border: 1px solid var(--red-border); border-radius: 10px; padding: 14px 18px; margin-bottom: 22px;">
          <div id="errorTitle" style="font-size: 15px; font-weight: 700; color: var(--red-ink); margin-bottom: 4px;"></div>
          <div id="errorBody" style="font-size: 15px; line-height: 1.5; color: #8A2020;"></div>
        </div>

        <form id="loginForm" style="display: flex; flex-direction: column; gap: 20px;">
          <div>
            <label for="applicant-id" style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Applicant ID</label>
            <input id="applicant-id" name="id" type="text" placeholder="APP-YYYY-#####" autocomplete="username" class="field-input mono" style="letter-spacing: 0.03em;">
          </div>

          <div>
            <div style="display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin-bottom: 8px;">
              <label for="exam-password" style="font-size: 14px; font-weight: 600;">Exam password</label>
              <a href="#" id="forgotLink" style="font-size: 14px; font-weight: 500; text-decoration: none;">Forgot password?</a>
            </div>
            <div style="position: relative;">
              <input id="exam-password" name="pw" type="password" placeholder="Enter your password" autocomplete="current-password" class="field-input" style="padding-right: 112px;">
              <button type="button" id="revealBtn" aria-label="Show password" style="position: absolute; right: 8px; top: 8px; height: 40px; padding: 0 14px; font-size: 14px; font-weight: 600; color: var(--ink-muted); background: #FFFFFF; border: 1px solid var(--border); border-radius: 8px; cursor: pointer; white-space: nowrap;">Show</button>
            </div>
          </div>

          <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
            <input id="trustDevice" type="checkbox" checked style="width: 20px; height: 20px; accent-color: var(--blue); cursor: pointer; flex: none;">
            <span style="font-size: 15px; color: var(--ink-muted);">Remember my applicant ID on this device</span>
          </label>

          <button type="submit" id="submitBtn" disabled style="width: 100%; height: 58px; font-size: 17px; font-weight: 600; border-radius: 10px; border: none; background: #E7EBF1; color: #94A2B6; cursor: not-allowed;">Sign in</button>
        </form>

      </div>
    </div>

    <div style="flex: 1 1 380px; min-width: 0; border-left: 1px solid var(--border); background: var(--bg-aside); padding: 64px 40px; display: flex; justify-content: center;">
      <div style="width: 100%; max-width: 400px;">
        <div style="font-size: 13px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--ink-faint); margin-bottom: 18px;">Your assessment access</div>

        <div style="background: #FFFFFF; border: 1px solid var(--border); border-radius: 12px; padding: 24px; margin-bottom: 26px;">
          <div style="font-size: 20px; font-weight: 700; letter-spacing: -0.015em; line-height: 1.3; margin-bottom: 12px;">Applicant assessment portal</div>
          <div style="font-size: 15px; line-height: 1.6; color: var(--ink-muted);">Sign in to view your assigned assessment, schedule, duration, and submission status.</div>
          <div style="margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--border); display: flex; align-items: center; gap: 10px;">
            <span style="width: 9px; height: 9px; border-radius: 50%; background: var(--green-ink); flex: none;"></span>
            <span style="font-size: 14px; font-weight: 600; color: var(--green-ink);">Secure sign-in available</span>
          </div>
        </div>

        <div style="font-size: 13px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--ink-faint); margin-bottom: 14px;">Before You Sign In</div>
        <ul style="margin: 0 0 26px; padding-left: 18px; display: flex; flex-direction: column; gap: 10px; font-size: 15px; line-height: 1.55; color: var(--ink-muted);">
          <li>Once you start the exam, the timer will begin and <strong>cannot be paused</strong>.</li>
          <li>Use a <strong>laptop or tablet</strong> with a stable internet connection. Your answers will be saved automatically.</li>
          <li><strong>Do not open or switch to other tabs</strong> during the exam.</li>
          <li>Any <strong>unfair means or misconduct</strong> may result in immediate disqualification and blacklisting.</li>
          <li>Make sure you are ready before starting the exam.</li>
        </ul>

        <div style="background: #FFFFFF; border: 1px solid var(--border); border-radius: 12px; padding: 20px;">
          <div style="font-size: 15px; font-weight: 700; margin-bottom: 8px;">Privacy</div>
          <p style="margin: 0; font-size: 14px; line-height: 1.6; color: var(--ink-muted);">This assessment does not use your camera, microphone, or screen recording. We record only your answers and the time you took.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const LOGIN_URL = <?= json_encode(site_url('login')) ?>;
const STORAGE_KEY = "examApplicantId";
const SERVER_NOW_MS = <?= (int) round(microtime(true) * 1000) ?>;
const CLIENT_CLOCK_START_MS = Date.now();
const BANGLADESH_DATE_FORMATTER = new Intl.DateTimeFormat("en-GB", {
  timeZone: "Asia/Dhaka",
  day: "2-digit",
  month: "short",
  year: "numeric",
  hour: "2-digit",
  minute: "2-digit",
  second: "2-digit",
  hour12: true
});

function updateCurrentDateTime() {
  const now = new Date(SERVER_NOW_MS + (Date.now() - CLIENT_CLOCK_START_MS));
  const label = BANGLADESH_DATE_FORMATTER.format(now) + " BDT";
  document.querySelectorAll(".currentDateTime").forEach((node) => { node.textContent = label; });
}

updateCurrentDateTime();
setInterval(updateCurrentDateTime, 1000);

const idInput = document.getElementById("applicant-id");
const pwInput = document.getElementById("exam-password");
const revealBtn = document.getElementById("revealBtn");
const trustCheckbox = document.getElementById("trustDevice");
const submitBtn = document.getElementById("submitBtn");
const form = document.getElementById("loginForm");
const errorBox = document.getElementById("errorBox");
const errorTitle = document.getElementById("errorTitle");
const errorBody = document.getElementById("errorBody");

let reveal = false;
let loading = false;

const savedId = localStorage.getItem(STORAGE_KEY);
if (savedId) idInput.value = savedId;

function updateSubmitState() {
  const ready = idInput.value.trim().length > 0 && pwInput.value.length > 0 && !loading;
  submitBtn.disabled = !ready;
  submitBtn.style.background = ready ? "#1A56DB" : "#E7EBF1";
  submitBtn.style.color = ready ? "#FFFFFF" : "#94A2B6";
  submitBtn.style.cursor = ready ? "pointer" : "not-allowed";
  submitBtn.textContent = loading ? "Signing in…" : "Sign in";
}

function setError(kind, body) {
  const errors = {
    credentials: { title: "We could not sign you in", body: body || "Check your applicant ID and password against your invitation email." },
    locked: { title: "This account is temporarily locked", body: "Too many failed attempts. Try again in 15 minutes or contact the assessment team." }
  };
  const err = errors[kind];
  if (!err) { errorBox.classList.add("hidden"); return; }
  errorTitle.textContent = err.title;
  errorBody.textContent = err.body;
  errorBox.classList.remove("hidden");
  idInput.classList.toggle("invalid", kind === "credentials");
  pwInput.classList.toggle("invalid", kind === "credentials");
}

function clearError() {
  errorBox.classList.add("hidden");
  idInput.classList.remove("invalid");
  pwInput.classList.remove("invalid");
}

idInput.addEventListener("input", () => { updateSubmitState(); });
pwInput.addEventListener("input", () => { updateSubmitState(); });

revealBtn.addEventListener("click", () => {
  reveal = !reveal;
  pwInput.type = reveal ? "text" : "password";
  revealBtn.textContent = reveal ? "Hide" : "Show";
  revealBtn.setAttribute("aria-label", reveal ? "Hide password" : "Show password");
});

document.getElementById("forgotLink").addEventListener("click", (e) => {
  e.preventDefault();
  setError("credentials", "Password resets are handled by the assessment team. Please contact your recruiter.");
});

form.addEventListener("submit", async (e) => {
  e.preventDefault();
  if (idInput.value.trim().length === 0 || pwInput.value.length === 0 || loading) return;

  loading = true;
  clearError();
  updateSubmitState();

  try {
    const res = await fetch(LOGIN_URL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        id: idInput.value.trim(),
        pw: pwInput.value,
        trustDevice: trustCheckbox.checked
      })
    });
    const data = await res.json();

    if (!res.ok) {
      if (data.error === "locked") {
        setError("locked");
      } else {
        const remaining = typeof data.attemptsRemaining === "number" ? data.attemptsRemaining : null;
        const suffix = remaining !== null ? (" You have " + remaining + (remaining === 1 ? " attempt" : " attempts") + " remaining.") : "";
        setError("credentials", "Check your applicant ID and password against your invitation email." + suffix);
      }
      loading = false;
      updateSubmitState();
      return;
    }

    if (trustCheckbox.checked) localStorage.setItem(STORAGE_KEY, idInput.value.trim());
    else localStorage.removeItem(STORAGE_KEY);

    window.location.href = data.redirect || "/";
  } catch (err) {
    setError("credentials", "Something went wrong reaching the assessment portal. Please try again.");
    loading = false;
    updateSubmitState();
  }
});

updateSubmitState();
</script>
</body>
</html>
