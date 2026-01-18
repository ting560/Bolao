<?php
/**
 * Sistema de Autenticação de Usuários para o Bolão
 * Arquivo: auth_functions.php
 */

// Arquivo de armazenamento dos usuários
define('USERS_FILE', 'temp/users.json');

/**
 * Carrega todos os usuários do arquivo JSON
 */
function loadUsers() {
    if (!file_exists(USERS_FILE)) {
        return [];
    }
    
    $content = file_get_contents(USERS_FILE);
    $data = json_decode($content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Erro ao decodificar JSON de usuários: " . json_last_error_msg());
        return [];
    }
    
    return $data['users'] ?? [];
}

/**
 * Salva os usuários no arquivo JSON
 */
function saveUsers($users) {
    $data = ['users' => $users];
    $json = json_encode($data, JSON_PRETTY_PRINT);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Erro ao codificar JSON de usuários: " . json_last_error_msg());
        return false;
    }
    
    return file_put_contents(USERS_FILE, $json) !== false;
}

/**
 * Registra um novo usuário (versão estendida com telefone)
 */
function registerUserExtended($name, $phone, $email, $password) {
    $users = loadUsers();
    
    // Verifica se email já existe
    foreach ($users as $user) {
        if ($user['email'] === $email) {
            return ['success' => false, 'message' => 'Email já cadastrado'];
        }
    }
    
    // Cria novo usuário
    $newUser = [
        'id' => uniqid(),
        'name' => trim($name),
        'phone' => trim($phone),
        'email' => strtolower(trim($email)),
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'created_at' => date('Y-m-d H:i:s'),
        'last_login' => null
    ];
    
    $users[] = $newUser;
    
    if (saveUsers($users)) {
        return ['success' => true, 'message' => 'Usuário registrado com sucesso!', 'user' => $newUser];
    } else {
        return ['success' => false, 'message' => 'Erro ao salvar usuário'];
    }
}

/**
 * Registra um novo usuário (versão básica)
 */
function registerUser($name, $email, $password) {
    return registerUserExtended($name, '', $email, $password);
}

/**
 * Autentica usuário
 */
function authenticateUser($email, $password) {
    $users = loadUsers();
    
    foreach ($users as &$user) {
        if ($user['email'] === strtolower(trim($email))) {
            if (password_verify($password, $user['password'])) {
                // Atualiza último login
                $user['last_login'] = date('Y-m-d H:i:s');
                saveUsers($users);
                
                // Retorna usuário sem a senha
                $userData = $user;
                unset($userData['password']);
                return ['success' => true, 'user' => $userData];
            } else {
                return ['success' => false, 'message' => 'Senha incorreta'];
            }
        }
    }
    
    return ['success' => false, 'message' => 'Usuário não encontrado'];
}

/**
 * Verifica se usuário está logado
 */
function isLoggedIn() {
    return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
}

/**
 * Obtém dados do usuário logado
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return $_SESSION['user_data'] ?? null;
}

/**
 * Faz login do usuário
 */
function loginUser($user) {
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_data'] = $user;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
}

/**
 * Faz logout do usuário
 */
function logoutUser() {
    unset($_SESSION['user_logged_in']);
    unset($_SESSION['user_data']);
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']);
    unset($_SESSION['user_email']);
    session_destroy();
}

/**
 * Middleware para proteger páginas que requerem login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Middleware para redirecionar usuários logados
 */
function redirectIfLoggedIn($redirectTo = 'index.php') {
    if (isLoggedIn()) {
        header("Location: $redirectTo");
        exit;
    }
}

?>