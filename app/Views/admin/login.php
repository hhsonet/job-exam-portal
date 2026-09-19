<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin login</title>
  <style>
    body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f4f6fa; font-family: Arial, sans-serif; color: #172033; }
    form { width: min(380px, calc(100% - 40px)); padding: 32px; background: white; border: 1px solid #dfe4ec; border-radius: 12px; box-shadow: 0 10px 30px #17203312; }
    h1 { margin: 0 0 24px; font-size: 26px; }
    label { display: block; margin: 16px 0 7px; font-weight: 600; }
    input { box-sizing: border-box; width: 100%; padding: 12px; border: 1px solid #b9c2d0; border-radius: 7px; font-size: 16px; }
    button { width: 100%; margin-top: 22px; padding: 13px; border: 0; border-radius: 7px; background: #1a56db; color: white; font-size: 16px; font-weight: 600; cursor: pointer; }
    .error { padding: 11px 13px; border-radius: 7px; background: #fff0f0; color: #a32121; }
  </style>
</head>
<body>
  <form method="post" action="<?= site_url('admin/login') ?>">
    <h1>Admin login</h1>
    <?php if (! empty($error)): ?><div class="error"><?= esc($error) ?></div><?php endif; ?>
    <label for="username">Username</label>
    <input id="username" name="username" type="text" autocomplete="username" required autofocus>
    <label for="password">Password</label>
    <input id="password" name="password" type="password" autocomplete="current-password" required>
    <button type="submit">Sign in</button>
  </form>
</body>
</html>
