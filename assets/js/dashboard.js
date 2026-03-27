document.addEventListener('DOMContentLoaded', () => {
  const CART_KEY = 'game_cart';
  let cart = JSON.parse(localStorage.getItem(CART_KEY)) || [];

  const cartCount = document.getElementById('cartCount');
  const cartButtonLeft = document.getElementById('cartButtonLeft');
  const cartPanel = document.getElementById('cartPanel');
  const cartItemsEl = document.getElementById('cartItems');
  const cartTotalEl = document.getElementById('cartTotal');
  const closeCartX = document.getElementById('closeCartX');

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function saveCart() {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
    renderCart();
  }

  function addToCart(item) {
    const ex = cart.find(i => String(i.id) === String(item.id));
    if (ex) ex.qty = (ex.qty || 0) + 1;
    else cart.push({ ...item, qty: 1 });
    saveCart();
  }

  function removeFromCart(id) {
    cart = cart.filter(i => String(i.id) !== String(id));
    saveCart();
  }

  function renderCart() {
    if (cartCount) cartCount.textContent = cart.reduce((s, i) => s + (Number(i.qty) || 0), 0);
    if (!cartItemsEl) return;
    
    cartItemsEl.innerHTML = '';
    if (cart.length === 0) {
      cartItemsEl.innerHTML = '<p class="muted">Seu carrinho está vazio.</p>';
      if (cartTotalEl) cartTotalEl.textContent = 'Total: R$ 0,00';
      return;
    }
    
    let total = 0;
    cart.forEach(it => {
      const div = document.createElement('div');
      div.className = 'cart-item';
      const price = Number(it.price) || 0;
      div.innerHTML = `
        <img src="${it.img || 'https://via.placeholder.com/400x225/222'}" alt="${escapeHtml(it.name)}">
        <div style="flex:1">
          <div style="font-weight:800">${escapeHtml(it.name)}</div>
          <div class="small">Qtd: ${it.qty}</div>
          <div style="font-weight:900;margin-top:6px">R$ ${price.toFixed(2).replace('.', ',')}</div>
        </div>
        <div>
          <button class="remove-btn" data-id="${it.id}" title="Remover"><i class="fa-solid fa-trash"></i></button>
        </div>`;
      cartItemsEl.appendChild(div);
      total += price * (it.qty || 1);
    });
    
    if (cartTotalEl) cartTotalEl.textContent = 'Total: R$ ' + total.toFixed(2).replace('.', ',');
    
    document.querySelectorAll('.remove-btn').forEach(b => {
      b.addEventListener('click', () => removeFromCart(b.dataset.id));
    });
  }

  if (cartButtonLeft) {
    cartButtonLeft.addEventListener('click', () => {
      cartPanel.classList.add('open');
      cartPanel.setAttribute('aria-hidden', 'false');
    });
  }

  if (closeCartX) {
    closeCartX.addEventListener('click', () => {
      cartPanel.classList.remove('open');
      cartPanel.setAttribute('aria-hidden', 'true');
    });
  }

  document.addEventListener('click', (e) => {
    if (cartPanel && cartPanel.classList.contains('open')) {
      const inside = cartPanel.contains(e.target) || (cartButtonLeft && cartButtonLeft.contains(e.target));
      if (!inside) cartPanel.classList.remove('open');
    }
  });

  renderCart();

  document.addEventListener('click', function(e) {
    const addBtn = e.target.closest('.add-cart');
    if (addBtn) {
      const id = addBtn.getAttribute('data-game-id');
      const name = addBtn.getAttribute('data-game-name') || addBtn.getAttribute('data-name') || '';
      const img = addBtn.getAttribute('data-game-img') || '';
      const priceRaw = addBtn.getAttribute('data-game-price') || addBtn.getAttribute('data-price') || '0';
      const price = parseFloat(String(priceRaw).replace(',', '.')) || 0;
      addToCart({ id, name, img, price });

      const t = document.createElement('div');
      t.textContent = 'Adicionado ao carrinho';
      t.style.position = 'fixed';
      t.style.right = '20px';
      t.style.bottom = '20px';
      t.style.padding = '10px 14px';
      t.style.borderRadius = '8px';
      t.style.background = 'rgba(0,0,0,0.6)';
      t.style.zIndex = '1000';
      document.body.appendChild(t);
      setTimeout(() => t.remove(), 1400);
      return;
    }

    const detailsBtn = e.target.closest('.view-details, .view-detail');
    if (detailsBtn) {
      const id = detailsBtn.getAttribute('data-id') || detailsBtn.getAttribute('data-game-id');
      if (id) {
        window.location.href = 'details.php?id=' + encodeURIComponent(id);
      }
      return;
    }
  });

  const checkoutBtn = document.getElementById('checkoutBtn');
  if (checkoutBtn) {
    checkoutBtn.addEventListener('click', () => {
      if (!cart || cart.length === 0) {
        alert('Seu carrinho está vazio.');
        return;
      }
      localStorage.setItem(CART_KEY, JSON.stringify(cart));
      window.location.href = 'checkout.php';
    });
  }

  const menuLinks = document.querySelectorAll('#leftMenu [data-menu]');
  const sections = {
    inicio: document.getElementById('section-inicio'),
    library: document.getElementById('section-library'),
    account: document.getElementById('section-account')
  };

  function showSection(name) {
    Object.values(sections).forEach(s => { if (s) s.classList.remove('active'); });
    if (sections[name]) sections[name].classList.add('active');
    document.querySelectorAll('#leftMenu a').forEach(a => {
      a.classList.toggle('active', a.getAttribute('data-menu') === name);
    });
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  menuLinks.forEach(a => {
    a.addEventListener('click', (e) => {
      e.preventDefault();
      const name = a.getAttribute('data-menu') || 'inicio';
      showSection(name);
    });
  });

  document.querySelectorAll('.chip').forEach(chip => {
    chip.addEventListener('click', () => {
      document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
      chip.classList.add('active');
      const token = chip.getAttribute('data-p');
      const cards = document.querySelectorAll('.grid .card');
      if (token === 'all') {
        cards.forEach(c => c.style.display = 'block');
        return;
      }
      cards.forEach(c => {
        const plats = (c.getAttribute('data-platforms') || '').split(',').map(s => s.trim()).filter(Boolean);
        if (plats.includes(token)) c.style.display = 'block';
        else c.style.display = 'none';
      });
    });
  });

  const openAdd = document.getElementById('openAddModal');
  const modalBackdrop = document.getElementById('modalBackdrop');
  const closeModalBtn = document.getElementById('closeModalBtn');
  if (openAdd) {
    openAdd.addEventListener('click', () => modalBackdrop.style.display = 'flex');
  }
  if (closeModalBtn) {
    closeModalBtn.addEventListener('click', () => modalBackdrop.style.display = 'none');
  }
});
