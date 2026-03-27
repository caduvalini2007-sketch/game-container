<?php


define('DB_HOST','127.0.0.1');
define('DB_PORT','3306');
define('DB_NAME','game_container');
define('DB_USER','root');
define('DB_PASS','');

session_start();


if(!isset($_SESSION['user'])){
    header('Location: index.php');
    exit;
}
$currentUser = $_SESSION['user'];
$isAdmin = (isset($currentUser['role']) && $currentUser['role'] === 'admin');


if(empty($_SESSION['csrf_token'])){
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        // fallback
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}


$dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    echo "<pre style='color:#fff'>Erro DB: " . htmlspecialchars($e->getMessage()) . "</pre>";
    exit;
}


$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($id <= 0){
    header('Location: dashboard.php');
    exit;
}


$flash = null;
$error = null;
if($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $posted_csrf = $_POST['csrf_token'] ?? '';
    if(!hash_equals($_SESSION['csrf_token'], (string)$posted_csrf)){
        $error = 'Token CSRF inválido. Por favor recarregue a página e tente novamente.';
    } else {
        $action = $_POST['action'] ?? '';

   
        if($action === 'update_game') {
        
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price_raw = trim($_POST['price'] ?? '');
            $price = str_replace(',', '.', $price_raw);
            $cover_image = trim($_POST['cover_image'] ?? '');
            $images_post = [
                trim($_POST['image1'] ?? ''),
                trim($_POST['image2'] ?? ''),
                trim($_POST['image3'] ?? ''),
                trim($_POST['image4'] ?? '')
            ];
            $released_at = trim($_POST['released_at'] ?? null);
            $developer_choice = $_POST['developer_select'] ?? '';
            $developer_new = trim($_POST['developer_new'] ?? '');
            $platforms_check = $_POST['platforms'] ?? []; // array
            $genres_check = $_POST['genres'] ?? []; // array

            $missing = [];
            if($name === '') $missing[] = 'Nome';
            if($description === '') $missing[] = 'Descrição';
            if($price === '' || !is_numeric($price)) $missing[] = 'Preço válido';
            if($cover_image === '') $missing[] = 'Capa (URL)';
        
            foreach($images_post as $k => $img) {
                if($img === '') $missing[] = 'Imagem ' . ($k+1);
            }
            if(empty($platforms_check)) $missing[] = 'Pelo menos 1 plataforma';
            if(empty($genres_check)) $missing[] = 'Pelo menos 1 gênero';
            if($developer_choice === 'new' && $developer_new === '') $missing[] = 'Desenvolvedora (novo)';
            if(!empty($missing)) {
                $error = 'Preencha corretamente: ' . implode(', ', $missing);
            } else {
                try {
                    $pdo->beginTransaction();

                   
                    if($developer_choice === 'new') {
                        $devName = $developer_new;
                        $stmt = $pdo->prepare("SELECT id FROM developers WHERE name = :n LIMIT 1");
                        $stmt->execute([':n'=>$devName]);
                        $r = $stmt->fetch();
                        if($r) $devId = $r['id'];
                        else {
                            $ins = $pdo->prepare("INSERT INTO developers (name) VALUES (:n)");
                            $ins->execute([':n'=>$devName]);
                            $devId = $pdo->lastInsertId();
                        }
                    } else {
                        $devId = intval($developer_choice) ?: null;
                        
                        if($devId) {
                            $stmt = $pdo->prepare("SELECT id FROM developers WHERE id = :id LIMIT 1");
                            $stmt->execute([':id'=>$devId]);
                            if(!$stmt->fetch()) $devId = null;
                        }
                    }

                   
                    $upd = $pdo->prepare("UPDATE games SET name = :name, description = :desc, price = :price, cover_image = :cover, developer_id = :dev, released_at = :released WHERE id = :id");
                    $upd->execute([
                        ':name'=>$name,
                        ':desc'=>$description,
                        ':price'=>floatval($price),
                        ':cover'=>$cover_image,
                        ':dev'=>$devId,
                        ':released'=>($released_at ?: null),
                        ':id'=>$id
                    ]);

                    
                    $delImgs = $pdo->prepare("DELETE FROM game_images WHERE game_id = :gid");
                    $delImgs->execute([':gid'=>$id]);
                    $insImg = $pdo->prepare("INSERT INTO game_images (game_id,url,position) VALUES (:gid,:url,:pos)");
                    for($i=0;$i<4;$i++){
                        $insImg->execute([':gid'=>$id, ':url'=>$images_post[$i], ':pos'=>$i+1]);
                    }

                    
                    $delP = $pdo->prepare("DELETE FROM game_platforms WHERE game_id = :gid");
                    $delP->execute([':gid'=>$id]);
                    $getP = $pdo->prepare("SELECT id FROM platforms WHERE name = :n LIMIT 1");
                    $insP = $pdo->prepare("INSERT INTO platforms (name) VALUES (:n)");
                    $insGP = $pdo->prepare("INSERT INTO game_platforms (game_id, platform_id) VALUES (:gid, :pid)");
                    foreach($platforms_check as $pname) {
                        $pname = trim($pname);
                        $getP->execute([':n'=>$pname]);
                        $r = $getP->fetch();
                        if($r) $pid = $r['id'];
                        else { $insP->execute([':n'=>$pname]); $pid = $pdo->lastInsertId(); }
                   
                        $insGP->execute([':gid'=>$id, ':pid'=>$pid]);
                    }

                    
                    $delG = $pdo->prepare("DELETE FROM game_genres WHERE game_id = :gid");
                    $delG->execute([':gid'=>$id]);
                    $getG = $pdo->prepare("SELECT id FROM genres WHERE name = :n LIMIT 1");
                    $insG = $pdo->prepare("INSERT INTO genres (name) VALUES (:n)");
                    $insGG = $pdo->prepare("INSERT INTO game_genres (game_id, genre_id) VALUES (:gid, :gid2)");
                    foreach($genres_check as $gname) {
                        $gname = trim($gname);
                        $getG->execute([':n'=>$gname]);
                        $r = $getG->fetch();
                        if($r) $gid2 = $r['id'];
                        else { $insG->execute([':n'=>$gname]); $gid2 = $pdo->lastInsertId(); }
                        $insGG->execute([':gid'=>$id, ':gid2'=>$gid2]);
                    }

                    $pdo->commit();
                    $flash = "Jogo atualizado com sucesso.";
                  
                } catch (Exception $ex) {
                    $pdo->rollBack();
                    $error = "Erro ao atualizar: " . $ex->getMessage();
                }
            }
        }

    
        if($action === 'delete_game') {
            
            $confirm = $_POST['confirm_delete'] ?? '0';
            if($confirm === '1') {
                try {
                    $pdo->beginTransaction();
                    
                    $pdo->prepare("DELETE FROM game_images WHERE game_id = :gid")->execute([':gid'=>$id]);
                    $pdo->prepare("DELETE FROM game_platforms WHERE game_id = :gid")->execute([':gid'=>$id]);
                    $pdo->prepare("DELETE FROM game_genres WHERE game_id = :gid")->execute([':gid'=>$id]);
                    $pdo->prepare("DELETE FROM games WHERE id = :gid")->execute([':gid'=>$id]);
                    $pdo->commit();
                  
                    header('Location: dashboard.php?deleted=1');
                    exit;
                } catch (Exception $ex) {
                    $pdo->rollBack();
                    $error = "Erro ao excluir: " . $ex->getMessage();
                }
            } else {
                $error = "Confirmação necessária para excluir.";
            }
        }
    }
}


