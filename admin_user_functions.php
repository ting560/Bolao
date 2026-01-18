<?php
/**
 * Funções de Gerenciamento de Usuários para o Painel Administrativo
 * Arquivo: admin_user_functions.php
 */

require_once 'auth_functions.php';

/**
 * Obtém todos os usuários cadastrados
 */
function adminGetAllUsers() {
    return loadUsers();
}

/**
 * Obtém um usuário específico pelo ID
 */
function adminGetUserById($userId) {
    $users = loadUsers();
    
    foreach ($users as $user) {
        if ($user['id'] === $userId) {
            return $user;
        }
    }
    
    return null;
}

/**
 * Adiciona um novo usuário (admin)
 */
function adminAddUser($name, $phone, $email, $password) {
    // Validar dados
    $validation = validateUserData($name, $phone, $email, $password);
    if (!$validation['valid']) {
        return ['success' => false, 'message' => implode(', ', $validation['errors'])];
    }
    
    $users = loadUsers();
    
    // Verifica se email já existe
    $emailLower = strtolower(trim($email));
    foreach ($users as $user) {
        if (isset($user['email']) && $user['email'] === $emailLower) {
            return ['success' => false, 'message' => 'Email já cadastrado'];
        }
    }
    
    // Cria novo usuário
    $newUser = [
        'id' => uniqid('user_', true), // Mais seguro
        'name' => trim($name),
        'phone' => trim($phone),
        'email' => $emailLower,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'created_at' => date('Y-m-d H:i:s'),
        'last_login' => null,
        'is_active' => true,
        'role' => 'user', // Pode ser 'user' ou 'admin'
        'source' => 'local' // Identificar como usuário local
    ];
    
    $users[] = $newUser;
    
    if (saveUsers($users)) {
        // Registrar atividade
        adminLogActivity('admin', 'add_user', "Usuário {$name} adicionado");
        return ['success' => true, 'message' => 'Usuário adicionado com sucesso!', 'user' => $newUser];
    } else {
        return ['success' => false, 'message' => 'Erro ao salvar usuário'];
    }
}

/**
 * Atualiza dados de um usuário
 */
function adminUpdateUser($userId, $name, $phone, $email, $isActive = true) {
    // Validar dados (sem senha na edição)
    $validation = validateUserData($name, $phone, $email);
    if (!$validation['valid']) {
        return ['success' => false, 'message' => implode(', ', $validation['errors'])];
    }
    
    $users = loadUsers();
    $userFound = false;
    $oldUserData = null;
    $emailLower = strtolower(trim($email));
    
    foreach ($users as $index => $user) {
        if ($user['id'] === $userId) {
            $oldUserData = $user;
            $userFound = true;
            
            // Verifica se email já existe (exceto para o próprio usuário)
            foreach ($users as $otherUser) {
                if ($otherUser['id'] !== $userId && isset($otherUser['email']) && $otherUser['email'] === $emailLower) {
                    return ['success' => false, 'message' => 'Email já cadastrado por outro usuário'];
                }
            }
            
            // Atualiza dados mantendo campos existentes
            $users[$index]['name'] = trim($name);
            $users[$index]['phone'] = trim($phone);
            $users[$index]['email'] = $emailLower;
            $users[$index]['is_active'] = $isActive;
            
            // Manter outros campos importantes
            if (!isset($users[$index]['source'])) {
                $users[$index]['source'] = 'local';
            }
            
            break;
        }
    }
    
    if (!$userFound) {
        return ['success' => false, 'message' => 'Usuário não encontrado'];
    }
    
    if (saveUsers($users)) {
        // Registrar atividade
        adminLogActivity('admin', 'update_user', "Usuário {$oldUserData['name']} atualizado");
        return ['success' => true, 'message' => 'Usuário atualizado com sucesso!'];
    } else {
        return ['success' => false, 'message' => 'Erro ao atualizar usuário'];
    }
}

/**
 * Altera a senha de um usuário
 */
function adminChangeUserPassword($userId, $newPassword) {
    $users = loadUsers();
    $userName = '';
    
    foreach ($users as $index => $user) {
        if ($user['id'] === $userId) {
            $userName = $user['name'];
            $users[$index]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            break;
        }
    }
    
    if (empty($userName)) {
        return ['success' => false, 'message' => 'Usuário não encontrado'];
    }
    
    if (saveUsers($users)) {
        // Registrar atividade
        adminLogActivity('admin', 'change_password', "Senha do usuário {$userName} alterada");
        return ['success' => true, 'message' => 'Senha alterada com sucesso!'];
    } else {
        return ['success' => false, 'message' => 'Erro ao alterar senha'];
    }
}

/**
 * Exclui um usuário
 */
