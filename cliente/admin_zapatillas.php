<?php
/* admin_zapatillas.php en la capa cliente: lista de zapatillas (modelos) para el
   administrador. Desde aquí se crea un modelo nuevo, se modifica, se elimina o se
   entra a gestionar sus tallas y su stock (tallas.php). Solo para el rol admin. */

session_start();
require_once "../datos/conexion.php";

/* Guardián DOBLE, igual que en admin_usuarios.php:
   sesión iniciada + rol admin (leído de $_SESSION, que vive en el servidor). */
if (!isset($_SESSION["id_usuario"]) || $_SESSION["rol"] !== "admin") {
  header("Location: login.php");
  exit;
}

try {
  $bd = conectar();
  /* Sin datos del usuario en la consulta → query() basta, no hace falta prepare().
     Las tallas y el stock no se piden aquí: se ven en tallas.php de cada zapatilla. */
  $consulta = $bd->query("SELECT id_zapatilla, modelo, color, precio, imagen FROM zapatilla ORDER BY modelo");
  $zapatillas = $consulta->fetchAll(); // todas las filas en un array
} catch (PDOException $e) {
  die("No se ha podido cargar la lista de zapatillas.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestión de zapatillas</title>
  <!-- Icono de la pestaña y hoja de estilos común (capa cliente: CSS) -->
  <link rel="icon" href="img/logo.svg">
  <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
  <?php require "cabecera.php"; // logo + menú común ?>
  <h1>Gestión de zapatillas</h1>
  <p><a href="zapatilla_form.php">Nueva zapatilla</a> · <a href="inicio.php">Volver</a></p>

  <table border="1">
    <tr><th>Foto</th><th>Modelo</th><th>Color</th><th>Precio</th><th>Acciones</th></tr>
    <?php foreach ($zapatillas as $zapatilla) { ?>
      <tr>
        <!-- Miniatura: la foto si tiene, si no el dibujo genérico -->
        <td>
          <img class="miniatura" alt=""
               src="<?= $zapatilla["imagen"] !== null ? "img/zapatillas/" . htmlspecialchars($zapatilla["imagen"]) : "img/zapatilla.svg" ?>">
        </td>
        <!-- Texto escrito por el admin → htmlspecialchars (XSS) -->
        <td><?= htmlspecialchars($zapatilla["modelo"]) ?></td>
        <td><?= htmlspecialchars($zapatilla["color"]) ?></td>
        <!-- number_format: formateo de representacion de la , y el . -->
        <td><?= number_format($zapatilla["precio"], 2, ",", ".") ?> €</td>
        <td>
          <!-- Tallas y Modificar: enlaces GET, solo abren otra página (no cambian nada) -->
          <a href="tallas.php?id=<?= $zapatilla["id_zapatilla"] ?>">Tallas y stock</a>
          <a href="zapatilla_form.php?id=<?= $zapatilla["id_zapatilla"] ?>">Modificar</a>

          <!-- Eliminar SÍ cambia datos → POST con el id oculto.
               Aquí no hay "no borrarse a sí mismo": una zapatilla no es una cuenta.
               Por el ON DELETE CASCADE de fk_talla_zapatilla, sus tallas se borran con ella. -->
          <form action="../negocio/eliminar_zapatilla.php" method="post" style="display:inline">
            <input type="hidden" name="id_zapatilla" value="<?= $zapatilla["id_zapatilla"] ?>">
            <button type="submit" onclick="return confirm('¿Eliminar esta zapatilla y todas sus tallas?')">Eliminar</button>
          </form>
        </td>
      </tr>
    <?php } ?>
  </table>
<?php require "pie.php"; ?>
</body>
</html>