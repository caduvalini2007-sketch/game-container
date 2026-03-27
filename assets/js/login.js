document.addEventListener('DOMContentLoaded', () => {
  const tLogin = document.getElementById('tabLogin');
  const tRegister = document.getElementById('tabRegister');
  const fLogin = document.getElementById('formLogin');
  const fRegister = document.getElementById('formRegister');

  if (tLogin && tRegister) {
    tLogin.addEventListener('click', () => {
      tLogin.classList.add('active');
      tRegister.classList.remove('active');
      if (fLogin) fLogin.style.display = 'block';
      if (fRegister) fRegister.style.display = 'none';
      tLogin.setAttribute('aria-selected', 'true');
      tRegister.setAttribute('aria-selected', 'false');
    });

    tRegister.addEventListener('click', () => {
      tRegister.classList.add('active');
      tLogin.classList.remove('active');
      if (fRegister) fRegister.style.display = 'block';
      if (fLogin) fLogin.style.display = 'none';
      tRegister.setAttribute('aria-selected', 'true');
      tLogin.setAttribute('aria-selected', 'false');
    });
  }

  const formRegister = document.getElementById('formRegister');
  if (formRegister) {
    formRegister.addEventListener('submit', (e) => {
      const u = formRegister.querySelector('[name="username"]').value.trim();
      const p = formRegister.querySelector('[name="password"]').value.trim();
      if (!u || !p) {
        e.preventDefault();
        alert('Preencha usuário e senha para registrar.');
      }
    });
  }

  const formLogin = document.getElementById('formLogin');
  if (formLogin) {
    formLogin.addEventListener('submit', (e) => {
      const u = formLogin.querySelector('[name="username"]').value.trim();
      const p = formLogin.querySelector('[name="password"]').value.trim();
      if (!u || !p) {
        e.preventDefault();
        alert('Preencha usuário e senha.');
      }
    });
  }
});
