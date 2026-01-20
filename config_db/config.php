<?php
// config.php - Configurações básicas do sistema
// Este arquivo deve ser incluído em todas as páginas

// ============================================
// CONFIGURAÇÕES DE SEGURANÇA E ERROS
// ============================================

// Nível de exibição de erros
// Em produção: error_reporting(0);
// Em desenvolvimento: error_reporting(E_ALL);
if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] == 'localhost') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

// Timezone do Brasil
date_default_timezone_set('America/Sao_Paulo');

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    session_regenerate_id(true); // Prevenção contra fixation attacks
}

// Headers de segurança
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// ============================================
// CONSTANTES DO SISTEMA
// ============================================

// Caminhos do sistema
define('ROOT_PATH', realpath(dirname(__FILE__) . '/..') . '/');
define('APP_PATH', ROOT_PATH . 'paginas/');
define('CSS_PATH', ROOT_PATH . 'css/');
define('JS_PATH', ROOT_PATH . 'js/');
define('BACKUP_PATH', ROOT_PATH . 'backups/');

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'barberflow');

// Configurações do site
define('SITE_NAME', 'BarberFlow');
define('SITE_URL', 'http://localhost/barberflow/');
define('SITE_VERSION', '1.0.0');

// Configurações de segurança
define('MIN_PASSWORD_LENGTH', 6);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutos em segundos

// ============================================
// FUNÇÕES DE CONEXÃO COM BANCO DE DADOS
// ============================================

/**
 * Estabelece conexão com o banco de dados
 * @return mysqli Objeto de conexão MySQLi
 */
function getConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($conn->connect_error) {
                throw new Exception("Erro na conexão com o banco de dados: " . $conn->connect_error);
            }
            
            $conn->set_charset("utf8mb4");
            $conn->query("SET time_zone = '-03:00'");
            $conn->query("SET sql_mode = ''");
            
        } catch (Exception $e) {
            // Log do erro
            error_log("[" . date('Y-m-d H:i:s') . "] " . $e->getMessage());
            
            // Se for página de setup ou criar_banco, mostrar erro detalhado
            $currentPage = basename($_SERVER['PHP_SELF']);
            if (in_array($currentPage, ['setup.php', 'criar_banco.php'])) {
                die($e->getMessage());
            }
            
            // Para outras páginas, redirecionar para setup
            $_SESSION['error'] = "Erro na conexão com o banco de dados. O sistema será configurado automaticamente.";
            redirect('setup.php');
        }
    }
    
    return $conn;
}

/**
 * Fecha a conexão com o banco de dados
 */
function closeConnection() {
    $conn = getConnection();
    if ($conn) {
        $conn->close();
    }
}

// ============================================
// FUNÇÕES DE SEGURANÇA
// ============================================

/**
 * Sanitiza entrada de dados
 * @param mixed $data Dados a serem sanitizados
 * @return mixed Dados sanitizados
 */
function cleanInput($data) {
    if (is_array($data)) {
        return array_map('cleanInput', $data);
    }
    
    if (empty($data)) return '';
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    return $data;
}

/**
 * Valida endereço de email
 * @param string $email Email a ser validado
 * @return bool True se válido, False caso contrário
 */
function isValidEmail($email) {
    if (empty($email)) return false;
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Gera hash de senha
 * @param string $password Senha em texto claro
 * @return string Hash da senha
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verifica senha
 * @param string $password Senha em texto claro
 * @param string $hash Hash armazenado
 * @return bool True se coincidir, False caso contrário
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Gera token CSRF
 * @return string Token CSRF
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica token CSRF
 * @param string $token Token a ser verificado
 * @param int $maxAge Tempo máximo de validade em segundos (padrão: 3600 = 1 hora)
 * @return bool True se válido, False caso contrário
 */
function verifyCsrfToken($token, $maxAge = 3600) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    // Verificar tempo de expiração
    if (isset($_SESSION['csrf_token_time']) && 
        (time() - $_SESSION['csrf_token_time']) > $maxAge) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Verifica e aplica tokens CSRF em formulários
 */
function applyCsrfToForms() {
    $token = generateCsrfToken();
    echo '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Prevenção contra ataques de força bruta
 * @param string $ip Endereço IP do usuário
 * @return bool True se bloqueado, False caso contrário
 */
function isBruteForceLocked($ip = null) {
    if (!$ip) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    $key = 'login_attempts_' . $ip;
    
    if (!isset($_SESSION[$key])) {
        return false;
    }
    
    $attempts = $_SESSION[$key];
    
    // Se excedeu o número máximo de tentativas
    if ($attempts['count'] >= MAX_LOGIN_ATTEMPTS) {
        $elapsed = time() - $attempts['time'];
        
        // Se ainda está no período de bloqueio
        if ($elapsed < LOCKOUT_TIME) {
            return true;
        } else {
            // Limpar tentativas após o bloqueio expirar
            unset($_SESSION[$key]);
            return false;
        }
    }
    
    return false;
}

/**
 * Registra tentativa de login falha
 * @param string $ip Endereço IP do usuário
 */
function recordLoginAttempt($ip = null) {
    if (!$ip) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    $key = 'login_attempts_' . $ip;
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'count' => 1,
            'time' => time()
        ];
    } else {
        $_SESSION[$key]['count']++;
        $_SESSION[$key]['time'] = time();
    }
}

