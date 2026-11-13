<?php

// Hay que "abrir" la sesión para poder destruirla: sin esto PHP no sabe cuál es
session_start();

// 1. Vacía los datos de la sesión (id_usuario, usuario, rol...)
$_SESSION = [];

// 2. Destruye la sesión en el servidor. La "llave" que tenga el navegador ya no abre nada
session_destroy();

// 3. De vuelta al formulario de acceso
header("Location: ../cliente/login.php");
exit;