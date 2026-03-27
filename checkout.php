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


if(empty($_SESSION['csrf_token'])){
    try { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
    catch (Exception $e) { $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32)); }
}

$dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo "Erro DB: " . htmlspecialchars($e->getMessage());
    exit;
}


if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if(stripos($contentType, 'application/json') === false){
        http_response_code(400);
        echo json_encode(['ok'=>false,'msg'=>'Content-Type deve ser application/json']);
        exit;
    }
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if(!is_array($data)){
        http_response_code(400);
        echo json_encode(['ok'=>false,'msg'=>'JSON inválido']);
        exit;
    }
    $posted_csrf = $data['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if(!hash_equals($_SESSION['csrf_token'], (string)$posted_csrf)){
        http_response_code(403);
        echo json_encode(['ok'=>false,'msg'=>'Token CSRF inválido']);
        exit;
    }

    $items = $data['items'] ?? [];
    $shipping = $data['shipping'] ?? [];
    $payment = $data['payment'] ?? [];
    $client_total_raw = $data['client_total'] ?? '0';
    $client_total = floatval(str_replace(',', '.', (string)$client_total_raw));

    if(!is_array($items) || count($items) === 0){
        http_response_code(400);
        echo json_encode(['ok'=>false,'msg'=>'Carrinho vazio']);
        exit;
    }

   
    $requiredShip = ['name','street','city','state','zip','country'];
    foreach($requiredShip as $f){
        if(empty(trim($shipping[$f] ?? ''))){
            http_response_code(400);
            echo json_encode(['ok'=>false,'msg'=>"Endereço incompleto: {$f}"]);
            exit;
        }
    }
  
    $zipRaw = preg_replace('/[^0-9]/', '', $shipping['zip']);
    if(!preg_match('/^\d{8}$/', $zipRaw)){
        http_response_code(400);
        echo json_encode(['ok'=>false,'msg'=>'CEP/ZIP inválido. Deve conter 8 dígitos.']);
        exit;
    }
 
    if(empty($payment['method'])){
        http_response_code(400);
        echo json_encode(['ok'=>false,'msg'=>'Método de pagamento não informado']);
        exit;
    }
  
    if($payment['method'] === 'card'){
        if(empty($payment['card']) || empty($payment['card']['number']) || empty($payment['card']['exp'])){
            http_response_code(400);
            echo json_encode(['ok'=>false,'msg'=>'Dados do cartão incompletos.']);
            exit;
        }
        if(!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $payment['card']['exp'])){
            http_response_code(400);
            echo json_encode(['ok'=>false,'msg'=>'Validade do cartão inválida (MM/AA).']);
            exit;
        }
        if(isset($payment['card']['cvv']) && !preg_match('/^\d{3,4}$/', $payment['card']['cvv'])){
            http_response_code(400);
            echo json_encode(['ok'=>false,'msg'=>'CVV inválido.']);
            exit;
        }
    }

    // verify items/prices
    try {
        $pdo->beginTransaction();
        $computed_total = 0.0;
        $gameLookup = $pdo->prepare("SELECT id, price FROM games WHERE id = :id LIMIT 1");
        $validated_items = [];
        foreach($items as $it){
            $gid = intval($it['id'] ?? 0);
            $qty = max(1, intval($it['qty'] ?? 1));
            if($gid <= 0){ throw new Exception("Item inválido enviado"); }
            $gameLookup->execute([':id'=>$gid]);
            $g = $gameLookup->fetch();
            if(!$g){ throw new Exception("Jogo (id={$gid}) não encontrado"); }
            $db_price = floatval($g['price']);
            $computed_total += $db_price * $qty;
            $validated_items[] = ['game_id'=>$gid,'qty'=>$qty,'price'=>$db_price];
        }
        $computed_total = round($computed_total, 2);
        if(abs($computed_total - $client_total) > 0.01){
            throw new Exception("Total difere do servidor (client={$client_total}, server={$computed_total}).");
        }

        // create order
        $order_number = date('Ymd') . strtoupper(substr(bin2hex(random_bytes(4)),0,8));
        $stmtOrder = $pdo->prepare("INSERT INTO orders (order_number, user_id, total, status, shipping_address, payment_method) VALUES (:onum, :uid, :total, :status, :ship, :pmethod)");
        $shipping_json = json_encode($shipping, JSON_UNESCAPED_UNICODE);
        $payment_method = substr($payment['method'],0,32);
        $stmtOrder->execute([
            ':onum'=>$order_number,
            ':uid'=> intval($currentUser['id'] ?? 0),
            ':total'=>$computed_total,
            ':status'=>'processing',
            ':ship'=>$shipping_json,
            ':pmethod'=>$payment_method
        ]);
        $orderId = $pdo->lastInsertId();
        $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, game_id, price, qty) VALUES (:oid, :gid, :price, :qty)");
        foreach($validated_items as $vi){
            $stmtItem->execute([
                ':oid'=>$orderId,
                ':gid'=>$vi['game_id'],
                ':price'=>$vi['price'],
                ':qty'=>$vi['qty']
            ]);
        }
        $pdo->commit();
        header('Content-Type: application/json');
        echo json_encode(['ok'=>true, 'order_id'=>$orderId, 'order_number'=>$order_number, 'total'=>number_format($computed_total,2,'.','')]);
        exit;
    } catch (Exception $ex) {
        if($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['ok'=>false,'msg'=>'Erro ao processar pedido: ' . $ex->getMessage()]);
        exit;
    }
}

