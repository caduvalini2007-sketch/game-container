<?php
require_once 'includes/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$currentUser = $_SESSION['user'];
$userId = $currentUser['id'] ?? null;
$flash = null;
$error = null;

// Lógica de atualização de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    if (!$userId) {
        $error = "Usuário inválido.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        if ($name === '') {
            $error = "Nome não pode ficar vazio.";
        } else {
            try {
                $upd = $pdo->prepare("UPDATE users SET name = :name, email = :email WHERE id = :id");
                $upd->execute([':name' => $name, ':email' => $email, ':id' => $userId]);

                $_SESSION['user']['name'] = $name;
                $_SESSION['user']['email'] = $email;
                $currentUser = $_SESSION['user'];
                $flash = "Perfil atualizado com sucesso.";
            } catch (Exception $ex) {
                $error = "Erro ao atualizar perfil: " . $ex->getMessage();
            }
        }
    }
}

// Lógica de alteração de senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    if (!$userId) {
        $error = "Usuário inválido.";
    } else {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (strlen($new) < 6) {
            $error = "Senha nova deve ter ao menos 6 caracteres.";
        } elseif ($new !== $confirm) {
            $error = "Nova senha e confirmação não coincidem.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id LIMIT 1");
                $stmt->execute([':id' => $userId]);
                $u = $stmt->fetch();
                if (!$u || !isset($u['password']) || $u['password'] === '') {
                    $error = "Não foi possível verificar a senha atual.";
                } else {
                    $hash = $u['password'];
                    if (!password_verify($current, $hash)) {
                        $error = "Senha atual incorreta.";
                    } else {
                        $newHash = password_hash($new, PASSWORD_DEFAULT);
                        $upd = $pdo->prepare("UPDATE users SET password = :ph WHERE id = :id");
                        $upd->execute([':ph' => $newHash, ':id' => $userId]);
                        $flash = "Senha alterada com sucesso.";
                    }
                }
            } catch (Exception $ex) {
                $error = "Erro ao alterar senha: " . $ex->getMessage();
            }
        }
    }
}

// Busca de jogos
$games = [];
$q = $pdo->query("SELECT g.id, g.name, g.price, g.cover_image FROM games g ORDER BY g.id DESC");
$raw = $q->fetchAll();
foreach ($raw as $g) {
    $cover = $g['cover_image'];
    if (!$cover) {
        $stm = $pdo->prepare("SELECT url FROM game_images WHERE game_id = :gid ORDER BY position ASC LIMIT 1");
        $stm->execute([':gid' => $g['id']]);
        $row = $stm->fetch();
        if ($row) $cover = $row['url'];
    }
    $pstm = $pdo->prepare("SELECT p.name as pname FROM platforms p JOIN game_platforms gp ON p.id = gp.platform_id WHERE gp.game_id = :gid");
    $pstm->execute([':gid' => $g['id']]);
    $platformRows = $pstm->fetchAll();
    $platformTokens = [];
    foreach ($platformRows as $pr) {
        $nameLower = mb_strtolower($pr['pname']);
        if (strpos($nameLower, 'pc') !== false) $platformTokens[] = 'pc';
        else if (strpos($nameLower, 'play') !== false || strpos($nameLower, 'ps') !== false) $platformTokens[] = 'ps';
        else if (strpos($nameLower, 'xbox') !== false) $platformTokens[] = 'xb';
        else if (strpos($nameLower, 'nintend') !== false) $platformTokens[] = 'n';
        else $platformTokens[] = preg_replace('/[^a-z0-9]+/', '', $nameLower);
    }
    $platformTokens = array_values(array_unique($platformTokens));
    $games[] = [
        'id' => $g['id'],
        'name' => $g['name'],
        'price' => $g['price'],
        'cover' => $cover,
        'platforms' => $platformRows,
        'platform_tokens' => $platformTokens
    ];
}

