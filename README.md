# Game Container 🎮

Um sistema de gerenciamento e loja de jogos desenvolvido em **PHP**, **MySQL**, **CSS** e **JavaScript**. O projeto permite o cadastro de jogos, visualização de detalhes, sistema de carrinho de compras e área do usuário.

## 🚀 Funcionalidades

- **Autenticação Segura**: Sistema de login e registro com hashing de senhas (BCRYPT).
- **Painel Administrativo**: Administradores podem adicionar novos jogos à plataforma.
- **Catálogo de Jogos**: Visualização de jogos com filtros por plataforma (PC, PS, Xbox, Nintendo).
- **Carrinho de Compras**: Adição dinâmica de itens ao carrinho com persistência local.
- **Área do Usuário**: Gerenciamento de perfil e visualização da biblioteca de jogos adquiridos.
- **Design Responsivo**: Interface moderna e adaptável para diferentes tamanhos de tela.

## 🛠️ Tecnologias Utilizadas

- **Backend**: PHP 8.x (PDO para conexão segura com banco de dados)
- **Frontend**: HTML5, CSS3 (Variáveis, Flexbox, Grid), JavaScript Vanilla
- **Banco de Dados**: MySQL
- **Ícones**: Font Awesome 6

## 📂 Estrutura do Projeto

O projeto foi organizado seguindo boas práticas de separação de responsabilidades:

```text
/
├── assets/             # Arquivos estáticos
│   ├── css/            # Estilos CSS separados por módulo
│   ├── js/             # Scripts JavaScript
│   └── images/         # Imagens e assets visuais
├── includes/           # Arquivos PHP reutilizáveis (configuração, funções)
├── sql/                # Scripts de criação do banco de dados
├── docs/               # Documentação adicional do projeto
├── index.php           # Página de login/registro
├── dashboard.php       # Painel principal do usuário
├── details.php         # Detalhes de um jogo específico
└── checkout.php        # Finalização de compra
```

## ⚙️ Como Instalar

1. **Clonar o repositório**:
   ```bash
   git clone https://github.com/seu-usuario/game-container.git
   ```

2. **Configurar o Banco de Dados**:
   - Importe o arquivo localizado em `sql/trabalho_claudemir.sql` no seu servidor MySQL.
   - O banco de dados padrão é `game_container`.

3. **Configurar a Conexão**:
   - Edite o arquivo `includes/config.php` com as credenciais do seu servidor local (Host, Usuário, Senha).

4. **Executar**:
   - Coloque a pasta do projeto no seu servidor local (ex: `htdocs` do XAMPP ou `www` do WAMP).
   - Acesse via navegador: `http://localhost/game-container`.

## 👤 Usuário Padrão (Admin)
- **Usuário**: `carlos`
- **Senha**: `5445`

---
Desenvolvido para fins acadêmicos e de portfólio.