function adminDeleteUser($userId) {
    $users = loadUsers();
    $userName = '';
    $newUsers = [];
    
    foreach ($users as $user) {
        if ($user['id'] === $userId) {
            $userName = $user['name'];
        } else {
            $newUsers[] = $user;
        }
    }
    
    if (empty($userName)) {
        return ['success' => false, 'message' => 'Usuário não encontrado'];
    }
    
    if (saveUsers($newUsers)) {
        // Registrar atividade
        adminLogActivity('admin', 'delete_user', "Usuário {$userName} excluído");
        return ['success' => true, 'message' => 'Usuário excluído com sucesso!'];
    } else {
        return ['success' => false, 'message' => 'Erro ao excluir usuário'];
    }
}

/**
 * Bloqueia/Desbloqueia um usuário
 */
function adminToggleUserStatus($userId) {
    $users = loadUsers();
    $userName = '';
    $newStatus = false;
    
    foreach ($users as $index => $user) {
        if ($user['id'] === $userId) {
            $userName = $user['name'];
            $newStatus = !$user['is_active'];
            $users[$index]['is_active'] = $newStatus;
            break;
        }
    }
    
    if (empty($userName)) {
        return ['success' => false, 'message' => 'Usuário não encontrado'];
    }
    
    if (saveUsers($users)) {
        $statusText = $newStatus ? 'desbloqueado' : 'bloqueado';
        // Registrar atividade
        adminLogActivity('admin', 'toggle_user', "Usuário {$userName} {$statusText}");
        return ['success' => true, 'message' => "Usuário {$statusText} com sucesso!"];
    } else {
        return ['success' => false, 'message' => 'Erro ao atualizar status do usuário'];
    }
}

/**
 * Obtém estatísticas de usuários
 */
function adminGetUserStats() {
    $users = loadUsers();
    $totalUsers = count($users);
    $activeUsers = 0;
    $inactiveUsers = 0;
    
    foreach ($users as $user) {
        if (isset($user['is_active']) && $user['is_active']) {
            $activeUsers++;
        } else {
            $inactiveUsers++;
        }
    }
    
    return [
        'total' => $totalUsers,
        'active' => $activeUsers,
        'inactive' => $inactiveUsers,
        'recent_users' => array_slice(array_reverse($users), 0, 5) // Últimos 5 usuários
    ];
}

/**
 * Registra atividade administrativa
 */
function adminLogActivity($admin, $action, $description) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'admin' => $admin,
        'action' => $action,
        'description' => $description
    ];
    
    $logFile = 'admin_activity.log';
    $logLine = json_encode($logEntry) . "\n";
    file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
}

/**
 * Obtém últimas atividades administrativas
 */
function adminGetRecentActivities($limit = 10) {
    $logFile = 'admin_activity.log';
    $activities = [];
    
    if (file_exists($logFile)) {
        $lines = file($logFile, FILE_IGNORE_NEW_LINES);
        $lines = array_reverse($lines);
        
        foreach (array_slice($lines, 0, $limit) as $line) {
            $activity = json_decode($line, true);
            if ($activity) {
                $activities[] = $activity;
            }
        }
    }
    
    return $activities;
}

/**
 * Formata data para exibição
 */
function formatDate($dateString) {
    if (empty($dateString)) return 'N/A';
    
    $date = new DateTime($dateString);
    return $date->format('d/m/Y H:i');
}

/**
 * Valida dados de usuário
 */
function validateUserData($name, $phone, $email, $password = null) {
    $errors = [];
    
    // Validar nome
    if (empty(trim($name))) {
        $errors[] = 'Nome é obrigatório';
    } elseif (strlen(trim($name)) < 3) {
        $errors[] = 'Nome deve ter pelo menos 3 caracteres';
    }
    
    // Validar telefone (opcional para edição)
    if ($password !== null || !empty(trim($phone))) { // Obrigatório na criação ou quando preenchido
        if (empty(trim($phone))) {
            $errors[] = 'Telefone é obrigatório';
        } elseif (!preg_match('/^[\d\(\)\-\s+]+$/', $phone)) {
            $errors[] = 'Telefone inválido';
        } elseif (strlen(preg_replace('/[^\d]/', '', $phone)) < 10) {
            $errors[] = 'Telefone deve ter pelo menos 10 dígitos';
        }
    }
    
    // Validar email
    if (empty(trim($email))) {
        $errors[] = 'Email é obrigatório';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email inválido';
    }
    
    // Validar senha (obrigatória apenas na criação)
    if ($password !== null) {
        if (empty($password)) {
            $errors[] = 'Senha é obrigatória';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Senha deve ter pelo menos 6 caracteres';
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

?>