$stmt = $pdo->prepare("
    SELECT g.*, d.name as developer_name
    FROM games g
    LEFT JOIN developers d ON g.developer_id = d.id
    WHERE g.id = :id
    LIMIT 1
");
$stmt->execute([':id'=>$id]);
$game = $stmt->fetch();
if(!$game){
    header('Location: dashboard.php');
    exit;
}


$imgStmt = $pdo->prepare("SELECT url, position FROM game_images WHERE game_id = :gid ORDER BY position ASC");
$imgStmt->execute([':gid'=>$id]);
$images = array_column($imgStmt->fetchAll(), 'url');


$platStmt = $pdo->prepare("SELECT p.name FROM platforms p JOIN game_platforms gp ON p.id = gp.platform_id WHERE gp.game_id = :gid");
$platStmt->execute([':gid'=>$id]);
$platforms_selected = array_column($platStmt->fetchAll(), 'name');


$allPlatsStmt = $pdo->query("SELECT name FROM platforms ORDER BY name ASC");
$allPlatforms = array_column($allPlatsStmt->fetchAll(), 'name');
$defaultPlatformOptions = ['PC','PlayStation','Xbox','Nintendo'];

foreach($defaultPlatformOptions as $dpo) if(!in_array($dpo, $allPlatforms)) $allPlatforms[] = $dpo;


$genStmt = $pdo->prepare("SELECT g2.name FROM genres g2 JOIN game_genres gg ON g2.id = gg.genre_id WHERE gg.game_id = :gid");
$genStmt->execute([':gid'=>$id]);
$genres_selected = array_column($genStmt->fetchAll(), 'name');

$allGenresStmt = $pdo->query("SELECT name FROM genres ORDER BY name ASC");
$allGenres = array_column($allGenresStmt->fetchAll(), 'name');

$popular_genres = ['Action','Adventure','RPG','Shooter','Strategy','Simulation','Sports','Racing','Puzzle','Indie'];
foreach($popular_genres as $pg) if(!in_array($pg, $allGenres)) $allGenres[] = $pg;


$devStmt = $pdo->query("SELECT id,name FROM developers ORDER BY name ASC");
$devList = $devStmt->fetchAll();

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }


