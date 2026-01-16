config.php - Arquivo de Configuração do Sistema BarberFlow
📋 Visão Geral
Este arquivo PHP (config.php) é o núcleo de configuração e inicialização do sistema BarberFlow. Ele contém todas as configurações essenciais, funções utilitárias, segurança, banco de dados e inicialização do sistema.

🏗️ Estrutura do Arquivo
🔒 SEÇÃO 1: Configurações de Segurança e Erros
Controle de exibição de erros (desenvolvimento vs produção)

Configuração de timezone (Brasil/São Paulo)

Inicialização segura de sessões

Headers de segurança HTTP

📦 SEÇÃO 2: Constantes do Sistema
Caminhos do sistema (ROOT_PATH, APP_PATH, CSS_PATH, etc.)

Configurações do banco de dados

Configurações do site (nome, URL, versão)

Parâmetros de segurança

🗄️ SEÇÃO 3: Conexão com Banco de Dados
Função getConnection() - Conexão singleton com MySQL

Função closeConnection() - Fecha conexão

Tratamento de erros e redirecionamento para setup

🛡️ SEÇÃO 4: Funções de Segurança
cleanInput() - Sanitização de dados de entrada

isValidEmail() - Validação de e-mail

Funções de hash de senha (BCRYPT)

Sistema completo de CSRF protection

Prevenção contra força bruta

🛠️ SEÇÃO 5: Funções de Utilidade
redirect() - Redirecionamento HTTP/JavaScript

formatDate() - Formatação de datas

formatCurrency() - Formatação monetária

countRecords() - Contagem de registros

generateAvatar() - Gera avatar a partir do nome

generateRandomPassword() - Gera senha segura

📝 SEÇÃO 6: Log e Auditoria
logAction() - Registra ações no banco de dados

cleanupOldLogs() - Limpa logs antigos automáticos

💾 SEÇÃO 7: Backup e Restauração
createBackup() - Backup completo do banco de dados

restoreBackup() - Restauração de backup

👤 SEÇÃO 8: Sessão e Autenticação
isLoggedIn() - Verifica se usuário está logado

requireLogin() - Requer autenticação para acesso

getCurrentUser() - Obtém dados do usuário atual

logoutUser() - Logout seguro

📊 SEÇÃO 9: Menu e Navegação
getMenuItems() - Gera menu baseado no tipo de usuário

hasPermission() - Verifica permissões de acesso

🔧 SEÇÃO 10: Inicialização do Sistema
needsSetup() - Verifica se sistema precisa de configuração

Redirecionamento automático para setup se necessário

🐛 SEÇÃO 11: Debug (Desenvolvimento)
debug() - Exibe dados formatados para debug

consoleLog() - Log no console JavaScript

💬 SEÇÃO 12: Sistema de Mensagens
Sistema de mensagens flash por sessão

addMessage(), getMessages(), displayMessages()

⚡ SEÇÃO 13: Autoload e Tratamento de Erros
Autoload de classes PHP

Handlers globais para erros e exceções

🔧 Principais Funcionalidades
🗄️ Sistema de Banco de Dados
php
// Conexão singleton otimizada
$conn = getConnection(); // Reutiliza conexão existente

// Configurações:
DB_HOST = 'localhost'
DB_USER = 'root'
DB_PASS = ''
DB_NAME = 'barberflow'
🛡️ Sistema de Segurança
CSRF Protection: Tokens únicos por formulário

Password Hashing: BCRYPT com cost 12

Input Sanitization: Limpeza automática de dados

Brute Force Protection: Bloqueio após 5 tentativas

Session Security: regenerate_id() e headers seguros

📊 Sistema de Logs
php
// Exemplo de uso:
logAction('login', 'Usuário fez login', $userId);
logAction('create', 'Criou novo cliente', $userId);
💾 Backup Automático
php
// Criar backup:
$backup = createBackup();
// Retorna: ['success' => true, 'filename' => 'backup_...', 'size' => ...]

// Restaurar:
$restore = restoreBackup('backup_2024-03-15.sql');
⚙️ Configurações Importantes
🔧 Ambiente de Desenvolvimento vs Produção
php
if ($_SERVER['HTTP_HOST'] == 'localhost') {
    // Desenvolvimento: mostra erros
    error_reporting(E_ALL);
} else {
    // Produção: esconde erros
    error_reporting(0);
}
📁 Estrutura de Diretórios
text
barberflow/
├── config.php          # Este arquivo
├── paginas/           # Páginas do sistema (APP_PATH)
├── css/               # Estilos (CSS_PATH)
├── js/                # Scripts (JS_PATH)
├── backups/           # Backups do banco (BACKUP_PATH)
└── classes/           # Classes PHP (autoload)
🚀 Como Usar
1. Incluir em Todas as Páginas
php
<?php
require_once '../config.php';
// Resto do código...
?>
2. Verificar Autenticação
php
// Para qualquer usuário logado:
requireLogin();