// Busca de jogos comprados
$purchasedGames = [];
if ($userId) {
    try {
        $ps = $pdo->prepare("
            SELECT DISTINCT g.id, g.name, g.price, g.cover_image
            FROM purchases p
            JOIN purchase_items pi ON pi.purchase_id = p.id
            JOIN games g ON g.id = pi.game_id
            WHERE p.user_id = :uid
            ORDER BY p.created_at DESC
        ");
        $ps->execute([':uid' => $userId]);
        $purchasedGames = $ps->fetchAll();
    } catch (Exception $e) {
        $purchasedGames = [];
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Novos e em alta — Game Container</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
  <?php if ($flash): ?><div style="position:fixed;right:20px;top:90px;z-index:999;color:#fff;background:linear-gradient(90deg,#163F1A,#2A7F3A);padding:10px;border-radius:8px"><?php echo e($flash); ?></div><?php endif; ?>
  <?php if ($error): ?><div style="position:fixed;right:20px;top:90px;z-index:999;color:#fff;background:linear-gradient(90deg,#7A1515,#4A1515);padding:10px;border-radius:8px"><?php echo e($error); ?></div><?php endif; ?>

  <header class="header">
    <div class="brand">Game Container</div>
    <div class="search-wrap">
      <div class="search" role="search">
        <i class="fa-solid fa-magnifying-glass" style="margin-right:8px;color:var(--muted)"></i>
        <input id="searchInput" placeholder="Pesquisar jogos..." aria-label="Pesquisar jogos">
      </div>
    </div>

    <div class="top-right">
      <div id="cartButtonLeft" class="cart-btn-header" title="Abrir carrinho">
        <i class="fa-solid fa-cart-shopping"></i>
        <span id="cartCount" class="cart-count">0</span>
      </div>
      <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
        <button id="openAddModal" class="btn-prim" style="padding:8px 12px;font-size:14px"><i class="fa-solid fa-plus" style="margin-right:8px"></i>Adicionar</button>
      <?php endif; ?>
      <a class="logout-btn" href="index.php?logout=1" title="Sair"><i class="fa-solid fa-right-from-bracket" style="margin-right:8px"></i>Sair</a>
    </div>
  </header>

  <div class="layout">
    <aside class="sidebar" aria-label="Menu lateral">
      <div class="menu-card">
        <div class="menu-title">Navegação</div>
        <nav class="menu" id="leftMenu">
          <a data-menu="inicio" class="active"><i class="fa-solid fa-house"></i> Início</a>
          <a data-menu="library"><i class="fa-solid fa-box"></i> Minha biblioteca</a>
          <a data-menu="account"><i class="fa-solid fa-user"></i> Minha conta</a>
        </nav>
      </div>
    </aside>

    <main>
      <section id="section-inicio" class="section active">
        <h1 class="hero-title">Novos e em alta</h1>
        <p class="hero-sub muted">Baseado em contagem de jogadores e notas de críticos</p>

        <div class="controls">
          <div class="platforms" id="platformsRow">
            <div class="chip active" data-p="all">Todas as plataformas</div>
            <div class="chip" data-p="pc"><i class="fa-brands fa-windows"></i> PC</div>
            <div class="chip" data-p="ps"><i class="fa-brands fa-playstation"></i> PlayStation</div>
            <div class="chip" data-p="xb"><i class="fa-brands fa-xbox"></i> Xbox</div>
            <div class="chip" data-p="n"><i class="fa-solid fa-gamepad"></i> Nintendo</div>
          </div>
        </div>

        <section class="grid" id="gamesGrid" aria-live="polite">
          <?php if (empty($games)): ?>
            <div class="card"><div style="padding:18px">Nenhum jogo cadastrado.</div></div>
          <?php else: foreach ($games as $g): $tokens = implode(',', $g['platform_tokens']); ?>
            <article class="card" data-game-id="<?php echo e($g['id']); ?>" data-platforms="<?php echo e($tokens); ?>">
              <img class="cover" src="<?php echo e($g['cover'] ?: 'https://via.placeholder.com/900x500/111'); ?>" alt="<?php echo e($g['name']); ?>">
              <div class="body">
                <div class="title"><?php echo e($g['name']); ?></div>
                <div class="price">R$ <?php echo number_format($g['price'] ?? 0, 2, ',', '.'); ?></div>
                <div class="footer">
                  <button class="btn-prim add-cart" data-game-id="<?php echo e($g['id']); ?>" data-game-name="<?php echo e($g['name']); ?>" data-game-img="<?php echo e($g['cover']); ?>" data-game-price="<?php echo e($g['price']); ?>"><i class="fa-solid fa-cart-plus"></i> Adicionar</button>
                  <button class="btn-out view-details" data-id="<?php echo e($g['id']); ?>"><i class="fa-solid fa-eye" style="margin-right:6px"></i>Ver detalhes</button>
                </div>
              </div>
            </article>
          <?php endforeach; endif; ?>
        </section>
      </section>

      <section id="section-library" class="section">
        <h2 class="hero-title" style="font-size:36px;margin-bottom:10px">Minha Biblioteca</h2>
        <p class="hero-sub muted">Jogos comprados por você.</p>
        <?php if (empty($purchasedGames)): ?>
          <div style="margin-top:12px;padding:18px;background:rgba(255,255,255,0.02);border-radius:10px" class="muted">Nenhuma compra encontrada.</div>
        <?php else: ?>
          <div class="library-grid" style="margin-top:12px">
            <?php foreach ($purchasedGames as $pg): ?>
              <div class="lib-card">
                <img src="<?php echo e($pg['cover'] ?: 'https://via.placeholder.com/300x168/222'); ?>" alt="<?php echo e($pg['name']); ?>">
                <div style="flex:1">
                  <div style="font-weight:900"><?php echo e($pg['name']); ?></div>
                  <div class="small muted">R$ <?php echo number_format($pg['price'] ?? 0, 2, ',', '.'); ?></div>
                </div>
                <div style="display:flex;flex-direction:column;gap:6px">
                  <button class="btn-out view-detail" data-id="<?php echo e($pg['id']); ?>"><i class="fa-solid fa-eye"></i></button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

      <section id="section-account" class="section">
        <h2 class="hero-title" style="font-size:36px;margin-bottom:10px">Minha Conta</h2>
        <div class="account-card">
          <form method="post">
            <input type="hidden" name="action" value="update_profile">
            <label>Nome</label>
            <input name="name" value="<?php echo e($currentUser['name'] ?? $currentUser['username']); ?>" style="width:100%;padding:10px;margin-bottom:10px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:#fff;border-radius:8px">
            <label>Email</label>
            <input name="email" value="<?php echo e($currentUser['email'] ?? ''); ?>" style="width:100%;padding:10px;margin-bottom:10px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:#fff;border-radius:8px">
            <button type="submit" class="btn-prim" style="width:auto;padding:10px 20px">Salvar Alterações</button>
          </form>
        </div>
      </section>
    </main>
  </div>

  <div id="cartPanel" class="cart-panel">
    <div class="cart-header">
      <h2>Seu Carrinho</h2>
      <button id="closeCartX" class="remove-btn"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div id="cartItems" class="cart-items"></div>
    <div style="padding-top:20px;border-top:1px solid rgba(255,255,255,0.05)">
      <div id="cartTotal" style="font-size:20px;font-weight:900;margin-bottom:15px">Total: R$ 0,00</div>
      <button id="checkoutBtn" class="btn-prim" style="width:100%">Finalizar Compra</button>
    </div>
  </div>

  <script src="assets/js/dashboard.js"></script>
</body>
</html>