/**
 * Limpa tentativas de login
 * @param string $ip Endereço IP do usuário
 */
function clearLoginAttempts($ip = null) {
    if (!$ip) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    $key = 'login_attempts_' . $ip;
    unset($_SESSION[$key]);
}

// ============================================
// FUNÇÕES DE UTILIDADE
// ============================================

/**
 * Redireciona para uma URL
 * @param string $url URL para redirecionamento
 * @param int $statusCode Código de status HTTP
 */
function redirect($url, $statusCode = 302) {
    if (!headers_sent()) {
        header("Location: " . $url, true, $statusCode);
        exit();
    } else {
        echo '<script>window.location.href="' . $url . '";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . $url . '"></noscript>';
        exit();
    }
}

/**
 * Formata data
 * @param string $date Data no formato MySQL ou timestamp
 * @param string $format Formato de saída (padrão: d/m/Y H:i)
 * @return string Data formatada
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return '';
    }
    
    try {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return $date;
        }
        return date($format, $timestamp);
    } catch (Exception $e) {
        return $date;
    }
}

/**
 * Formata moeda
 * @param mixed $value Valor a ser formatado
 * @param bool $withSymbol Se deve incluir o símbolo R$
 * @return string Valor formatado
 */
function formatCurrency($value, $withSymbol = true) {
    if (empty($value) && $value !== '0') {
        return $withSymbol ? 'R$ 0,00' : '0,00';
    }
    
    $num = floatval($value);
    $formatted = number_format($num, 2, ',', '.');
    
    return $withSymbol ? "R$ {$formatted}" : $formatted;
}

/**
 * Conta registros em uma tabela
 * @param string $table Nome da tabela
 * @param string $condition Condição WHERE (opcional)
 * @return int Número de registros
 */
function countRecords($table, $condition = '1=1') {
    try {
        $conn = getConnection();
        
        // Sanitizar nome da tabela
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        
        $sql = "SELECT COUNT(*) as total FROM `{$table}` WHERE {$condition}";
        $result = $conn->query($sql);
        
        if (!$result) {
            throw new Exception("Erro na consulta: " . $conn->error);
        }
        
        $row = $result->fetch_assoc();
        $conn->close();
        
        return intval($row['total'] ?? 0);
    } catch (Exception $e) {
        error_log("Erro ao contar registros: " . $e->getMessage());
        return 0;
    }
}

/**
 * Gera um avatar com as iniciais do nome
 * @param string $name Nome completo
 * @return string Iniciais (máximo 2 caracteres)
 */
function generateAvatar($name) {
    if (empty($name)) return '?';
    
    $names = explode(' ', trim($name));
    $initials = '';
    
    if (count($names) >= 2) {
        $initials = strtoupper(substr($names[0], 0, 1) . substr($names[1], 0, 1));
    } else {
        $initials = strtoupper(substr($name, 0, 2));
    }
    
    return $initials;
}

/**
 * Gera uma senha aleatória
 * @param int $length Comprimento da senha (padrão: 8)
 * @return string Senha gerada
 */
function generateRandomPassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    
    return $password;
}

// ============================================
// FUNÇÕES DE LOG E AUDITORIA
// ============================================

/**
 * Registra uma ação no log do sistema
 * @param string $action Ação realizada
 * @param string $details Detalhes da ação
 * @param int $userId ID do usuário (opcional)
 */
