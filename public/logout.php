<?php

declare(strict_types=1);
require dirname(__DIR__) . '/app/inicio.php';

// El cierre de sesión solo se acepta por POST con token CSRF
if (!esPost()) {
    redirigir('/');
}
validarCsrf();
cerrarSesion();
redirigir('/login.php');