$cover = $game['cover_image'] ?: ($images[0] ?? null);
$slides = [];
if($cover) $slides[] = $cover;
foreach($images as $u){ if(!$u) continue; if(!in_array($u,$slides,true)) $slides[] = $u; }
if(empty($slides)) $slides[] = 'https://via.placeholder.com/1200x700/111?text=No+Image';


$jsGame = [
  'id' => $game['id'],
  'name' => $game['name'],
  'price' => (float)($game['price'] ?? 0),
  'img' => $cover ?: $slides[0]
];
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title><?php echo e($game['name']); ?> — Game Store</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>

:root{
  --bg:#000000;
  --panel:#0a0a0a;
  --card:#0b0b0b;
  --muted:#9aa6b0;
  --accent:#4f6ef6;      
  --accent-2:#7a3df0;    
  --cta:#ff6b35;         
  --price:#57d08e;
  --radius:8px;
}
*{box-sizing:border-box;font-family:Inter,system-ui,-apple-system,'Segoe UI',Roboto,Arial;}
html,body{height:100%}
body{margin:0;background:var(--bg);color:#fff;min-height:100vh;-webkit-font-smoothing:antialiased;overflow-y:auto}


.header{
  display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid rgba(255,255,255,0.04);position:sticky;top:0;background:linear-gradient(180deg, rgba(0,0,0,0.75), rgba(0,0,0,0.55));z-index:80;
}
.brand{display:flex;align-items:center;gap:12px}
.brand .logo{width:42px;height:42px;border-radius:8px;background:linear-gradient(135deg,var(--accent),var(--accent-2));display:flex;align-items:center;justify-content:center;font-weight:900}
.header-actions{display:flex;align-items:center;gap:10px}
.btn{background:transparent;border:1px solid rgba(255,255,255,0.04);color:var(--muted);padding:8px 12px;border-radius:8px;cursor:pointer;font-weight:700}
.btn:hover{color:#fff;border-color:rgba(255,255,255,0.08)}
.btn.primary{background:linear-gradient(90deg,var(--accent),var(--accent-2));border:0;color:#fff;padding:10px 14px;box-shadow:0 12px 30px rgba(0,0,0,0.6)}


.full {
  width:100%;
  max-width:1600px;
  margin:0 auto;
  padding:22px;
  display:grid;
  grid-template-columns: 1fr 420px;
  gap:26px;
}
@media (max-width:1100px){ .full{grid-template-columns:1fr 360px} }
@media (max-width:880px){ .full{grid-template-columns:1fr;gap:18px;padding:12px} }


.left-card{background:transparent;border-radius:6px;padding:0}
.cover-hero{width:100%;border-radius:8px;overflow:hidden;border:1px solid rgba(255,255,255,0.03)}
.cover-hero img{width:100%;height:560px;object-fit:cover;display:block}
@media (max-width:900px){ .cover-hero img{height:380px} }


.info-full{margin-top:16px;display:flex;gap:18px;align-items:flex-start;flex-wrap:wrap}
.title-section{flex:1}
.h1{font-size:30px;font-weight:900;margin:0}
.meta{color:var(--muted);margin-top:6px}
.platforms{margin-top:12px;display:flex;gap:8px;flex-wrap:wrap}
.pill{background:rgba(255,255,255,0.02);padding:7px 12px;border-radius:999px;color:var(--muted);font-weight:800;border:1px solid rgba(255,255,255,0.02)}


.description{margin-top:16px;background:linear-gradient(180deg, rgba(255,255,255,0.01), rgba(255,255,255,0.00));padding:14px;border-radius:8px;color:var(--muted);line-height:1.6;border:1px solid rgba(255,255,255,0.02)}


.slider-area{margin-top:18px;}
.slider{position:relative;border-radius:8px;overflow:hidden;border:1px solid rgba(255,255,255,0.03)}
.slides{display:flex;transition:transform .45s cubic-bezier(.22,.9,.28,1);will-change:transform}
.slide{min-width:100%;flex:0 0 100%;display:flex;align-items:center;justify-content:center;padding:18px;background:linear-gradient(180deg, rgba(255,255,255,0.01), transparent)}
.slide img{max-width:100%;max-height:460px;object-fit:contain;border-radius:6px}
.arrow{position:absolute;top:50%;transform:translateY(-50%);background:rgba(0,0,0,0.5);border:0;color:#fff;padding:10px;border-radius:8px;cursor:pointer}
.arrow.left{left:12px} .arrow.right{right:12px}
.dots{display:flex;gap:8px;justify-content:center;margin-top:10px}
.dot{width:10px;height:10px;border-radius:999px;background:rgba(255,255,255,0.06);cursor:pointer}
.dot.active{background:linear-gradient(90deg,var(--accent),var(--accent-2))}


.thumb-row{display:flex;gap:12px;margin-top:12px;overflow-x:auto;padding-bottom:6px}
.thumb{min-width:140px;flex:0 0 140px;border-radius:8px;overflow:hidden;border:2px solid transparent;cursor:pointer}
.thumb img{width:100%;height:88px;object-fit:cover}
.thumb.active{border-color:rgba(122,61,240,0.6);transform:translateY(-6px);box-shadow:0 18px 40px rgba(122,61,240,0.06)}


.admin-panel{margin-top:18px;background:rgba(255,255,255,0.02);padding:14px;border-radius:8px;border:1px solid rgba(255,255,255,0.03)}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media (max-width:880px){ .form-grid{grid-template-columns:1fr} }
.input, textarea, select {width:100%;padding:10px;border-radius:8px;background:transparent;border:1px solid rgba(255,255,255,0.04);color:#fff}
label{display:block;margin-bottom:6px;color:var(--muted);font-weight:700}


.cart-panel{position:sticky;top:88px;background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));padding:16px;border-radius:10px;border:1px solid rgba(255,255,255,0.03)}
.cart-items{max-height:520px;overflow:auto;display:flex;flex-direction:column;gap:12px;margin-bottom:10px}


.cart-item {
  display:flex;
  gap:12px;
  align-items:center;
  padding:12px;
  border-radius:10px;
  background: linear-gradient(180deg, rgba(255,255,255,0.01), rgba(255,255,255,0.00));
  border:1px solid rgba(255,255,255,0.03);
}


.cart-item img{ width:84px; height:56px; object-fit:cover; border-radius:6px; }


.qty {
  display:flex;
  gap:8px;
  align-items:center;
}


.qty-btn {
  width:34px;
  height:34px;
  border-radius:8px;
  border:1px solid rgba(255,255,255,0.06);
  background:transparent;
  color:#fff;
  font-weight:800;
  cursor:pointer;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  transition: transform .08s ease, background .12s;
  box-shadow: 0 6px 18px rgba(0,0,0,0.45);
}
.qty-btn:hover { transform:translateY(-3px); border-color: rgba(255,255,255,0.12); background: rgba(255,255,255,0.015); }


.qty-value { min-width:30px; text-align:center; font-weight:900; font-size:14px; }


.remove-btn {
  background: linear-gradient(90deg, rgba(255,80,80,1), rgba(255,110,90,0.95));
  border:0;
  padding:8px;
  height:38px;
  width:38px;
  border-radius:10px;
  color:#fff;
  cursor:pointer;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  box-shadow:0 10px 30px rgba(122,61,240,0.06);
  transition: transform .08s, opacity .12s;
}
.remove-btn:hover { transform:translateY(-3px); opacity:0.95; }


.remove-btn i { font-size:14px; }


#clearCart {
  width:38px;
  height:38px;
  border-radius:10px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  border:1px solid rgba(255,255,255,0.04);
  background:transparent;
  cursor:pointer;
}
#clearCart:hover { background: rgba(255,255,255,0.01); transform:translateY(-2px); }


.cart-footer { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-top:12px; }


.checkout, #checkoutBtn.checkout {
  background: linear-gradient(90deg, var(--accent), var(--cta));
  border: 0;
  color: #fff;
  font-weight: 900;
  padding: 10px 16px;
  border-radius: 10px;
  cursor: pointer;
  min-width:110px;
  height:42px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  box-shadow: 0 12px 30px rgba(0,0,0,0.55), 0 4px 14px rgba(79,110,246,0.08);
  transition: transform .12s ease, box-shadow .12s, opacity .12s;
}
.checkout i { margin-right:8px; }
.checkout:hover { transform: translateY(-3px); box-shadow: 0 18px 40px rgba(0,0,0,0.6); opacity:0.98; }
.checkout:focus { outline: 3px solid rgba(122,61,240,0.12); outline-offset:2px; }


@media (max-width:560px) {
  .checkout, #checkoutBtn.checkout { min-width: 100%; height:48px; font-size:16px; }
}


.cart-item .title { font-size:15px; }
.cart-item .small { color:var(--muted); margin-top:6px; }


#lightbox{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,0.78);z-index:1200;padding:16px}
#lightbox .box{max-width:1100px;width:100%;background:var(--panel);border-radius:8px;padding:12px;border:1px solid rgba(255,255,255,0.03)}
#lightbox img{width:100%;height:640px;object-fit:contain;display:block}


.small{font-size:13px;color:var(--muted)}
.success{background:linear-gradient(90deg,#163F1A,#2A7F3A);padding:10px;border-radius:6px;color:#dfffeb;margin-bottom:10px}
.error{background:linear-gradient(90deg,#4A1515,#7A1D1D);padding:10px;border-radius:6px;color:#ffdede;margin-bottom:10px}
</style>
</head>
<body>
  <header class="header">
    <div class="brand">
      <div class="logo">GC</div>
      <div>
        <div style="font-weight:900">Game Container</div>
        <div class="small" style="margin-top:2px">Loja</div>
      </div>
    </div>

    <div class="header-actions">
      <a class="btn" href="dashboard.php"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
      <?php if($isAdmin): ?>
        <a class="btn" href="dashboard.php"><i class="fa-solid fa-gear"></i> Admin</a>
      <?php endif; ?>
      <a class="btn" href="index.php?logout=1"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
    </div>
  </header>

  <main class="full" role="main">
   
    <section class="left-card">
      <?php if($flash): ?><div class="success"><?php echo e($flash); ?></div><?php endif; ?>
      <?php if($error): ?><div class="error"><?php echo e($error); ?></div><?php endif; ?>

      <div class="cover-hero" id="coverBox">
        <img id="coverImg" src="<?php echo e($cover ?: $slides[0]); ?>" alt="<?php echo e($game['name']); ?>">
      </div>

      <div class="info-full">
        <div class="title-section">
          <h1 class="h1"><?php echo e($game['name']); ?></h1>
          <div class="meta">Desenvolvedora: <?php echo e($game['developer_name'] ?: '—'); ?></div>
          <div class="platforms">
            <?php if(!empty($platforms_selected)): foreach($platforms_selected as $p): ?>
              <div class="pill"><?php echo e($p); ?></div>
            <?php endforeach; else: ?>
              <div class="pill">—</div>
            <?php endif; ?>
          </div>
        </div>

        <div style="min-width:220px;max-width:420px">
          <div style="background:linear-gradient(180deg, rgba(255,255,255,0.01), rgba(255,255,255,0.00));padding:12px;border-radius:8px;border:1px solid rgba(255,255,255,0.03)">
            <div class="price" style="font-weight:900;font-size:20px;color:var(--price)">R$ <?php echo number_format($game['price'] ?? 0, 2, ',', '.'); ?></div>
            <div class="small" style="margin-top:6px">Lançamento: <?php echo e($game['released_at'] ? e($game['released_at']) : '—'); ?></div>
            <div style="margin-top:12px;display:flex;gap:10px">
              <button id="addCartBtn" class="btn primary" style="flex:1"><i class="fa-solid fa-cart-plus" style="margin-right:8px"></i> Adicionar</button>
              <button id="buyNow" class="btn" title="Comprar agora"><i class="fa-solid fa-bolt" style="color:var(--cta);margin-right:8px"></i> Comprar</button>
            </div>
          </div>
        </div>
      </div>

      <div class="description">
        <?php echo nl2br(e($game['description'] ?: 'Descrição não disponível.')); ?>
      </div>

   
      <div class="slider-area">
        <div class="slider" id="slider">
          <div class="slides" id="slides">
            <?php foreach($slides as $s): ?>
              <div class="slide"><img src="<?php echo e($s); ?>" alt="Print"></div>
            <?php endforeach; ?>
          </div>
          <button class="arrow left" id="prev"><i class="fa-solid fa-chevron-left"></i></button>
          <button class="arrow right" id="next"><i class="fa-solid fa-chevron-right"></i></button>
        </div>

        <div class="dots" id="dots">
          <?php for($i=0;$i<count($slides);$i++): ?>
            <div class="dot<?php echo $i===0 ? ' active' : ''; ?>" data-index="<?php echo $i; ?>"></div>
          <?php endfor; ?>
        </div>

        <div class="thumb-row" id="thumbRow">
          <?php foreach($slides as $idx => $s): ?>
            <div class="thumb<?php echo $idx===0 ? ' active' : ''; ?>" data-index="<?php echo $idx; ?>"><img src="<?php echo e($s); ?>" alt="Thumb <?php echo $idx+1; ?>"></div>
          <?php endforeach; ?>
        </div>
      </div>

     
      <?php if($isAdmin): ?>
        <div class="admin-panel" aria-label="Painel de edição (admin)">
          <h3 style="margin-top:0">Gerenciar jogo — edição</h3>

          
          <form method="post" novalidate onsubmit="return confirmUpdate(event);">
            <input type="hidden" name="action" value="update_game" id="actionField">
            <input type="hidden" name="confirm_delete" value="0" id="confirmDeleteField">
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">

            <div style="display:flex;gap:12px;flex-wrap:wrap">
              <div style="flex:1;min-width:260px">
                <label>Nome</label>
                <input class="input" type="text" name="name" value="<?php echo e($game['name']); ?>" required>
              </div>
              <div style="width:220px;min-width:140px">
                <label>Preço (ex: 49.90)</label>
                <input class="input" type="text" name="price" value="<?php echo e($game['price']); ?>" required>
              </div>
            </div>

            <div style="margin-top:10px">
              <label>Descrição</label>
              <textarea name="description" class="input" rows="6" required><?php echo e($game['description']); ?></textarea>
            </div>

            <div style="margin-top:10px" class="form-grid">
              <div>
                <label>Capa (URL)</label>
                <input class="input" name="cover_image" value="<?php echo e($game['cover_image']); ?>" required>
              </div>

              <div>
                <label>Data de lançamento (YYYY-MM-DD)</label>
                <input class="input" name="released_at" value="<?php echo e($game['released_at']); ?>">
              </div>

              <div>
                <label>Imagem 1 (URL)</label>
                <input class="input" name="image1" value="<?php echo e($images[0] ?? ''); ?>" required>
              </div>
              <div>
                <label>Imagem 2 (URL)</label>
                <input class="input" name="image2" value="<?php echo e($images[1] ?? ''); ?>" required>
              </div>
              <div>
                <label>Imagem 3 (URL)</label>
                <input class="input" name="image3" value="<?php echo e($images[2] ?? ''); ?>" required>
              </div>
              <div>
                <label>Imagem 4 (URL)</label>
                <input class="input" name="image4" value="<?php echo e($images[3] ?? ''); ?>" required>
              </div>
            </div>

            <div style="margin-top:10px" class="form-grid">
              <div>
                <label>Desenvolvedora (selecionar)</label>
                <select name="developer_select" class="input">
                  <option value="">-- selecione --</option>
                  <?php foreach($devList as $dev): ?>
                    <option value="<?php echo e($dev['id']); ?>" <?php echo ($game['developer_name'] === $dev['name']) ? 'selected' : ''; ?>><?php echo e($dev['name']); ?></option>
                  <?php endforeach; ?>
                  <option value="new">-- outro (criar novo) --</option>
                </select>
                <label style="margin-top:8px">Ou digite novo (se escolher 'outro')</label>
                <input class="input" name="developer_new" placeholder="Nome da desenvolvedora (novo)">
              </div>

              <div>
                <label>Plataformas (marque)</label>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:6px">
                  <?php
                    
                    $platOpts = $allPlatforms;
                  ?>
                  <?php foreach($platOpts as $pp): $checked = in_array($pp, $platforms_selected); ?>
                    <label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="platforms[]" value="<?php echo e($pp); ?>" <?php echo $checked ? 'checked' : ''; ?>> <?php echo e($pp); ?></label>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <div style="margin-top:10px">
              <label>Gêneros (marque)</label>
              <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;margin-top:6px">
                <?php foreach($allGenres as $gname): $checked = in_array($gname, $genres_selected); ?>
                  <label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="genres[]" value="<?php echo e($gname); ?>" <?php echo $checked ? 'checked' : ''; ?>> <?php echo e($gname); ?></label>
                <?php endforeach; ?>
              </div>
            </div>

            <div style="margin-top:12px;display:flex;gap:8px;align-items:center">
              <button type="submit" class="btn primary" onclick="window.lastClicked='update'">Salvar alterações</button>

              <button type="submit" class="btn" style="background:transparent;border:1px solid rgba(255,80,80,0.18);color:#ffbaba" onclick="window.lastClicked='delete'">Excluir jogo</button>
            </div>
          </form>
        </div>
      <?php endif; ?>
    </section>

  
    <aside class="cart-panel">
      <h3>Seu carrinho</h3>
      <div id="cartItems" class="cart-items"></div>
      <div id="cartEmpty" class="small" style="text-align:center">Seu carrinho está vazio.</div>
      <div class="cart-footer">
        <div style="font-weight:900">Total: <span id="cartTotal">R$ 0,00</span></div>
        <div style="display:flex;gap:8px;align-items:center">
          <button id="clearCart" class="btn" title="Limpar carrinho"><i class="fa-solid fa-trash"></i></button>
          
          <button id="checkoutBtn" class="checkout"><i class="fa-solid fa-check"></i>Finalizar</button>
        </div>
      </div>
    </aside>
  </main>

  
  <div id="lightbox" role="dialog" aria-modal="true">
    <div class="box">
      <img id="lightboxImg" src="" alt="Preview">
      <div style="display:flex;justify-content:space-between;margin-top:10px">
        <button id="lbClose" class="btn">Fechar</button>
        <button id="setAsCover" class="btn primary"><i class="fa-solid fa-image" style="margin-right:8px"></i> Usar como capa (visual)</button>
      </div>
    </div>
  </div>

<script>

const GAME = <?php echo json_encode($jsGame, JSON_UNESCAPED_UNICODE); ?>;
const CART_KEY = 'gc_cart_v1';


window.lastClicked = 'update';


const slidesEl = document.getElementById('slides');
const slideCount = <?php echo count($slides); ?>;
let currentIndex = 0;
let autoplay = null;
const AUTOPLAY_MS = 4000;

function updateSlider(resetAutoplay = true){
  slidesEl.style.transform = `translateX(${-currentIndex * 100}%)`;
  document.querySelectorAll('.dot').forEach((d,i)=> d.classList.toggle('active', i===currentIndex));
  document.querySelectorAll('.thumb').forEach((t,i)=> t.classList.toggle('active', i===currentIndex));
  if(resetAutoplay) restartAutoplay();
}
document.getElementById('prev').addEventListener('click', ()=> { currentIndex = (currentIndex - 1 + slideCount) % slideCount; updateSlider(); });
document.getElementById('next').addEventListener('click', ()=> { currentIndex = (currentIndex + 1) % slideCount; updateSlider(); });
document.querySelectorAll('.dot').forEach(d => d.addEventListener('click', ()=> { currentIndex = parseInt(d.dataset.index); updateSlider(); }));
document.querySelectorAll('.thumb').forEach(t => t.addEventListener('click', ()=> { currentIndex = parseInt(t.dataset.index); updateSlider(); }));

document.querySelectorAll('.slide img').forEach(img=>{
  img.style.cursor = 'zoom-in';
  img.addEventListener('click', ()=> openLightbox(img.src));
});

function restartAutoplay(){
  if(autoplay) clearInterval(autoplay);
  autoplay = setInterval(()=> {
    currentIndex = (currentIndex + 1) % slideCount;
    updateSlider(false);
  }, AUTOPLAY_MS);
}
document.getElementById('slider').addEventListener('mouseenter', ()=> { if(autoplay) clearInterval(autoplay); });
document.getElementById('slider').addEventListener('mouseleave', ()=> restartAutoplay());
updateSlider(false);
restartAutoplay();


const lightbox = document.getElementById('lightbox');
const lbImg = document.getElementById('lightboxImg');
let lbCurrent = null;
function openLightbox(src){ lbCurrent = src; lbImg.src = src; lightbox.style.display = 'flex'; }
document.getElementById('lbClose').addEventListener('click', ()=> lightbox.style.display='none');
lightbox.addEventListener('click', (e)=> { if(e.target === lightbox) lightbox.style.display = 'none'; });
document.getElementById('setAsCover').addEventListener('click', ()=> {
  if(!lbCurrent) return;
  document.getElementById('coverImg').src = lbCurrent;
  lightbox.style.display = 'none';
  toast('Imagem aplicada como capa (visual).');
});


function getCart(){ try{ return JSON.parse(localStorage.getItem(CART_KEY) || '[]'); }catch(e){ return []; } }
function saveCart(c){ localStorage.setItem(CART_KEY, JSON.stringify(c)); }
function formatBR(v){ return 'R$ ' + Number(v).toFixed(2).replace('.',','); }


function renderCart(){
  const items = getCart();
  const container = document.getElementById('cartItems');
  container.innerHTML = '';
  const emptyEl = document.getElementById('cartEmpty');
  if(items.length === 0){
    emptyEl.style.display = 'block';
    document.getElementById('cartTotal').textContent = formatBR(0);
    return;
  }
  emptyEl.style.display = 'none';
  let total = 0;
  items.forEach(it=>{
    total += Number(it.price) * (it.qty || 1);
    const div = document.createElement('div');
    div.className = 'cart-item';
    div.innerHTML = `
      <img src="${it.img || 'https://via.placeholder.com/400x225/222'}" alt="${escapeHtml(it.name)}">
      <div style="flex:1">
        <div class="title" style="font-weight:800">${escapeHtml(it.name)}</div>
        <div class="small" style="margin-top:6px">R$ ${Number(it.price).toFixed(2).replace('.',',')}</div>
        <div style="margin-top:8px;display:flex;gap:12px;align-items:center">
          <div class="qty" role="group" aria-label="Controles de quantidade">
            <button class="qty-btn dec" data-id="${it.id}" aria-label="Diminuir quantidade">−</button>
            <div class="qty-value" aria-live="polite">${it.qty}</div>
            <button class="qty-btn inc" data-id="${it.id}" aria-label="Aumentar quantidade">+</button>
          </div>
        </div>
      </div>
      <div>
        <button class="remove-btn" data-id="${it.id}" title="Remover item" aria-label="Remover item"><i class="fa-solid fa-trash"></i></button>
      </div>
    `;
    container.appendChild(div);
  });
  // rewire event handlers (mesma lógica que você já tinha)
  document.querySelectorAll('.remove-btn').forEach(b=> b.onclick = ()=> removeFromCart(b.dataset.id));
  document.querySelectorAll('.dec').forEach(b=> b.onclick = ()=> changeQty(b.dataset.id, -1));
  document.querySelectorAll('.inc').forEach(b=> b.onclick = ()=> changeQty(b.dataset.id, +1));
  document.getElementById('cartTotal').textContent = formatBR(total);
}

function addToCart(item){
  const cart = getCart();
  const ex = cart.find(i => String(i.id) === String(item.id));
  if(ex) ex.qty = (ex.qty || 1) + 1;
  else cart.push({...item, qty:1});
  saveCart(cart);
  renderCart();
  toast('Adicionado: ' + item.name);
}

function removeFromCart(id){ let cart = getCart(); cart = cart.filter(i => String(i.id) !== String(id)); saveCart(cart); renderCart(); }
function changeQty(id, delta){ const cart = getCart(); const it = cart.find(i => String(i.id) === String(id)); if(!it) return; it.qty = Math.max(1, (it.qty || 1) + delta); saveCart(cart); renderCart(); }
function clearCart(){ if(!confirm('Deseja realmente limpar o carrinho?')) return; saveCart([]); renderCart(); }

document.getElementById('addCartBtn').addEventListener('click', ()=> addToCart(GAME));
document.getElementById('buyNow').addEventListener('click', ()=> {
 
  addToCart(GAME);

 
  setTimeout(()=> {
   
    window.location.href = 'checkout.php';
  }, 120);
});
document.getElementById('clearCart').addEventListener('click', clearCart);
document.getElementById('checkoutBtn').addEventListener('click', ()=> {
  const items = getCart();
  if(items.length === 0){
    alert('Seu carrinho está vazio.');
    return;
  }

 
  saveCart(items);

  
  window.location.href = 'checkout.php';
});



renderCart();
window.addEventListener('storage', (e)=> { if(e.key === CART_KEY) renderCart(); });


function escapeHtml(s){ if(!s) return ''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }
function toast(msg){ const el = document.createElement('div'); el.textContent = msg; el.style.position='fixed'; el.style.right='20px'; el.style.bottom='20px'; el.style.background='linear-gradient(90deg,#000000cc,#141414cc)'; el.style.padding='10px 14px'; el.style.borderRadius='10px'; el.style.border='1px solid rgba(255,255,255,0.04)'; el.style.boxShadow='0 8px 30px rgba(0,0,0,0.6)'; document.body.appendChild(el); setTimeout(()=> { el.style.transition='opacity .4s'; el.style.opacity='0'; }, 1400); setTimeout(()=> el.remove(), 2000); }


function confirmUpdate(e){
  
  if(window.lastClicked === 'delete'){
    if(!confirm('Tem certeza que deseja excluir este jogo? Esta ação não pode ser desfeita.')){
      return false;
    }
    
    document.getElementById('actionField').value = 'delete_game';
    document.getElementById('confirmDeleteField').value = '1';
    return true; 
  }


  const name = document.querySelector('[name="name"]').value.trim();
  const price = document.querySelector('[name="price"]').value.trim();
  const imgs = [document.querySelector('[name="image1"]').value.trim(), document.querySelector('[name="image2"]').value.trim(), document.querySelector('[name="image3"]').value.trim(), document.querySelector('[name="image4"]').value.trim()];
  if(!name || !price){ alert('Nome e preço são obrigatórios.'); return false; }
  if(isNaN(parseFloat(price.replace(',','.')))){ alert('Preço inválido.'); return false; }
  for(let i=0;i<imgs.length;i++){ if(!imgs[i]){ alert('Informe todas as 4 imagens.'); return false; } }
  return confirm('Salvar alterações no banco de dados?');
}
</script>
</body>
</html>
