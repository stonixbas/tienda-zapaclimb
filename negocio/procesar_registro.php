<?php
/* procesar_registro.php en la capa de negocio, recibe el formulario de alta del cliente, 
valida los datos en el servidor y , si todo esta bien, guarda el cliente en la base de datos */

require_once "../datos/conexion.php";
require_once "../cliente/plantilla_mensaje.php"; // los mensajes de esta página salen con el diseño común

/* Solo se acepta el envio del formulario en post. si alguien abre esta url directamente en el 
navegador, se le devuelve al formulario. */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Location: ../cliente/registro.php");
  exit;
}

/* Recoger los datos con trim(), quita los espacios del principio y del final.
?? "" pone un texto vacío si el campo no ha llegado asi evitando avisos de php */
$nombre_apellidos = trim($_POST["nombre_apellidos"] ?? "");
$nombre_usuario = trim($_POST["nombre_usuario"] ?? "");
$correo = trim($_POST["correo"] ?? "");
$password = ($_POST["password"] ?? ""); // no lleva trim por si un espacio es parte de la contraseña
$direccion = trim($_POST["direccion"] ?? "");
$telefono = trim($_POST["telefono"] ?? "");
$genero = trim($_POST["genero"] ?? "");
$fecha_nacimiento = trim($_POST["fecha_nacimiento"] ?? "");

/* validar en el servidor, el "required" del HTML se puede saltar facilmente,
asi que aqui se vuelve a comprobar todo, los errores se guardan en un array */
$errores = [];

if ($nombre_apellidos === "" || $nombre_usuario === "" || $correo  === "" || 
$password  === "" || $direccion  === "" || $telefono  === "") {
  $errores[] = "Rellena todos los campos obligatorios.";
}
if (mb_strlen($nombre_apellidos) > 100) {
  $errores[] = "El nombre no puede pasar de 100 caracteres.";
}
if (mb_strlen($nombre_usuario) > 40) {
  $errores[] = "El usuario no puede pasar de 40 caracteres.";
}
if (mb_strlen($correo) > 150) {
  $errores[] = "El correo no puede pasar de 150 caracteres.";
}
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
  $errores[] = "El correo no tiene un formato válido.";
}
if (strlen($password) < 8) {
  $errores[] = "La contraseña debe tener al menos 8 caracteres.";
}
if (mb_strlen($direccion) > 150) {
  $errores[] = "La dirección no puede pasar de 150 caracteres.";
}
// Quita los espacios: "612 34 56 78" pasa a ser "612345678"
$telefono = str_replace(" ", "", $telefono);
// Acepta 9 cifras, con o sin el prefijo +34 delante
if (!preg_match('/^(\+34)?[0-9]{9}$/', $telefono)) {
  $errores[] = "El teléfono debe tener 9 cifras (puede llevar +34 delante).";
} else {
  // Si es válido y lleva +34, se le quita: en la BD se guarda siempre igual
  if (str_starts_with($telefono, "+34")) {
    $telefono = substr($telefono, 3);
  }
}
// Solo se aceptan los valores del select: alguien podría enviar otro a mano
if (!in_array($genero, ["", "masculino", "femenino"], true)) {
  $errores[] = "Género no válido.";
}

// Si hay errores, se muestran y se para aquí: no se toca la base de datos
if (count($errores) > 0) {
  foreach ($errores as $error) {
    echo "<p>" . $error . "</p>";
  }
  echo '<a href="../cliente/registro.php">Volver al formulario</a>';
  exit;
}

// Los campos opcionales vacíos se guardan como NULL, no como texto vacío.
$genero   = ($genero === "") ? null : $genero;
$fecha_nacimiento = ($fecha_nacimiento === "") ? null : $fecha_nacimiento;

/* Cifrar la contraseña. Nunca se guarda tal cual: password_hash genera
un hash con sal aleatoria que luego se comprueba con password_verify. */
$password_hash = password_hash($password, PASSWORD_DEFAULT);

/* Guardar con una consulta preparada. Los :nombres son huecos que se rellenan
en execute(), separando el SQL de los datos (protege contra la inyección SQL).
El rol lo pone el servidor, nunca el formulario. */
$sql = "INSERT INTO usuario (nombre_apellidos, nombre_usuario, rol, correo, password, direccion, telefono , genero, fecha_nacimiento)
        VALUES (:nombre_apellidos , :nombre_usuario , 'cliente' , :correo , :password , :direccion , :telefono , :genero, :fecha_nacimiento)";

try {
  $conexion = conectar();
  $consulta = $conexion->prepare($sql);
  $consulta->execute([
    ":nombre_apellidos" => $nombre_apellidos,
    ":nombre_usuario" => $nombre_usuario,
    ":correo" => $correo,
    ":password" => $password_hash,
    ":direccion" => $direccion,
    ":telefono" => $telefono,
    ":genero" => $genero,
    ":fecha_nacimiento" => $fecha_nacimiento
  ]);

  // htmlspecialchars evita que un nombre con código HTML/JS se ejecute en la página
  echo "<p>Alta completada. ¡Bienvenido, " . htmlspecialchars($nombre_usuario) . "!</p>";
  // Siguiente paso natural tras el alta: entrar con la cuenta nueva
  echo '<a href="../cliente/login.php">Entrar</a>';
} 
catch (PDOException $e) {
  // 23000 = se ha incumplido una restricción, aquí el UNIQUE (usuario o email repetido)
  if ($e->getCode() === "23000") {
    echo "<p>Ese usuario o correo ya está registrado.</p>";
  } else {
    // Cualquier otro error: mensaje genérico, sin mostrar el error real
    echo "<p>No se pudo completar el alta de usuario. Inténtalo más tarde.</p>";
  }
  echo '<a href="../cliente/registro.php">Volver al formulario</a>';
}