function logAction($action, $details = '', $userId = null) {
    try {
        $conn = getConnection();
        
        $user_id = $userId ?? ($_SESSION['user_id'] ?? null);
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt = $conn->prepare("INSERT INTO logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $action, $details, $ip_address, $user_agent);
        $stmt->execute();
        $stmt->close();
        
    } catch (Exception $e) {
        error_log("Erro ao registrar log: " . $e->getMessage());
    }
}

/**
 * Limpa logs antigos
 * @param int $days Dias a manter (padrão: 90)
 * @return bool True se sucesso, False caso contrário
 */
function cleanupOldLogs($days = 90) {
    try {
        $conn = getConnection();
        
        $dateLimit = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $stmt = $conn->prepare("DELETE FROM logs WHERE created_at < ?");
        $stmt->bind_param("s", $dateLimit);
        $stmt->execute();
        $deletedRows = $stmt->affected_rows;
        $stmt->close();
        
        return $deletedRows;
    } catch (Exception $e) {
        error_log("Erro ao limpar logs: " . $e->getMessage());
        return false;
    }
}

// ============================================
// FUNÇÕES DE BACKUP E RESTAURAÇÃO
// ============================================

/**
 * Cria backup do banco de dados
 * @param string $filename Nome do arquivo (opcional)
 * @return array Resultado da operação
 */
function createBackup($filename = null) {
    try {
        $conn = getConnection();
        
        // Criar diretório de backups se não existir
        if (!file_exists(BACKUP_PATH)) {
            mkdir(BACKUP_PATH, 0777, true);
        }
        
        // Gerar nome do arquivo
        if (!$filename) {
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        }
        
        $filepath = BACKUP_PATH . $filename;
        
        // Obter todas as tabelas
        $tables = [];
        $result = $conn->query("SHOW TABLES");
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
        
        $backupContent = "-- Backup do Banco de Dados BarberFlow\n";
        $backupContent .= "-- Gerado em: " . date('Y-m-d H:i:s') . "\n";
        $backupContent .= "-- Versão do sistema: " . SITE_VERSION . "\n\n";
        
        foreach ($tables as $table) {
            // Estrutura da tabela
            $backupContent .= "--\n-- Estrutura da tabela `{$table}`\n--\n";
            $result = $conn->query("SHOW CREATE TABLE `{$table}`");
            $row = $result->fetch_assoc();
            $backupContent .= $row['Create Table'] . ";\n\n";
            
            // Dados da tabela
            $backupContent .= "--\n-- Dumping data for table `{$table}`\n--\n";
            
            $result = $conn->query("SELECT * FROM `{$table}`");
            while ($row = $result->fetch_assoc()) {
                $columns = implode('`, `', array_keys($row));
                $values = implode("', '", array_map([$conn, 'real_escape_string'], array_values($row)));
                $backupContent .= "INSERT INTO `{$table}` (`{$columns}`) VALUES ('{$values}');\n";
            }
            $backupContent .= "\n";
        }
        
        // Salvar arquivo
        if (file_put_contents($filepath, $backupContent)) {
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => filesize($filepath),
                'tables' => count($tables)
            ];
        } else {
            throw new Exception("Erro ao salvar arquivo de backup");
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Restaura backup do banco de dados
 * @param string $filename Nome do arquivo
 * @return array Resultado da operação
 */
function restoreBackup($filename) {
    try {
        $filepath = BACKUP_PATH . $filename;
        
        if (!file_exists($filepath)) {
            throw new Exception("Arquivo de backup não encontrado");
        }
        
        $conn = getConnection();
        
        // Ler arquivo
        $sql = file_get_contents($filepath);
        
        if (empty($sql)) {
            throw new Exception("Arquivo de backup vazio ou corrompido");
        }
        
        // Executar queries
        $queries = explode(";\n", $sql);
        $successful = 0;
        $failed = 0;
        
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                try {
                    if ($conn->query($query)) {
                        $successful++;
                    } else {
                        $failed++;
                    }
                } catch (Exception $e) {
                    $failed++;
                }
            }
        }
        
        return [
            'success' => true,
            'successful_queries' => $successful,
            'failed_queries' => $failed,
            'total_queries' => count($queries) - 1
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// ============================================
// FUNÇÕES DE SESSÃO E AUTENTICAÇÃO
// ============================================

/**
 * Verifica se o usuário está logado
 * @return bool True se logado, False caso contrário
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Requer login para acessar a página
 * @param array $allowedTypes Tipos de usuário permitidos (opcional)
 */
function requireLogin($allowedTypes = []) {
    if (!isLoggedIn()) {
        $_SESSION['redirect_to'] = basename($_SERVER['PHP_SELF']);
        $_SESSION['error'] = "Você precisa fazer login para acessar esta página.";
        redirect('login.php');
        exit();
    }
    
    // Verificar tipo de usuário se especificado
    if (!empty($allowedTypes) && isset($_SESSION['user_type'])) {
        if (!in_array($_SESSION['user_type'], $allowedTypes)) {
            $_SESSION['error'] = "Você não tem permissão para acessar esta página.";
            redirect('dashboard.php');
            exit();
        }
    }
}

/**
 * Obtém dados do usuário atual
 * @return array|null Dados do usuário ou null se não encontrado
 */
function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    try {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT id, nome, email, tipo, telefone, data_cadastro, ultimo_login FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        $stmt->close();
        
        return $user;
    } catch (Exception $e) {
        error_log("Erro ao obter usuário: " . $e->getMessage());
        return null;
    }
}

/**
 * Atualiza último login do usuário
 * @param int $userId ID do usuário
 */
function updateLastLogin($userId) {
    try {
        $conn = getConnection();
        $stmt = $conn->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        error_log("Erro ao atualizar último login: " . $e->getMessage());
    }
}

/**
 * Encerra sessão do usuário
 */
function logoutUser() {
    if (isset($_SESSION['user_id'])) {
        try {
            $conn = getConnection();
            $stmt = $conn->prepare("UPDATE usuarios SET ultimo_logout = NOW() WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            // Ignorar erros no logout
        }
    }
    
    // Limpar todas as variáveis de sessão
    $_SESSION = [];
    
    // Destruir cookie de sessão
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir sessão
    session_destroy();
}

// ============================================
// FUNÇÕES DE MENU E NAVEGAÇÃO
// ============================================

/**
 * Obtém itens do menu baseado no tipo de usuário
 * @param string $userType Tipo do usuário
 * @return array Itens do menu
 */
function getMenuItems($userType) {
    $allItems = [
        [
            'link' => 'dashboard.php',
            'icon' => 'fas fa-home',
            'text' => 'Dashboard',
            'roles' => ['admin', 'barbeiro', 'cliente']
        ],
        [
            'link' => 'appointments.php',
            'icon' => 'fas fa-calendar-alt',
            'text' => 'Agendamentos',
            'roles' => ['admin', 'barbeiro', 'cliente']
        ],
        [
            'link' => 'clients.php',
            'icon' => 'fas fa-users',
            'text' => 'Clientes',
            'roles' => ['admin']
        ],
        [
            'link' => 'reports.php',
            'icon' => 'fas fa-chart-bar',
            'text' => 'Relatórios',
            'roles' => ['admin']
        ],
        [
            'link' => 'profile.php',
            'icon' => 'fas fa-user',
            'text' => 'Perfil',
            'roles' => ['admin', 'barbeiro', 'cliente']
        ],
    ];
    
    $filteredItems = [];
    foreach ($allItems as $item) {
        if (in_array($userType, $item['roles'])) {
            $filteredItems[] = $item;
        }
    }
    
    return $filteredItems;
}

/**
 * Verifica permissão de acesso à página
 * @param string $userType Tipo do usuário
 * @param string $page Página a ser acessada
 * @return bool True se permitido, False caso contrário
 */
function hasPermission($userType, $page) {
    $permissions = [
        'admin' => ['dashboard.php', 'appointments.php', 'clients.php', 'reports.php', 'profile.php', 'logout.php', 'setup.php'],
        'barbeiro' => ['dashboard.php', 'appointments.php', 'profile.php', 'logout.php'],
        'cliente' => ['dashboard.php', 'appointments.php', 'profile.php', 'logout.php']
    ];
    
    return isset($permissions[$userType]) && in_array($page, $permissions[$userType]);
}

// ============================================
// INICIALIZAÇÃO DO SISTEMA
// ============================================

// Verificar necessidade de configuração
function needsSetup() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
        if ($conn->connect_error) {
            return true;
        }
        
        $conn->select_db(DB_NAME);
        if ($conn->error) {
            return true;
        }
        
        // Verificar tabelas essenciais
        $tables = ['usuarios', 'clientes', 'agendamentos'];
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result->num_rows === 0) {
                $conn->close();
                return true;
            }
        }
        
        $conn->close();
        return false;
    } catch (Exception $e) {
        return true;
    }
}

