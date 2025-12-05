# 🌸 ElasPodem- Rede Feminina de Serviços

![Logo ElasPodem](https://img.shields.io/badge/ElasPodem-v1.0-E85D4E?style=for-the-badge&logo=heart)
![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=for-the-badge&logo=javascript)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql)

## 📋 Sobre o Projeto

**ElasPodem** é uma plataforma web que conecta mulheres prestadoras de serviços com mulheres clientes, criando uma rede de apoio e empoderamento feminino. O objetivo é proporcionar um ambiente seguro e exclusivo para mulheres oferecerem e contratarem serviços diversos, fortalecendo a economia feminina e gerando networking.

### ✨ Diferenciais

- 🛡️ **100% Seguro**: Comunidade exclusivamente feminina
- ✅ **Verificação**: Sistema de avaliações e recomendações
- 💼 **Dual**: Seja cliente E prestadora simultaneamente
- 🚀 **Gratuito**: Cadastro sem taxas ou mensalidades
- 📱 **Responsivo**: Funciona perfeitamente em todos os dispositivos

---

## 🎯 Funcionalidades

### 👤 Para Todas as Usuárias
- [x] Cadastro e login seguros
- [x] Gerenciamento de perfil completo
- [x] Upload de foto de perfil
- [x] Busca e filtros avançados de serviços
- [x] Solicitação de serviços
- [x] Histórico de solicitações enviadas
- [x] Alteração de senha
- [x] Exclusão de conta

### 💼 Para Prestadoras
- [x] Cadastro ilimitado de serviços
- [x] Gerenciamento completo (CRUD) de serviços
- [x] Ativar/desativar serviços
- [x] Recebimento de solicitações
- [x] Aceitar/recusar solicitações
- [x] Contato direto via WhatsApp
- [x] Descrição profissional personalizada

---

## 🗄️ Diagrama Entidade-Relacionamento (DER)

```
┌─────────────────────────────────────────────┐
│              USUARIOS                       │
├─────────────────────────────────────────────┤
│ PK  id                    INT               │
│     nome                  VARCHAR(100)      │
│ UK  email                 VARCHAR(100)      │
│     senha                 VARCHAR(255)      │
│     telefone              VARCHAR(20)       │
│     cidade                VARCHAR(100)      │
│     foto_perfil           VARCHAR(255)      │
│     eh_prestadora         BOOLEAN           │
│     area_atuacao          VARCHAR(255)      │
│     descricao_profissional TEXT             │
│     data_cadastro         TIMESTAMP         │
│     data_atualizacao      TIMESTAMP         │
└──────────────┬──────────────────────────────┘
               │
               │ 1:N (oferece)
               │
               ▼
┌─────────────────────────────────────────────┐
│              SERVICOS                       │
├─────────────────────────────────────────────┤
│ PK  id                    INT               │
│ FK  usuario_id            INT               │
│     titulo                VARCHAR(150)      │
│     descricao             TEXT              │
│     categoria             VARCHAR(50)       │
│     preco_estimado        DECIMAL(10,2)     │
│     localizacao           VARCHAR(100)      │
│     ativo                 BOOLEAN           │
│     data_cadastro         TIMESTAMP         │
│     data_atualizacao      TIMESTAMP         │
└──────────────┬──────────────────────────────┘
               │
               │ 1:N (recebe)
               │
               ▼
┌─────────────────────────────────────────────┐
│           SOLICITACOES                      │
├─────────────────────────────────────────────┤
│ PK  id                    INT               │
│ FK  servico_id            INT               │
│ FK  cliente_id            INT               │
│     mensagem              TEXT              │
│     status                ENUM              │
│                           (pendente,        │
│                            aceita,          │
│                            recusada)        │
│     data_solicitacao      TIMESTAMP         │
│     data_resposta         TIMESTAMP         │
└─────────────────────────────────────────────┘

Relacionamentos:
• USUARIOS (1) ──oferece──> (N) SERVICOS
• SERVICOS (1) ──recebe───> (N) SOLICITACOES
• USUARIOS (1) ──solicita─> (N) SOLICITACOES (como cliente)
```

