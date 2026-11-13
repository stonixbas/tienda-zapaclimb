<?php


session_start();
require_once "../datos/conexion.php";

/* Guardián DOBLE:
   1. ¿Hay sesión? Si no, no ha pasado por el login.
   2. ¿Es admin? Un usuario normal también tiene sesión, así que con el 1 solo no basta.
   El rol se lee de $_SESSION, que vive en el SERVIDOR: el usuario no puede cambiarlo
   (no es como un campo de formulario o la URL, que sí puede manipular). */
if (!isset($_SESSION["id_usuario"]) || $_SESSION["rol"] !== "admin") {
  header("Location: login.php");
  exit;
}

try {
  $bd = conectar();
  /* Sin datos del usuario en la consulta → no hace falta prepare(); query() basta.
  Nunca se pide la columna de la contraseña: aquí no se usa. */
  $consulta = $bd->query("SELECT id_usuario, nombre_usuario, correo, rol FROM usuario ORDER BY nombre_usuario");
  $usuarios = $consulta->fetchAll(); // todas las filas en un array
} catch (PDOException $e) {
  die("No se ha podido cargar la lista de usuarios.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestión de usuarios</title>
  <!-- Icono de la pestaña y hoja de estilos común (capa cliente: CSS) -->
  <link rel="icon" href="img/logo.svg">
  <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
  <?php require "cabecera.php"; // logo + menú común ?>
  <h1>Gestión de usuarios</h1>
  <p><a href="usuario_form.php">Nuevo usuario</a> · <a href="inicio.php">Volver</a></p>

  <table border="1">
    <tr><th>Usuario</th><th>Correo</th><th>Rol</th><th>Acciones</th></tr>
    <?php foreach ($usuarios as $usuario) { ?>
      <tr>
        <!-- Todo lo que escribió un usuario se pinta con htmlspecialchars (XSS) -->
        <td><?= htmlspecialchars($usuario["nombre_usuario"]) ?></td>
        <td><?= htmlspecialchars($usuario["correo"]) ?></td>
        <td><?= htmlspecialchars($usuario["rol"]) ?></td>
        <td>
          <!-- Modificar: un enlace GET está bien, solo ABRE el formulario (no cambia nada) -->
          <a href="usuario_form.php?id=<?= $usuario["id_usuario"] ?>">Modificar</a>

          <?php if ($usuario["id_usuario"] != $_SESSION["id_usuario"]) { ?>
            <!-- Eliminar SÍ cambia datos → POST con el id oculto.
                 Ocultar el botón en la propia fila es solo comodidad:
                 la comprobación de verdad irá en negocio/eliminar_usuario.php -->
            <form action="../negocio/eliminar_usuario.php" method="post" style="display:inline">
              <input type="hidden" name="id_usuario" value="<?= $usuario["id_usuario"] ?>">
              <button type="submit" onclick="return confirm('¿Eliminar este usuario?')">Eliminar</button>
            </form>
          <?php } ?>
        </td>
      </tr>
    <?php } ?>
  </table>
<?php require "pie.php"; ?>
</body>
</html>