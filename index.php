<?php
require_once 'includes/config.php';

// logout via ?logout=1 (opcional)
if (isset($_GET['logout']) && $_GET['logout']) {
    session_unset();
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

$flash = null;

function ensureAdmin(PDO $pdo) {
    $adminUser = 'carlos';
    $adminPass = '5445';
    try {
        $stmt = $pdo->prepare("SELECT id,password FROM users WHERE username = :u LIMIT 1");
        $stmt->execute([':u' => $adminUser]);
        $r = $stmt->fetch();
        if (!$r) {
            $hash = password_hash($adminPass, PASSWORD_DEFAULT);
            $ins = $pdo->prepare("INSERT INTO users (username,password,role,created_at) VALUES (:u,:p,'admin',NOW())");
            $ins->execute([':u' => $adminUser, ':p' => $hash]);
            return "Admin criado: {$adminUser}";
        } else {
            if (strlen($r['password']) < 20) {
                $hash = password_hash($r['password'], PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE users SET password = :p WHERE id = :id");
                $upd->execute([':p' => $hash, ':id' => $r['id']]);
                return "Admin existente atualizado para hash seguro.";
            }
        }
    } catch (PDOException $ex) {
        return null;
    }
    return null;
}

$seedMsg = ensureAdmin($pdo);
if ($seedMsg) $flash = $seedMsg;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'register') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$username || !$password) {
            $_SESSION['flash'] = 'Preencha usuário e senha para registrar.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :u LIMIT 1");
        $stmt->execute([':u' => $username]);
        if ($stmt->fetch()) {
            $_SESSION['flash'] = 'Usuário já existe. Escolha outro nome.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins = $pdo->prepare("INSERT INTO users (username,email,password,role,created_at) VALUES (:u,:e,:p,'user',NOW())");
        $ins->execute([':u' => $username, ':e' => $email, ':p' => $hash]);

        $_SESSION['flash'] = 'Conta criada com sucesso. Faça login.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    if ($action === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if (!$username || !$password) {
            $_SESSION['flash'] = 'Preencha usuário e senha.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        $stmt = $pdo->prepare("SELECT id,username,password,role FROM users WHERE username = :u LIMIT 1");
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['password'])) {
            $_SESSION['flash'] = 'Usuário ou senha inválidos.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        $_SESSION['user'] = ['id' => $user['id'], 'username' => $user['username'], 'role' => $user['role']];
        header('Location: dashboard.php');
        exit;
    }
}

if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Game Container — Login</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
  <div class="container">
    <div class="hero" role="region" aria-label="Apresentação">
      <h1 class="site-title">Game Container</h1>
      <p class="welcome">Bem-vindo — entre na sua conta para acessar o painel de gerenciamento.</p>

      <div class="hero-card" aria-hidden="false">
        <div class="feature">
          <h4>Central de jogos</h4>
          <p class="muted">Cadastre e gerencie jogos com capas e imagens — apenas administradores podem adicionar.</p>
        </div>
        <div class="feature">
          <h4>Segurança</h4>
          <p class="muted">Senhas armazenadas com hashing. Para produção, configure HTTPS e outras proteções.</p>
        </div>
      </div>

      <?php if ($flash): ?>
        <div class="notice" style="margin-top:18px"><?php echo e($flash); ?></div>
      <?php endif; ?>
    </div>

    <div class="panel" role="region" aria-label="Login e registro">
      <div class="tabs" role="tablist">
        <div id="tabLogin" class="tab active" role="tab" aria-selected="true">Entrar</div>
        <div id="tabRegister" class="tab" role="tab" aria-selected="false">Registrar</div>
      </div>

      <form id="formLogin" method="post" aria-label="Formulário de login">
        <input type="hidden" name="action" value="login" />
        <label for="loginUser">Usuário</label>
        <input id="loginUser" name="username" autocomplete="username" />
        <label for="loginPass">Senha</label>
        <input id="loginPass" name="password" type="password" autocomplete="current-password" />
        <button class="btn-enter" type="submit">Entrar</button>
      </form>

      <form id="formRegister" method="post" aria-label="Formulário de registro" style="display:none;">
        <input type="hidden" name="action" value="register" />
        <label for="regUser">Usuário</label>
        <input id="regUser" name="username" />
        <label for="regEmail">Email (opcional)</label>
        <input id="regEmail" name="email" type="email" />
        <label for="regPass">Senha</label>
        <input id="regPass" name="password" type="password" />
        <button class="btn-enter" type="submit">Criar conta</button>
      </form>

      <div style="margin-top:auto" class="small center">
        <span class="muted">Ao criar conta você deverá entrar para acessar o painel.</span>
      </div>
    </div>
  </div>

  <script src="assets/js/login.js"></script>
</body>
</html>
