config.php - Arquivo de Configuração do Sistema
Arquivo principal de configuração do BarberFlow. Deve ser incluído em todas as páginas PHP.

Funcionalidades Principais
🛡️ Segurança
Controle de erros (dev/prod)

Sessões seguras com regenerate_id()

Headers de segurança HTTP

CSRF protection

Prevenção contra força bruta

Sanitização de inputs

🗄️ Banco de Dados
Conexão MySQLi singleton

Configuração UTF8MB4

Auto-redirecionamento para setup se necessário

Backup e restauração automática

👤 Autenticação
Sistema de login/logout

Controle de permissões por tipo de usuário

Atualização de último login

Logs de ações do usuário

🛠️ Utilitários
Formatação de datas e moedas

Geração de avatares

Sistema de mensagens flash

Debug para desenvolvimento

Redirecionamentos

📊 Logs e Backup
Auditoria de ações

Limpeza automática de logs antigos

Backup completo do banco

Restauração de backups

Configurações
Banco: barberflow (localhost/root)

Timezone: America/Sao_Paulo

Caminhos: ROOT_PATH, APP_PATH, CSS_PATH, etc.

Segurança: Senha mínima 6 chars, 5 tentativas de login

Tipos de Usuários
admin: Acesso total

barbeiro: Agendamentos e perfil

cliente: Próprios agendamentos

Como Usar
php
require_once '../config.php';
requireLogin(['admin']); // Requer login admin
$conn = getConnection(); // Conexão com banco
addMessage('success', 'Operação realizada!'); // Mensagem flash
