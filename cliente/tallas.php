<?php
/* tallas.php en la capa cliente: tallas y stock de UNA zapatilla (llega ?id= en la URL).
   El administrador ve sus tallas, cambia el stock de cada una, añade tallas nuevas y
   elimina las que no se hayan vendido. Solo para el rol admin. */
session_start();
require_once "../datos/conexion.php";

// Mismo guardián doble que el resto de páginas del admin
if (!isset($_SESSION["id_usuario"]) || $_SESSION["rol"] !== "admin") {
  header("Location: login.php");
  exit;
}

// Sin un id de zapatilla válido esta página no tiene sentido → a la lista
$id_zapatilla = filter_var($_GET["id"] ?? "", FILTER_VALIDATE_INT);
if ($id_zapatilla === false) {
  header("Location: admin_zapatillas.php");
  exit;
}

try {
  $bd = conectar();

  // 1. La zapatilla: para el título y para comprobar que existe
  $consulta = $bd->prepare("SELECT modelo, color FROM zapatilla WHERE id_zapatilla = :id");
  $consulta->execute([":id" => $id_zapatilla]);
  $zapatilla = $consulta->fetch();

  // 2. Sus tallas: el WHERE por la FK (id_zapatilla) trae solo las de ESTA zapatilla
  $consulta = $bd->prepare("SELECT id_talla, numero_talla, stock FROM talla
                            WHERE id_zapatilla = :id ORDER BY numero_talla");
  $consulta->execute([":id" => $id_zapatilla]);
  $tallas = $consulta->fetchAll();
} catch (PDOException $e) {
  die("No se han podido cargar las tallas.");
}

// Un id que no existe (ej. ?id=999) → fetch() devuelve false → vuelta a la lista
if ($zapatilla === false) {
  header("Location: admin_zapatillas.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tallas de <?= htmlspecialchars($zapatilla["modelo"]) ?></title>
  <!-- Icono de la pestaña y hoja de estilos común (capa cliente: CSS) -->
  <link rel="icon" href="img/logo.svg">
  <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
  <?php require "cabecera.php"; // logo + menú común ?>
  <h1>Tallas de «<?= htmlspecialchars($zapatilla["modelo"]) ?>» (<?= htmlspecialchars($zapatilla["color"]) ?>)</h1>
  <p><a href="admin_zapatillas.php">Volver a la lista de zapatillas</a></p>

  <?php if (count($tallas) === 0) { ?>
    <!-- Lista vacía: mejor un mensaje que una tabla sin filas -->
    <p>Esta zapatilla todavía no tiene tallas. Añade la primera abajo.</p>
  <?php } else { ?>
    <table border="1">
      <tr><th>Talla</th><th>Stock</th><th>Estado</th><th>Eliminar</th></tr>
      <?php foreach ($tallas as $talla) { ?>
        <tr>
          <!-- En la BD la talla es decimal(3,1): 42.0 y 42.5. (float) quita el ".0"
               y str_replace pone la coma española → se ve "42" o "42,5" -->
          <td><?= str_replace(".", ",", (float) $talla["numero_talla"]) ?></td>
          <td>
            <!-- Un formulario POR FILA para cambiar el stock de esa talla.
                 Lleva oculto el id de la talla (qué fila cambiar) y el de la zapatilla (a dónde volver). -->
            <form action="../negocio/guardar_talla.php" method="post">
              <input type="hidden" name="id_talla" value="<?= $talla["id_talla"] ?>">
              <input type="hidden" name="id_zapatilla" value="<?= $id_zapatilla ?>">
              <input type="number" name="stock" min="0" step="1" required
              value="<?= $talla["stock"] ?>">
              <button type="submit">Guardar stock</button>
            </form>
          </td>
          <!-- Decisión de diseño: con stock 0 la talla NO se borra, se muestra "Sin stock"
               y en la tienda no se podrá comprar -->
          <td><?= $talla["stock"] > 0 ? "Disponible" : "Sin stock" ?></td>
          <td>
            <form action="../negocio/eliminar_talla.php" method="post">
              <input type="hidden" name="id_talla" value="<?= $talla["id_talla"] ?>">
              <input type="hidden" name="id_zapatilla" value="<?= $id_zapatilla ?>">
              <button type="submit" onclick="return confirm('¿Eliminar esta talla?')">Eliminar</button>
            </form>
          </td>
        </tr>
      <?php } ?>
    </table>
  <?php } ?>

  <h2>Añadir talla</h2>
  <!-- Sin id_talla → guardar_talla.php sabe que es un INSERT (como el id oculto del RF01) -->
  <form action="../negocio/guardar_talla.php" method="post">
    <input type="hidden" name="id_zapatilla" value="<?= $id_zapatilla ?>">

    <!-- step="0.5": medias tallas (42,5). El rango lo vuelve a comprobar guardar_talla.php -->
    <label for="numero_talla">Talla *</label>
    <input type="number" id="numero_talla" name="numero_talla" min="30" max="50" step="0.5" required>

    <label for="stock">Stock inicial *</label>
    <input type="number" id="stock" name="stock" min="0" step="1" value="0" required>

    <button type="submit">Añadir</button>
  </form>
<?php require "pie.php"; ?>
</body>
</html>