// Apenas para administradores:
requireLogin(['admin']);

// Para admin ou barbeiro:
requireLogin(['admin', 'barbeiro']);
3. Usar Conexão com Banco
php
$conn = getConnection();
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
// ...
4. Adicionar Proteção CSRF em Formulários
php
<form method="POST">
    <?php applyCsrfToForms(); ?>
    <!-- outros campos -->
</form>
5. Validar CSRF no Processamento
php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        die('Token CSRF inválido!');
    }
    // Processar formulário...
}
🔒 Considerações de Segurança
🚨 Principais Medidas Implementadas
Sessões Seguras: regenerate_id() e cookie params

SQL Injection: Prepared statements via MySQLi

XSS Prevention: htmlspecialchars em todas as saídas

CSRF Protection: Tokens únicos por formulário

Brute Force: Limite de tentativas de login

Password Security: BCRYPT com alto custo

Error Handling: Logs sem expor informações sensíveis

📝 Regras de Validação
php
// Senha: mínimo 6 caracteres
define('MIN_PASSWORD_LENGTH', 6);

// Login: máximo 5 tentativas em 15 minutos
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutos
🎯 Sistema de Mensagens Flash
💬 Uso Básico
php
// Adicionar mensagem:
addMessage('success', 'Cliente salvo com sucesso!');
addMessage('error', 'Erro ao processar a solicitação.');

// Exibir mensagens (no template):
<?php displayMessages(); ?>
🎨 Tipos de Mensagens
success - Verde (sucesso)

error - Vermelho (erro)

warning - Amarelo (aviso)

info - Azul (informação)

📊 Sistema de Permissões
👥 Tipos de Usuários
admin - Acesso total

barbeiro - Agendamentos e perfil

cliente - Próprios agendamentos e perfil

🔐 Verificação de Permissões
php
// Verificar se usuário pode acessar página:
if (!hasPermission($_SESSION['user_type'], basename($_SERVER['PHP_SELF']))) {
    redirect('dashboard.php');
}
⚡ Funções de Debug (Desenvolvimento)
🐛 Debug no Navegador
php
debug($arrayDeDados); // Exibe com estilo e encerra
debug($variavel, false); // Exibe sem encerrar execução
📋 Log no Console JavaScript
php
consoleLog(['status' => 'success', 'data' => $data]);
🔄 Inicialização Automática
🏁 Fluxo de Inicialização
Verifica se banco precisa de setup

Redireciona para setup.php se necessário

Inicia sessão com segurança

Configura timezone

Define headers de segurança

Registra handlers de erro

Inclui autoload de classes

⚠️ Páginas que Ignoram Setup Check
php
$setupPages = ['setup.php', 'criar_banco.php', 'login.php', 'register.php'];
📈 Estatísticas e Monitoramento
📊 Funções de Contagem
php
// Contar usuários ativos:
$totalUsuarios = countRecords('usuarios', 'status = "ativo"');

// Contar agendamentos hoje:
$hoje = date('Y-m-d');
$agendamentosHoje = countRecords('agendamentos', "DATE(data) = '{$hoje}'");
⚠️ Considerações de Performance
🚀 Otimizações Implementadas
Conexão Singleton: Evita múltiplas conexões

Prepared Statements: Previne SQL injection e melhora performance

Session Lazy Loading: Só inicia sessão quando necessário

Error Logging: Logs em arquivo sem overhead no frontend

💾 Gerenciamento de Recursos
php
// Fecha conexão automaticamente ao final da execução
register_shutdown_function('closeConnection');
🔧 Personalização
⚙️ Constantes Customizáveis
php
// No início do arquivo, modificar:
define('SITE_NAME', 'Minha Barbearia');
define('DB_NAME', 'minha_barbearia_db');
define('SITE_URL', 'https://meusite.com/');
🎨 Configurações de Ambiente
Desenvolvimento: localhost mostra erros

Produção: Outros hosts escondem erros

Timezone configurável para qualquer região

📄 Informações Técnicas
Arquivo: config.php
Tipo: Arquivo de configuração e bootstrap
Dependências: PHP 7.4+, MySQLi, sessões habilitadas
Funções: 40+ funções utilitárias
Segurança: Nível empresarial

Nota: Este arquivo é o coração do sistema BarberFlow. Todas as páginas devem incluí-lo para garantir segurança, consistência e funcionalidades completas. Em produção, revisar constantes de banco de dados e desativar exibição de erros.