// Render page (GET)
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Finalizar compra — Game Container</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{ --bg:#000; --muted:#9aa6b0; --accent:#4f6ef6; --cta:#ff6b35; --card:#0b0b0b; --radius:10px; --price:#57d08e; }
body{margin:0;background:var(--bg);color:#fff;font-family:Inter,system-ui,Arial;-webkit-font-smoothing:antialiased;}
.header{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid rgba(255,255,255,0.03)}
.brand{display:flex;align-items:center;gap:12px}
.brand .logo{width:40px;height:40px;border-radius:8px;background:linear-gradient(135deg,var(--accent),#7a3df0);display:flex;align-items:center;justify-content:center;font-weight:900}
.container{max-width:1100px;margin:24px auto;padding:0 18px;display:grid;grid-template-columns:1fr 380px;gap:22px}
@media(max-width:900px){ .container{grid-template-columns:1fr;padding:12px} }
.card{background:var(--card);padding:16px;border-radius:10px;border:1px solid rgba(255,255,255,0.03)}
.section-title{font-weight:900;margin:0 0 8px 0}
.small{color:var(--muted);font-size:14px}
.input, textarea, select{width:100%;padding:10px;border-radius:8px;background:transparent;border:1px solid rgba(255,255,255,0.04);color:#fff}
.row{display:flex;gap:12px}
.addr-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
@media(max-width:560px){ .addr-grid{grid-template-columns:1fr} }

/* order summary list */
.summary-list{display:flex;flex-direction:column;gap:12px}
.summary-item{display:flex;gap:12px;align-items:center;padding:10px;border-radius:8px;background:linear-gradient(180deg, rgba(255,255,255,0.01), rgba(255,255,255,0.00));border:1px solid rgba(255,255,255,0.02)}
.summary-item img{width:84px;height:56px;object-fit:cover;border-radius:6px}
.summary-total{display:flex;justify-content:space-between;align-items:center;padding:12px;border-radius:8px;background:rgba(255,255,255,0.02);font-weight:900}

/* primary CTA */
.place-btn{background:linear-gradient(90deg,var(--accent),#7a3df0);border:0;padding:12px 16px;border-radius:10px;color:#fff;font-weight:900;cursor:pointer;width:100%}
.place-btn:disabled{opacity:0.5;cursor:not-allowed}

/* remove button (trash) */
.item-remove-btn{
  background:transparent;border:0;color:#ffbaba;padding:8px;border-radius:8px;cursor:pointer;
}
.item-remove-btn:hover{color:#fff;background:rgba(255,255,255,0.02)}

/* toast */
#toast{position:fixed;right:20px;bottom:20px;background:rgba(0,0,0,0.8);padding:10px 14px;border-radius:10px;border:1px solid rgba(255,255,255,0.04);display:none;z-index:9999}
</style>
</head>
<body>
  <header class="header">
    <div class="brand">
      <div class="logo">GC</div>
      <div>
        <div style="font-weight:900">Game Container</div>
        <div class="small">Finalize sua compra</div>
      </div>
    </div>
    <div>
      <a class="small" href="dashboard.php" style="color:var(--muted);text-decoration:none"><i class="fa-solid fa-arrow-left"></i> Voltar à loja</a>
    </div>
  </header>

  <main class="container" id="mainContainer">
    <!-- left: checkout form -->
    <section class="card" id="checkoutForm">
      <h2 class="section-title">Endereço de entrega</h2>
      <p class="small">Preencha seus dados de entrega</p>

      <div style="margin-top:10px">
        <label class="small">Nome completo</label>
        <input class="input" id="ship_name" placeholder="Nome completo" maxlength="100">
      </div>

      <div style="margin-top:10px">
        <label class="small">Endereço</label>
        <input class="input" id="ship_street" placeholder="Rua, número, complemento" maxlength="150">
      </div>

      <div class="addr-grid" style="margin-top:10px">
        <div>
          <label class="small">Cidade</label>
          <input class="input" id="ship_city" placeholder="Cidade" maxlength="80">
        </div>
        <div>
          <label class="small">Estado (UF)</label>
          <input class="input" id="ship_state" placeholder="Ex: SP" maxlength="2">
        </div>
        <div>
          <label class="small">CEP</label>
          <input class="input" id="ship_zip" placeholder="00000-000" maxlength="9">
        </div>
        <div>
          <label class="small">País</label>
          <input class="input" id="ship_country" placeholder="País" value="Brasil" maxlength="60">
        </div>
      </div>

      <div class="form-note">Formato de CEP aceito: 12345-678 ou 12345678</div>

      <hr style="margin:16px 0;border-color:rgba(255,255,255,0.03)">

      <h3 class="section-title">Pagamento</h3>
      <p class="small">Método</p>
      <select id="payment_method" class="input" style="margin-top:8px">
        <option value="card">Cartão de crédito (simulado)</option>
        <option value="pix">PIX (simulado)</option>
        <option value="boleto">Boleto bancário (simulado)</option>
      </select>

      <div id="cardFields" style="margin-top:12px">
        <label class="small">Número do cartão</label>
        <input class="input" id="card_number" placeholder="0000 0000 0000 0000" maxlength="19" inputmode="numeric">
        <div class="row" style="margin-top:8px">
          <input class="input" id="card_exp" placeholder="MM/AA" maxlength="5">
          <input class="input" id="card_cvv" placeholder="CVV" maxlength="4" inputmode="numeric">
        </div>
        <div class="form-note">Os dados do cartão não são enviados em texto puro neste demo (simulação).</div>
      </div>

      <div style="margin-top:14px">
        <button id="placeOrder" class="place-btn">Finalizar pedido</button>
      </div>

      <div id="formMsg" style="margin-top:12px"></div>
    </section>

    <!-- right: order summary -->
    <aside class="card">
      <h2 class="section-title">Resumo do pedido</h2>
      <div class="small" style="margin-bottom:10px">Itens no carrinho</div>
      <div id="summaryList" class="summary-list"></div>

      <div class="summary-total" style="margin-top:12px">
        <div class="small">Total</div>
        <div id="summaryTotal">R$ 0,00</div>
      </div>

      <div style="margin-top:14px" class="small">Observação: pagamento será simulado — não processamos transações reais aqui.</div>
    </aside>
  </main>

  <div id="toast"></div>

<script>
// CSRF token
const CSRF = "<?php echo e($_SESSION['csrf_token']); ?>";
const CART_KEY = 'gc_cart_v1';

// Helpers
function formatBR(v){ return 'R$ ' + Number(v).toFixed(2).replace('.',','); }
function escapeHtml(s){ if(!s) return ''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }

// small toast
function showToast(msg){
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.style.display = 'block';
  t.style.opacity = '1';
  setTimeout(()=>{ t.style.transition='opacity .4s'; t.style.opacity='0'; }, 1400);
  setTimeout(()=>{ t.style.display='none'; t.style.transition=''; }, 1800);
}

// Load cart summary with remove buttons
function loadCartToSummary(){
  const list = document.getElementById('summaryList');
  list.innerHTML = '';
  let items = [];
  try{ items = JSON.parse(localStorage.getItem(CART_KEY) || '[]'); }catch(e){ items = []; }
  if(items.length === 0){
    list.innerHTML = '<div class="small" style="text-align:center;color:var(--muted)">Seu carrinho está vazio.</div>';
    document.getElementById('summaryTotal').textContent = formatBR(0);
    document.getElementById('placeOrder').disabled = true;
    return;
  }
  let total = 0;
  items.forEach(it=>{
    total += Number(it.price) * (it.qty || 1);
    const el = document.createElement('div');
    el.className = 'summary-item';
    el.innerHTML = `
      <img src="${it.img || 'https://via.placeholder.com/400x225/222'}" alt="${escapeHtml(it.name)}">
      <div style="flex:1">
        <div style="font-weight:800">${escapeHtml(it.name)}</div>
        <div class="small">R$ ${Number(it.price).toFixed(2).replace('.',',')} × ${it.qty}</div>
      </div>
      <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px">
        <div style="font-weight:900">R$ ${(Number(it.price)*it.qty).toFixed(2).replace('.',',')}</div>
        <button class="item-remove-btn" data-id="${it.id}" title="Remover item"><i class="fa-solid fa-trash"></i></button>
      </div>
    `;
    list.appendChild(el);

    // attach handler for this remove button
    const btn = el.querySelector('.item-remove-btn');
    if(btn){
      btn.addEventListener('click', ()=>{
        removeItemFromCart(it.id);
      });
    }
  });
  document.getElementById('summaryTotal').textContent = formatBR(total);
  document.getElementById('placeOrder').disabled = false;
}

// remove item from localStorage cart by id and re-render
function removeItemFromCart(id){
  let cart = [];
  try{ cart = JSON.parse(localStorage.getItem(CART_KEY) || '[]'); }catch(e){ cart = []; }
  const filtered = cart.filter(i => String(i.id) !== String(id));
  localStorage.setItem(CART_KEY, JSON.stringify(filtered));
  loadCartToSummary();
  showToast('Produto removido do carrinho');
}

// Payment UI toggle & masks (same as before)
const pm = document.getElementById('payment_method');
pm.addEventListener('change', ()=> {
  document.getElementById('cardFields').style.display = pm.value === 'card' ? 'block' : 'none';
});
pm.dispatchEvent(new Event('change'));

const cardNumberEl = document.getElementById('card_number');
if(cardNumberEl) cardNumberEl.addEventListener('input', (e)=>{
  let v = e.target.value.replace(/\D/g,'').slice(0,16);
  let parts = v.match(/.{1,4}/g) || [];
  e.target.value = parts.join(' ');
});
const cardExpEl = document.getElementById('card_exp');
if(cardExpEl) cardExpEl.addEventListener('input', (e)=>{
  let v = e.target.value.replace(/\D/g,'').slice(0,4);
  if(v.length >= 3) v = v.slice(0,2) + '/' + v.slice(2);
  e.target.value = v;
});
const cardCvvEl = document.getElementById('card_cvv');
if(cardCvvEl) cardCvvEl.addEventListener('input', (e)=> { e.target.value = e.target.value.replace(/\D/g,'').slice(0,4); });
const zipEl = document.getElementById('ship_zip');
if(zipEl) zipEl.addEventListener('input', (e)=>{
  let v = e.target.value.replace(/\D/g,'').slice(0,8);
  if(v.length > 5) v = v.slice(0,5) + '-' + v.slice(5);
  e.target.value = v;
});
const stateEl = document.getElementById('ship_state');
if(stateEl) stateEl.addEventListener('input', (e)=>{ e.target.value = e.target.value.replace(/[^A-Za-z]/g,'').toUpperCase().slice(0,2); });

// Basic client-side validation
function validateForm(){
  const name = document.getElementById('ship_name').value.trim();
  const street = document.getElementById('ship_street').value.trim();
  const city = document.getElementById('ship_city').value.trim();
  const state = document.getElementById('ship_state').value.trim();
  const zip = document.getElementById('ship_zip').value.trim();
  const country = document.getElementById('ship_country').value.trim();

  if(name.length < 3) { alert('Nome inválido ou muito curto.'); return false; }
  if(street.length < 5) { alert('Endereço inválido.'); return false; }
  if(city.length < 2) { alert('Cidade inválida.'); return false; }
  if(state.length < 2) { alert('Estado inválido (uso de 2 letras).'); return false; }

  const zipDigits = zip.replace(/\D/g,'');
  if(!/^\d{8}$/.test(zipDigits)){ alert('CEP inválido. Deve conter 8 dígitos.'); return false; }

  const method = document.getElementById('payment_method').value;
  if(method === 'card'){
    const cardNum = cardNumberEl.value.replace(/\s/g,'');
    const exp = cardExpEl.value.trim();
    const cvv = cardCvvEl.value.trim();
    if(!/^\d{16}$/.test(cardNum)){ alert('Número do cartão inválido. Informe 16 dígitos.'); return false; }
    if(!/^(0[1-9]|1[0-2])\/\d{2}$/.test(exp)){ alert('Validade do cartão inválida. Use MM/AA.'); return false; }
    if(!/^\d{3,4}$/.test(cvv)){ alert('CVV inválido.'); return false; }
  }
  return true;
}

// Place order
document.getElementById('placeOrder').addEventListener('click', async ()=>{
  let items = [];
  try{ items = JSON.parse(localStorage.getItem(CART_KEY) || '[]'); }catch(e){ items = []; }
  if(items.length === 0){ alert('Carrinho vazio'); return; }
  if(!validateForm()) return;

  const shipping = {
    name: document.getElementById('ship_name').value.trim(),
    street: document.getElementById('ship_street').value.trim(),
    city: document.getElementById('ship_city').value.trim(),
    state: document.getElementById('ship_state').value.trim(),
    zip: document.getElementById('ship_zip').value.trim(),
    country: document.getElementById('ship_country').value.trim()
  };

  const payment_method = document.getElementById('payment_method').value;
  const payment = { method: payment_method };
  if(payment_method === 'card'){
    const cardNum = cardNumberEl.value.replace(/\s/g,'');
    const masked = '**** **** **** ' + cardNum.slice(-4);
    payment.card = { number: masked, exp: cardExpEl.value.trim(), cvv: '***' };
  }

  let client_total = 0;
  const sanitizedItems = items.map(it=>{
    const qty = Math.max(1, parseInt(it.qty || 1));
    client_total += Number(it.price) * qty;
    return { id: it.id, qty: qty, price: Number(it.price) };
  });
  client_total = Math.round(client_total * 100) / 100;

  const btn = document.getElementById('placeOrder');
  btn.disabled = true;
  btn.textContent = 'Processando...';

  const payload = {
    csrf_token: CSRF,
    items: sanitizedItems,
    shipping,
    payment,
    client_total: client_total.toFixed(2)
  };

  try {
    const res = await fetch('checkout.php', {
      method: 'POST',
      headers: { 'Content-Type':'application/json', 'X-CSRF-Token': CSRF },
      body: JSON.stringify(payload)
    });
    const j = await res.json();
    if(!j.ok){
      alert('Erro: ' + (j.msg || 'Erro desconhecido'));
      btn.disabled = false;
      btn.textContent = 'Finalizar pedido';
      return;
    }
    localStorage.removeItem(CART_KEY);
    renderSuccess(j.order_number, j.total);
  } catch (err) {
    alert('Erro de rede/processamento: ' + err.message);
    btn.disabled = false;
    btn.textContent = 'Finalizar pedido';
  }
});

function renderSuccess(orderNumber, total){
  const main = document.getElementById('mainContainer');
  main.innerHTML = `
    <div style="max-width:900px;margin:40px auto;padding:18px">
      <div class="card" style="background:linear-gradient(180deg,#052015,#063022);border:1px solid rgba(0,0,0,0.4)">
        <h2 style="margin-top:0">Pedido confirmado</h2>
        <p class="small">Obrigado pela compra! Seu pedido <strong>#${escapeHtml(orderNumber)}</strong> foi recebido e está sendo processado.</p>
        <p style="margin-top:12px">Total: <strong>${formatBR(Number(total))}</strong></p>
        <div style="margin-top:18px;display:flex;gap:10px">
          <a href="dashboard.php" class="place-btn" style="background:transparent;color:#fff;border:1px solid rgba(255,255,255,0.06);text-decoration:none;display:inline-flex;align-items:center;justify-content:center">Voltar à loja</a>
          <a href="orders.php" class="place-btn" style="text-decoration:none">Ver meus pedidos</a>
        </div>
      </div>
    </div>
  `;
}

// init
loadCartToSummary();
</script>
</body>
</html>
