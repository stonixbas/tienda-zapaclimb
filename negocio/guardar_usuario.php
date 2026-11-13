<?php
/* guardar_usuario.php en la capa de negocio: recibe el formulario de alta o de modificación
  del administrador (usuario_form.php), comprueba que quien envía es un admin, valida
  los datos en el servidor igual que el registro y guarda el usuario con el rol
  elegido (cliente o admin). Al terminar vuelve a la lista de usuarios.
  Si llega el id oculto, actualiza ese usuario (sin tocar la contraseña); si no, lo crea */
session_start();
require_once "../datos/conexion.php";
require_once "../cliente/plantilla_mensaje.php"; // los mensajes de esta página salen con el diseño común

/* Este archivo vuelve a comprobar que quien envía es un admin. El guardián del
   formulario no basta: cualquiera podría mandar un POST directamente aquí (con curl
   o con un formulario propio) sin pasar por usuario_form.php. Por eso cada archivo
   que hace algo de admin se protege por sí mismo. */
if (!isset($_SESSION["id_usuario"]) || $_SESSION["rol"] !== "admin") {
  header("Location: ../cliente/login.php");
  exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Location: ../cliente/admin_usuarios.php");
  exit;
}

$nombre_apellidos = trim($_POST["nombre_apellidos"] ?? "");
$nombre_usuario = trim($_POST["nombre_usuario"] ?? "");
$correo = trim($_POST["correo"] ?? "");
$password = ($_POST["password"] ?? ""); // no lleva trim por si un espacio es parte de la contraseña
$direccion = trim($_POST["direccion"] ?? "");
$telefono = trim($_POST["telefono"] ?? "");
$genero = trim($_POST["genero"] ?? "");
$fecha_nacimiento = trim($_POST["fecha_nacimiento"] ?? "");
$rol = $_POST["rol"] ?? "";
$id = $_POST["id_usuario"] ?? "";
$editando = ($id !== "");

$errores = [];
// El id oculto se puede cambiar con F12: se valida como cualquier otro dato
if ($editando) {
  $id = filter_var($id, FILTER_VALIDATE_INT);
  if ($id === false) {
    $errores[] = "Usuario no válido.";
  }
}

if ($nombre_apellidos === "" || $nombre_usuario === "" || $correo  === "" ||
 $direccion  === "" || $telefono  === "") {
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
if (!$editando && strlen($password) < 8) {
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
/* LISTA BLANCA del rol: solo se aceptan los dos valores previstos.
   Sin esto, alguien podría mandar rol = "superjefe" y se guardaría tal cual. */
if (!in_array($rol, ["cliente", "admin"], true)) {
  $errores[] = "Rol no válido.";
}

// Un admin no puede quitarse su propio rol: perdería el acceso al panel
// (y si es el único admin, la tienda se quedaría sin ninguno)
if ($editando && $id == $_SESSION["id_usuario"] && $rol !== "admin") {
  $errores[] = "No puedes quitarte a ti mismo el rol de administrador.";
}

if (count($errores) > 0) {
  foreach ($errores as $error) {
    echo "<p>" . $error . "</p>";
  }
  echo '<a href="javascript:history.back()">Volver al formulario</a>';
  exit;
}

// Los campos opcionales vacíos se guardan como NULL, no como texto vacío.
$genero   = ($genero === "") ? null : $genero;
$fecha_nacimiento = ($fecha_nacimiento === "") ? null : $fecha_nacimiento;

try {
  $bd = conectar();

  if ($editando) {
    /* MODIFICAR: UPDATE sin la columna password (la contraseña no se toca desde aquí,
       la cambia el propio usuario). El WHERE es imprescindible: sin él se
       modificarían TODOS los usuarios de la tabla. */
    $sql = "UPDATE usuario SET nombre_apellidos = :nombre_apellidos, nombre_usuario = :nombre_usuario,
              rol = :rol, correo = :correo, direccion = :direccion, telefono = :telefono,
              genero = :genero, fecha_nacimiento = :fecha_nacimiento
            WHERE id_usuario = :id";
    $datos = [
      ":nombre_apellidos" => $nombre_apellidos,
      ":nombre_usuario" => $nombre_usuario,
      ":rol" => $rol,
      ":correo" => $correo,
      ":direccion" => $direccion,
      ":telefono" => $telefono,
      ":genero" => $genero,
      ":fecha_nacimiento" => $fecha_nacimiento,
      ":id" => $id
    ];
  } else {
    // CREAR: el INSERT de siempre, con el rol como hueco (:rol) y la contraseña cifrada
    $sql = "INSERT INTO usuario (nombre_apellidos, nombre_usuario, rol, correo, password, direccion, telefono, genero, fecha_nacimiento)
            VALUES (:nombre_apellidos, :nombre_usuario, :rol, :correo, :password, :direccion, :telefono, :genero, :fecha_nacimiento)";
    $datos = [
      ":nombre_apellidos" => $nombre_apellidos,
      ":nombre_usuario" => $nombre_usuario,
      ":rol" => $rol,
      ":correo" => $correo,
      ":password" => password_hash($password, PASSWORD_DEFAULT),
      ":direccion" => $direccion,
      ":telefono" => $telefono,
      ":genero" => $genero,
      ":fecha_nacimiento" => $fecha_nacimiento
    ];
  }

  // Un solo prepare/execute para los dos casos: solo cambian el SQL y los datos
  $consulta = $bd->prepare($sql);
  $consulta->execute($datos);
} catch (PDOException $e) {
  if ($e->getCode() === "23000") {
    echo "<p>Ese usuario o correo ya está registrado.</p>";
  } else {
    echo "<p>No se ha podido guardar el usuario. Inténtalo más tarde.</p>";
  }
  echo '<a href="javascript:history.back()">Volver al formulario</a>';
  exit;
}

/* Todo bien → de vuelta a la lista, donde ya aparece el nuevo usuario o modificado.
   Redirigir (en vez de mostrar un mensaje aquí) evita que, si el admin
   pulsa F5, el navegador reenvíe el formulario y se intente crear otra vez. */
header("Location: ../cliente/admin_usuarios.php");
exit;