### 🔑 Índices e Constraints

```sql
-- Tabela USUARIOS
PRIMARY KEY: id
UNIQUE KEY: email
INDEX: cidade, eh_prestadora

-- Tabela SERVICOS
PRIMARY KEY: id
FOREIGN KEY: usuario_id → usuarios(id) ON DELETE CASCADE
INDEX: categoria, ativo, localizacao

-- Tabela SOLICITACOES
PRIMARY KEY: id
FOREIGN KEY: servico_id → servicos(id) ON DELETE CASCADE
FOREIGN KEY: cliente_id → usuarios(id) ON DELETE CASCADE
INDEX: status, servico_id, cliente_id
```

---

## 🛠️ Tecnologias Utilizadas

### Frontend
- **HTML5**: Estrutura semântica
- **CSS3**: Estilização moderna com animações
- **JavaScript (ES6+)**: Lógica e interatividade
- **Fetch API**: Comunicação com backend

### Backend
- **PHP 8.0+**: Linguagem server-side
- **PDO**: Acesso seguro ao banco de dados
- **Prepared Statements**: Proteção contra SQL Injection
- **Sessions**: Gerenciamento de autenticação

### Banco de Dados
- **MySQL 8.0+**: Sistema de gerenciamento
- **InnoDB Engine**: Suporte a transações e chaves estrangeiras

### Segurança
- **Password Hashing**: `password_hash()` com bcrypt
- **CORS**: Configuração adequada
- **Validações**: Server-side e client-side
- **Sanitização**: Proteção contra XSS

---

## 📦 Estrutura do Projeto

```
entreelas/
├── frontend/
│   ├── assets/
│   │   ├── css/
│   │   │   ├── style.css              # CSS global
│   │   │   ├── landing.css            # Landing page
│   │   │   ├── login.css              # Login/Cadastro
│   │   │   ├── dashboard.css          # Dashboard
│   │   │   ├── meus-servicos.css      # Meus Serviços
│   │   │   ├── solicitacoes.css       # Solicitações
│   │   │   └── perfil.css             # Perfil
│   │   └── js/
│   │       ├── main.js                # Utilitários globais
│   │       ├── auth.js                # Autenticação
│   │       ├── dashboard.js           # Dashboard
│   │       ├── meus-servicos.js       # Meus Serviços
│   │       ├── solicitacoes.js        # Solicitações
│   │       └── perfil.js              # Perfil
│   ├── pages/
│   │   ├── login.html                 # Tela de login
│   │   ├── cadastro.html              # Tela de cadastro
│   │   ├── dashboard.html             # Dashboard principal
│   │   ├── meus-servicos.html         # Gerenciar serviços
│   │   ├── solicitacoes.html          # Ver solicitações
│   │   └── perfil.html                # Editar perfil
│   └── index.html                     # Landing page
│
└── backend/
    ├── config/
    │   └── database.php               # Conexão com BD
    ├── controllers/
    │   ├── AuthController.php         # Login/Cadastro
    │   ├── UsuariaController.php      # Perfil/Foto
    │   ├── ServicoController.php      # CRUD Serviços
    │   └── SolicitacaoController.php  # Solicitações
    ├── utils/
    │   └── response.php               # Padronização JSON
    ├── uploads/                       # Fotos de perfil
    ├── api.php                        # Roteador principal
    └── .htaccess                      # Config Apache
```

---

## 🚀 Instalação e Configuração

### Pré-requisitos

- **XAMPP** ou similar (Apache + MySQL + PHP 8.0+)
- Navegador moderno (Chrome, Firefox, Edge)

### Passo 1: Clone o Repositório

```bash
git clone https://github.com/seu-usuario/entreelas.git
cd entreelas
```

### Passo 2: Configure o Banco de Dados

