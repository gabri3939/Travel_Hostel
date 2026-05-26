<?php
// Inicia a sessao para manter possiveis dados de usuario ou mensagens.
session_start();

// Redireciona tudo para o controlador de rotas central do sistema.
header("location: controller/router.php");
