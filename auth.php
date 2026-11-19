<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/auth.php';

start_app_session();

if (current_teacher_id() !== null) {
    header('Location: index.php');
    exit;
}

$tab = (string)($_GET['tab'] ?? 'login');
$tab = $tab === 'register' ? 'register' : 'login';

$loginError = null;
$registerError = null;

// Display Microsoft login error from URL
if (isset($_GET['error'])) {
    $loginError = (string)$_GET['error'];
}

$loginEmail = '';
$registerEmail = '';
$registerName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'login') {
        $tab = 'login';
        $loginEmail = normalize_email((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if (!is_allowed_teacher_email($loginEmail)) {
            $loginError = 'Neplatný email (musí končiť na @centrumnadania.sk).';
        } elseif ($password === '') {
            $loginError = 'Zadaj heslo.';
        } else {
            $stmt = db()->prepare('SELECT id, password_hash FROM teachers WHERE username = ?');
            $stmt->execute([$loginEmail]);
            $row = $stmt->fetch();

            if (!$row || !password_verify($password, (string)$row['password_hash'])) {
                $loginError = 'Nesprávny email alebo heslo.';
            } else {
                $_SESSION['teacher_id'] = (int)$row['id'];
                header('Location: index.php');
                exit;
            }
        }
    } elseif ($action === 'register') {
        $tab = 'register';
        $registerEmail = normalize_email((string)($_POST['email'] ?? ''));
        $registerName = trim((string)($_POST['full_name'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $password2 = (string)($_POST['password2'] ?? '');

        if (!is_allowed_teacher_email($registerEmail)) {
            $registerError = 'Neplatný email (musí končiť na @centrumnadania.sk).';
        } elseif ($registerName === '') {
            $registerError = 'Zadaj meno.';
        } elseif ($password === '' || $password2 === '') {
            $registerError = 'Zadaj heslo 2x.';
        } elseif ($password !== $password2) {
            $registerError = 'Heslá sa nezhodujú.';
        } else {
            $stmt = db()->prepare('SELECT id FROM teachers WHERE username = ?');
            $stmt->execute([$registerEmail]);
            if ($stmt->fetch()) {
                $registerError = 'Používateľ už existuje.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins = db()->prepare('INSERT INTO teachers (username, full_name, password_hash) VALUES (?, ?, ?)');
                $ins->execute([$registerEmail, $registerName, $hash]);
                $_SESSION['teacher_id'] = (int)db()->lastInsertId();
                header('Location: index.php');
                exit;
            }
        }
    }
}

?><!doctype html>
<html lang="sk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prihlásenie / Registrácia</title>
    <style>
        *,*::before,*::after{box-sizing:border-box}
        body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:0;min-height:100vh;display:grid;place-items:center;background:radial-gradient(1200px 600px at 10% 10%, #e6f0ff 0%, rgba(230,240,255,0) 60%),radial-gradient(1000px 700px at 90% 0%, #fde7f3 0%, rgba(253,231,243,0) 55%),#f6f7fb;color:#111827;padding:24px}
        .shell{width:min(920px,100%);display:grid;grid-template-columns:1.1fr .9fr;gap:18px;align-items:stretch}
        @media (max-width: 860px){.shell{grid-template-columns:1fr}}
        .hero{border:1px solid #e6e8f0;background:rgba(255,255,255,.75);backdrop-filter:blur(6px);border-radius:16px;padding:20px;min-height:420px}
        .hero h1{margin:0 0 8px 0;font-size:28px;letter-spacing:-.02em}
        .hero p{margin:0;color:#4b5563;line-height:1.45}
        .hero .pill{display:inline-flex;align-items:center;gap:8px;margin-top:14px;padding:8px 10px;border-radius:999px;border:1px solid #d6daea;background:#fff;color:#111827;font-weight:650;font-size:13px}
        .card{border:1px solid #e6e8f0;background:#fff;border-radius:16px;padding:16px;box-shadow:0 18px 55px rgba(17,24,39,.10)}
        .tabs{display:flex;gap:8px;border:1px solid #e6e8f0;background:#f9fafb;border-radius:14px;padding:6px}
        .tab{flex:1;border:1px solid transparent;background:transparent;border-radius:12px;padding:10px 12px;font-weight:800;cursor:pointer}
        .tab[aria-selected="true"]{background:#fff;border-color:#e6e8f0;box-shadow:0 6px 18px rgba(17,24,39,.06)}
        label{display:block;font-weight:700;margin-top:12px}
        input{width:100%;padding:10px 12px;border:1px solid #d6daea;border-radius:12px;margin-top:6px;font:inherit}
        button.primary{margin-top:14px;width:100%;padding:11px 12px;border-radius:12px;border:1px solid #2b6cb0;background:#2b6cb0;color:#fff;font-weight:900;cursor:pointer}
        .muted{color:#6b7280;font-size:13px}
        .err{margin-top:12px;color:#b00020;font-weight:700}
        .form{display:none}
        .form.active{display:block}
        a{color:#2b6cb0;text-decoration:none;font-weight:700}
    </style>
</head>
<body>
<div class="shell">
    <div class="hero">
        <h1>Rezervácie učební</h1>
        <p>Prihlás sa svojím školským emailom a vytváraj rezervácie učební bez konfliktov.</p>
        <div class="pill">Email musí končiť na @centrumnadania.sk</div>
    </div>

    <div class="card">
        <div class="tabs" role="tablist" aria-label="Prihlásenie a registrácia">
            <button class="tab" type="button" id="tabLogin" role="tab" aria-controls="panelLogin" aria-selected="<?= $tab === 'login' ? 'true' : 'false' ?>">Prihlásenie</button>
            <button class="tab" type="button" id="tabRegister" role="tab" aria-controls="panelRegister" aria-selected="<?= $tab === 'register' ? 'true' : 'false' ?>">Registrácia</button>
        </div>

        <div id="panelLogin" class="form <?= $tab === 'login' ? 'active' : '' ?>" role="tabpanel" aria-labelledby="tabLogin">
            <form method="post" autocomplete="on">
                <input type="hidden" name="action" value="login">

                <label for="login_email">Email</label>
                <input id="login_email" name="email" type="email" required value="<?= htmlspecialchars($loginEmail) ?>">

                <label for="login_password">Heslo</label>
                <input id="login_password" name="password" type="password" required>

                <button class="primary" type="submit">Prihlásiť</button>
            </form>
            
            <div style="text-align:center;margin:16px 0 12px;color:#6b7280;font-size:13px;font-weight:600">alebo</div>
            
            <a href="microsoft_login.php" style="display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:11px 12px;border-radius:12px;border:1px solid #d6daea;background:#fff;color:#111827;font-weight:700;text-decoration:none;font-size:14px">
                <svg width="20" height="20" viewBox="0 0 21 21" xmlns="http://www.w3.org/2000/svg"><path d="M1 1h9v9H1zM11 1h9v9h-9zM1 11h9v9H1zM11 11h9v9h-9z" fill="#737373"/></svg>
                Prihlásiť sa cez Microsoft
            </a>
            
            <?php if ($loginError !== null): ?>
                <div class="err"><?= htmlspecialchars($loginError) ?></div>
            <?php endif; ?>
            <div class="muted" style="margin-top:12px">Nemáš účet? <a href="#" id="goRegister">Vytvoriť účet</a></div>
        </div>

        <div id="panelRegister" class="form <?= $tab === 'register' ? 'active' : '' ?>" role="tabpanel" aria-labelledby="tabRegister">
            <form method="post" autocomplete="on">
                <input type="hidden" name="action" value="register">

                <label for="reg_email">Email</label>
                <input id="reg_email" name="email" type="email" required value="<?= htmlspecialchars($registerEmail) ?>">

                <label for="reg_name">Meno</label>
                <input id="reg_name" name="full_name" type="text" required value="<?= htmlspecialchars($registerName) ?>">

                <label for="reg_password">Heslo</label>
                <input id="reg_password" name="password" type="password" required>

                <label for="reg_password2">Heslo znovu</label>
                <input id="reg_password2" name="password2" type="password" required>

                <button class="primary" type="submit">Vytvoriť účet</button>
            </form>
            <?php if ($registerError !== null): ?>
                <div class="err"><?= htmlspecialchars($registerError) ?></div>
            <?php endif; ?>
            <div class="muted" style="margin-top:12px">Máš účet? <a href="#" id="goLogin">Prihlásiť sa</a></div>
        </div>
    </div>
</div>

<script>
const tabLogin = document.getElementById('tabLogin');
const tabRegister = document.getElementById('tabRegister');
const panelLogin = document.getElementById('panelLogin');
const panelRegister = document.getElementById('panelRegister');

function setTab(which){
  const isLogin = which === 'login';

  tabLogin.setAttribute('aria-selected', isLogin ? 'true' : 'false');
  tabRegister.setAttribute('aria-selected', isLogin ? 'false' : 'true');

  panelLogin.classList.toggle('active', isLogin);
  panelRegister.classList.toggle('active', !isLogin);

  const url = new URL(window.location.href);
  url.searchParams.set('tab', which);
  history.replaceState(null, '', url.toString());
}

tabLogin.addEventListener('click', () => setTab('login'));
tabRegister.addEventListener('click', () => setTab('register'));
document.getElementById('goRegister').addEventListener('click', (e) => {e.preventDefault(); setTab('register');});
document.getElementById('goLogin').addEventListener('click', (e) => {e.preventDefault(); setTab('login');});
</script>
</body>
</html>