1. Abra o **phpMyAdmin**: `http://localhost/phpmyadmin`
2. Clique em **SQL**
3. Execute o script `database/schema.sql`:

```sql
CREATE DATABASE IF NOT EXISTS entreelas 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE entreelas;

-- [Cole o conteúdo completo do schema.sql]
```

### Passo 3: Configure o Backend

1. Edite `backend/config/database.php` se necessário:

```php
private $host = 'localhost';
private $db_name = 'entreelas';
private $username = 'root';
private $password = '';  // Senha do MySQL
```

2. Verifique as permissões da pasta `backend/uploads/`:

```bash
chmod 777 backend/uploads/
```

### Passo 4: Configure o Frontend

1. Edite `assets/js/auth.js`, `dashboard.js`, etc. para o caminho correto:

```javascript
// Se estiver em: C:\xampp\htdocs\entreelas\
const API_URL = '/entreelas/backend/api.php';

// Se estiver em uma subpasta: C:\xampp\htdocs\projeto\entreelas\
const API_URL = '/projeto/entreelas/backend/api.php';
```

### Passo 5: Inicie o Servidor

1. Abra o **XAMPP Control Panel**
2. Inicie **Apache** e **MySQL**
3. Acesse: `http://localhost/entreelas/index.html`

---

## 📱 Como Usar

### 1️⃣ Criar Conta

1. Acesse a landing page
2. Clique em **"Cadastrar"**
3. Preencha seus dados
4. Marque **"Quero oferecer serviços"** se for prestadora
5. Clique em **"Criar Minha Conta"**

### 2️⃣ Fazer Login

1. Clique em **"Entrar"**
2. Digite seu email e senha
3. Clique em **"Entrar"**

### 3️⃣ Buscar Serviços (Cliente)

1. No **Dashboard**, veja todos os serviços disponíveis
2. Use os **filtros** por categoria ou cidade
3. Clique em **"Solicitar Serviço"**
4. Escreva uma mensagem (opcional)
5. Acompanhe em **"Solicitações"**

### 4️⃣ Oferecer Serviços (Prestadora)

1. Vá em **"Meus Serviços"**
2. Clique em **"Adicionar Serviço"**
3. Preencha título, descrição, categoria, preço
4. Salve o serviço
5. Receba solicitações em **"Solicitações Recebidas"**

### 5️⃣ Gerenciar Perfil

1. Clique em **"Perfil"**
2. Altere sua foto, dados pessoais
3. Mude sua senha
4. Exclua sua conta se desejar

---

## 🔒 Segurança Implementada

### Autenticação
- ✅ Senhas hasheadas com `password_hash()`
- ✅ Sessões com timeout de 30 minutos
- ✅ Regeneração de session ID no login
- ✅ Proteção contra fixação de sessão

### Validações
- ✅ **Client-side**: JavaScript com feedback imediato
- ✅ **Server-side**: PHP com validações rigorosas
- ✅ Sanitização de todos os inputs
- ✅ Prepared statements contra SQL Injection

### Upload de Arquivos
- ✅ Validação de tipo MIME
- ✅ Limite de 5MB por arquivo
- ✅ Renomeação automática (evita sobrescrita)
- ✅ Apenas imagens permitidas

### CORS
- ✅ Headers configurados adequadamente
- ✅ Métodos HTTP permitidos: GET, POST
- ✅ Credenciais permitidas

---

## 📊 Endpoints da API

### Autenticação

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `?action=cadastro` | Cadastra nova usuária |
| POST | `?action=login` | Realiza login |
| POST | `?action=logout` | Realiza logout |
| GET | `?action=verificar-auth` | Verifica autenticação |

### Perfil

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `?action=meu-perfil` | Retorna dados do perfil |
| POST | `?action=atualizar-perfil` | Atualiza dados do perfil |
| POST | `?action=upload-foto` | Upload de foto (FormData) |
| POST | `?action=remover-foto` | Remove foto de perfil |
| POST | `?action=alterar-senha` | Altera senha |
| POST | `?action=excluir-conta` | Exclui conta permanentemente |

