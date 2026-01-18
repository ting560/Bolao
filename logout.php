<?php
session_start();
require_once 'auth_functions.php';

// Faz logout do usuário
logoutUser();

// Redireciona para a página inicial com mensagem de logout
header('Location: index.php?logged_out=1');
exit;
?>