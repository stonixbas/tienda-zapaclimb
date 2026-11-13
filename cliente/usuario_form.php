<?php
/* usuario_form.php en la capa cliente: formulario con el que el administrador
   da de alta un usuario nuevo (cliente o admin), y tambien sirve para modificarlo si llega
   ? id= en la URL. Tiene los mismos campos que el registro más el rol,
   y envía los datos por POST a negocio/guardar_usuario.php.*/
session_start();
require_once "../datos/conexion.php";
// Mismo guardián doble que la lista: este formulario también es solo para el admin
if (!isset($_SESSION["id_usuario"]) || $_SESSION["rol"] !== "admin") {
  header("Location: login.php");
  exit;
}
// Valores por defecto = formulario vacío (modo CREAR)
$usuario = [
  "nombre_apellidos" => "",
  "nombre_usuario"   => "",
  "correo"           => "",
  "direccion"        => "",
  "telefono"         => "",
  "genero"           => "",
  "fecha_nacimiento" => "",
  "rol"              => "cliente"
];
$id = null;

// ¿Viene un id en la URL? → modo MODIFICAR
if (isset($_GET["id"])) {
  /* El id llega por la URL: el usuario puede escribir cualquier cosa ("abc", "-5"...).
     filter_var con FILTER_VALIDATE_INT devuelve el número o false si no es un entero. */
  $id = filter_var($_GET["id"], FILTER_VALIDATE_INT);
  if ($id === false) {
    header("Location: admin_usuarios.php");
    exit;
  }

  try {
    $bd = conectar();
    // Aquí SÍ hay un dato del usuario (el id) → consulta preparada
    $consulta = $bd->prepare("SELECT nombre_apellidos, nombre_usuario, correo, direccion, telefono, genero, fecha_nacimiento, rol FROM usuario WHERE id_usuario = :id");
    $consulta->execute([":id" => $id]);
    $usuario = $consulta->fetch();
  } catch (PDOException $e) {
    die("No se ha podido cargar el usuario.");
  }

  // Un id que no existe (ej. ?id=999) → fetch() devuelve false, igual que en el login
  if ($usuario === false) {
    header("Location: admin_usuarios.php");
    exit;
  }
}

// Una variable con nombre claro para no repetir "$id !== null" por todo el HTML
$editando = ($id !== null);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- El operador ternario (condición ? sí : no) elige el texto según el modo -->
  <title><?= $editando ? "Modificar usuario" : "Nuevo usuario" ?></title>
  <!-- Icono de la pestaña y hoja de estilos común (capa cliente: CSS) -->
  <link rel="icon" href="img/logo.svg">
  <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
  <?php require "cabecera.php"; // logo + menú común ?>
  <h1><?= $editando ? "Modificar usuario" : "Nuevo usuario" ?></h1>

  <form method="post" action="../negocio/guardar_usuario.php">

    <?php if ($editando) { ?>
      <!-- El id viaja OCULTO para que guardar sepa QUÉ usuario actualizar.
           Ojo: aunque esté oculto se puede cambiar con F12 → guardar lo vuelve a validar. -->
      <input type="hidden" name="id_usuario" value="<?= $id ?>">
    <?php } ?>

    <label for="nombre_apellidos">Nombre completo *</label>
    <input type="text" id="nombre_apellidos" name="nombre_apellidos" maxlength="100" required
    value="<?= htmlspecialchars($usuario["nombre_apellidos"]) ?>">
  
    <label for="nombre_usuario">Usuario *</label>
    <input type="text" id="nombre_usuario" name="nombre_usuario" maxlength="40" required
    value="<?= htmlspecialchars($usuario["nombre_usuario"]) ?>">

    <label for="correo">Correo electrónico *</label>
    <input type="email" id="correo" name="correo" maxlength="150" required
    value="<?= htmlspecialchars($usuario["correo"]) ?>">

    <!-- Contraseña INICIAL: al crear hace falta (sin ella el usuario nunca podría entrar). -->
    <?php if (!$editando) { ?>
      <label for="password">Contraseña inicial *</label>
      <input type="password" id="password" name="password" minlength="8" required>
    <?php } ?>

    <label for="direccion">Dirección *</label>
    <input type="text" id="direccion" name="direccion" maxlength="150" required
    value="<?= htmlspecialchars($usuario["direccion"]) ?>">

    <label for="telefono">Teléfono *</label>
    <input type="tel" id="telefono" name="telefono" maxlength="15" required
    value="<?= htmlspecialchars($usuario["telefono"]) ?>">

    <!-- DIFERENCIA CON EL REGISTRO: aquí el rol SÍ se elige en el formulario.
         Es aceptable porque a esta página solo llega el admin (guardián),
         pero el servidor volverá a comprobar el valor: un select también se puede manipular.
         En un select no hay value="...": se marca con "selected" la opción que coincide
         con lo guardado en la BD. -->
    <label for="rol">Rol *</label>
    <select id="rol" name="rol" required>
      <option value="cliente" <?= $usuario["rol"] === "cliente" ? "selected" : "" ?>>Cliente</option>
      <option value="admin" <?= $usuario["rol"] === "admin" ? "selected" : "" ?>>Admin</option>
    </select>

    <!-- El género puede ser NULL en la BD: con ?? "" se trata como "Sin indicar" -->
    <label for="genero">Género</label>
    <select id="genero" name="genero">
      <option value="" <?= ($usuario["genero"] ?? "") === "" ? "selected" : "" ?>>-- Sin indicar --</option>
      <option value="masculino" <?= $usuario["genero"] === "masculino" ? "selected" : "" ?>>Masculino</option>
      <option value="femenino" <?= $usuario["genero"] === "femenino" ? "selected" : "" ?>>Femenino</option>
    </select>

    <label for="fecha_nacimiento">Fecha de nacimiento</label>
    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento"
    value="<?= htmlspecialchars($usuario["fecha_nacimiento"] ?? "") ?>">

    <button type="submit">Guardar</button>
  </form>

  <p><a href="admin_usuarios.php">Volver a la lista</a></p>
<?php require "pie.php"; ?>
</body>
</html>