### Serviços

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `?action=listar-servicos` | Lista serviços (exceto próprios) |
| GET | `?action=meus-servicos` | Lista serviços da usuária |
| POST | `?action=criar-servico` | Cria novo serviço |
| POST | `?action=atualizar-servico` | Atualiza serviço |
| POST | `?action=deletar-servico` | Deleta serviço |

### Solicitações

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `?action=solicitar-servico` | Solicita um serviço |
| GET | `?action=minhas-solicitacoes` | Solicitações enviadas |
| GET | `?action=solicitacoes-recebidas` | Solicitações recebidas |
| POST | `?action=responder-solicitacao` | Aceita/recusa solicitação |

---

## 🧪 Casos de Teste

### ✅ Autenticação
- [x] Cadastro com email duplicado deve retornar erro
- [x] Login com credenciais inválidas retorna erro 401
- [x] Senha com menos de 8 caracteres não é aceita
- [x] Sessão expira após 30 minutos de inatividade

### ✅ Serviços
- [x] Usuária não vê seus próprios serviços no dashboard
- [x] Prestadora pode criar múltiplos serviços
- [x] Serviços inativos não aparecem na busca
- [x] Editar serviço de outra usuária retorna erro 403

### ✅ Solicitações
- [x] Não pode solicitar o próprio serviço
- [x] Solicitações duplicadas são bloqueadas
- [x] Status muda corretamente ao aceitar/recusar
- [x] WhatsApp abre ao clicar em "Entrar em Contato"

### ✅ Perfil
- [x] Upload de arquivos não-imagem é rejeitado
- [x] Foto antiga é deletada ao fazer novo upload
- [x] Senha antiga incorreta impede alteração
- [x] Exclusão de conta remove todos os dados

---

## 🎨 Design e UX

### Paleta de Cores

```css
--primary: #E85D4E;        /* Coral principal */
--primary-dark: #d54a3b;   /* Coral escuro (hover) */
--secondary: #FF6B6B;      /* Coral secundário */
--accent: #FF9E8E;         /* Rosa claro */
--success: #4CAF50;        /* Verde sucesso */
--error: #DC3545;          /* Vermelho erro */
--text: #1a1a1a;           /* Texto principal */
--text-light: #666;        /* Texto secundário */
--background: #FAFAFA;     /* Fundo claro */
```

### Tipografia

- **Fonte**: System UI (nativa do SO)
- **Títulos**: 36-48px, peso 800
- **Corpo**: 14-16px, peso 400-600
- **Labels**: 13-14px, peso 600

### Responsividade

- **Mobile**: < 768px
- **Tablet**: 768px - 1024px
- **Desktop**: > 1024px

---

## 🤝 Contribuindo

Contribuições são bem-vindas! Para contribuir:

1. Fork o projeto
2. Crie uma branch: `git checkout -b feature/nova-funcionalidade`
3. Commit suas mudanças: `git commit -m 'Adiciona nova funcionalidade'`
4. Push para a branch: `git push origin feature/nova-funcionalidade`
5. Abra um Pull Request

---

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo `LICENSE` para mais detalhes.

---

## 👥 Autores

Desenvolvido com 💜 para conectar mulheres através de serviços.

---

## 📞 Suporte

Para dúvidas ou problemas:
- 📧 Email: suporte@elaspodem.com.br
- 💬 WhatsApp: (XX) XXXXX-XXXX

---

## 🎯 Roadmap Futuro

- [ ] Sistema de avaliações (estrelas)
- [ ] Chat interno entre usuárias
- [ ] Notificações por email
- [ ] Filtros avançados (preço, distância)
- [ ] Histórico de serviços realizados
- [ ] Sistema de favoritos
- [ ] Modo escuro
- [ ] App mobile (React Native)

---

**Feito com ❤️ de mulheres para mulheres** 🌸