// Inicializar sistema se necessário
if (!defined('SKIP_SETUP_CHECK')) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    $setupPages = ['setup.php', 'criar_banco.php', 'login.php', 'register.php'];
    
    if (!in_array($currentPage, $setupPages) && needsSetup()) {
        redirect('setup.php');
        exit();
    }
}

// Registrar shutdown function para fechar conexão
register_shutdown_function('closeConnection');

// ============================================
// FUNÇÕES DE DEBUG (APENAS DESENVOLVIMENTO)
// ============================================

/**
 * Função de debug (apenas para desenvolvimento)
 * @param mixed $data Dados para debug
 * @param bool $exit Se deve encerrar execução
 */
function debug($data, $exit = true) {
    if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] == 'localhost') {
        echo '<pre style="background: #f4f4f4; padding: 15px; border: 1px solid #ddd; border-radius: 5px; font-family: monospace; font-size: 12px;">';
        print_r($data);
        echo '</pre>';
        
        if ($exit) {
            exit();
        }
    }
}

/**
 * Log para console JavaScript
 * @param mixed $data Dados para log
 */
function consoleLog($data) {
    if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] == 'localhost') {
        $json = json_encode($data);
        echo "<script>console.log({$json});</script>";
    }
}

// ============================================
// MENSAGENS DE SESSÃO
// ============================================

// Inicializar array de mensagens se não existir
if (!isset($_SESSION['messages'])) {
    $_SESSION['messages'] = [];
}

/**
 * Adiciona mensagem à sessão
 * @param string $type Tipo da mensagem (success, error, warning, info)
 * @param string $message Texto da mensagem
 */
function addMessage($type, $message) {
    $_SESSION['messages'][] = [
        'type' => $type,
        'text' => $message,
        'time' => time()
    ];
}

/**
 * Obtém e limpa mensagens da sessão
 * @return array Mensagens
 */
function getMessages() {
    $messages = $_SESSION['messages'] ?? [];
    $_SESSION['messages'] = [];
    return $messages;
}

/**
 * Exibe mensagens da sessão
 */
function displayMessages() {
    $messages = getMessages();
    
    if (empty($messages)) {
        return;
    }
    
    foreach ($messages as $message) {
        $type = $message['type'];
        $text = $message['text'];
        
        echo "<div class='alert alert-{$type}'>";
        echo "<i class='fas fa-" . ($type === 'success' ? 'check-circle' : 
                                  $type === 'error' ? 'exclamation-circle' : 
                                  $type === 'warning' ? 'exclamation-triangle' : 'info-circle') . "'></i>";
        echo " {$text}";
        echo "</div>";
    }
}

// ============================================
// AUTOLOAD DE CLASSES (SE NECESSÁRIO)
// ============================================

spl_autoload_register(function ($className) {
    $classFile = APP_PATH . 'classes/' . $className . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
    }
});

// ============================================
// TRATAMENTO DE ERROS GLOBAIS
// ============================================

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    // Não exibir erros se a exibição estiver desativada
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $errorTypes = [
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict Standards',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated'
    ];
    
    $errorType = isset($errorTypes[$errno]) ? $errorTypes[$errno] : 'Unknown Error';
    
    $message = sprintf(
        '[%s] %s: %s in %s on line %d',
        date('Y-m-d H:i:s'),
        $errorType,
        $errstr,
        $errfile,
        $errline
    );
    
    error_log($message);
    
    // Em desenvolvimento, mostrar erro
    if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] == 'localhost') {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px; font-family: monospace; font-size: 12px;'>";
        echo "<strong>{$errorType}:</strong> {$errstr}<br>";
        echo "<small>in {$errfile} on line {$errline}</small>";
        echo "</div>";
    }
    
    return true;
});

set_exception_handler(function ($exception) {
    $message = sprintf(
        '[%s] Exception: %s in %s on line %d',
        date('Y-m-d H:i:s'),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine()
    );
    
    error_log($message . "\n" . $exception->getTraceAsString());
    
    // Em desenvolvimento, mostrar exceção
    if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] == 'localhost') {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px; font-family: monospace; font-size: 12px;'>";
        echo "<strong>Exception:</strong> {$exception->getMessage()}<br>";
        echo "<small>in {$exception->getFile()} on line {$exception->getLine()}</small><br>";
        echo "<pre style='background: #fff; padding: 10px; border-radius: 3px; margin-top: 10px;'>{$exception->getTraceAsString()}</pre>";
        echo "</div>";
    } else {
        // Em produção, mostrar mensagem genérica
        echo "<div style='text-align: center; padding: 50px;'>";
        echo "<h2>Ops! Algo deu errado.</h2>";
        echo "<p>Estamos trabalhando para resolver o problema. Tente novamente mais tarde.</p>";
        echo "<p><a href='dashboard.php'>Voltar ao Dashboard</a></p>";
        echo "</div>";
    }
    
    exit(1);
});

// ============================================
// FIM DO ARQUIVO DE CONFIGURAÇÃO
// ============================================

// Mensagem de debug (apenas desenvolvimento)
if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] == 'localhost') {
    consoleLog(['config' => 'loaded', 'time' => date('H:i:s')]);
}
?>
