<?php
/* carrito.php en la capa cliente: muestra el carrito del cliente. En la sesión solo
   están id_talla y cantidad; el modelo, el color, la talla y el PRECIO se leen de la BD.
   Permite cambiar la cantidad, quitar una línea y confirmar la compra. */
session_start();
require_once "../datos/conexion.php";

// Solo compra un cliente
if (!isset($_SESSION["id_usuario"]) || $_SESSION["rol"] !== "cliente") {
  header("Location: login.php");
  exit;
}

$carrito = $_SESSION["carrito"] ?? [];
$lineas = [];
$total = 0;

if (count($carrito) > 0) {
  try {
    $bd = conectar();
    /* JOIN: la talla + los datos de SU zapatilla en una sola consulta.
       Se prepara UNA vez y se ejecuta una vez por cada talla del carrito. */
    $consulta = $bd->prepare("SELECT t.numero_talla, t.stock, z.modelo, z.color, z.precio
                              FROM talla t JOIN zapatilla z ON t.id_zapatilla = z.id_zapatilla
                              WHERE t.id_talla = :id");
    foreach ($carrito as $id_talla => $cantidad) {
      $consulta->execute([":id" => $id_talla]);
      $fila = $consulta->fetch();

      if ($fila === false) {
        // La talla ya no existe (el admin la borró) → fuera del carrito
        unset($_SESSION["carrito"][$id_talla]);
        continue;
      }

      $fila["id_talla"] = $id_talla;
      $fila["cantidad"] = $cantidad;
      $fila["subtotal"] = $fila["precio"] * $cantidad;
      $total += $fila["subtotal"];
      $lineas[] = $fila;
    }
  } catch (PDOException $e) {
    die("No se ha podido cargar el carrito.");
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Carrito</title>
  <!-- Icono de la pestaña y hoja de estilos común (capa cliente: CSS) -->
  <link rel="icon" href="img/logo.svg">
  <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
  <?php require "cabecera.php"; // logo + menú común ?>
  <h1>Tu carrito</h1>
  <p><a href="catalogo.php">Seguir comprando</a> · <a href="inicio.php">Volver</a></p>

  <?php if (count($lineas) === 0) { ?>
    <p>Tu carrito está vacío.</p>
  <?php } else { ?>
    <table border="1">
      <tr><th>Zapatilla</th><th>Talla</th><th>Precio</th><th>Cantidad</th><th>Subtotal</th><th>Quitar</th></tr>
      <?php foreach ($lineas as $linea) { ?>
        <tr>
          <td><?= htmlspecialchars($linea["modelo"]) ?> (<?= htmlspecialchars($linea["color"]) ?>)</td>
          <td><?= str_replace(".", ",", (float) $linea["numero_talla"]) ?></td>
          <td><?= number_format($linea["precio"], 2, ",", ".") ?> €</td>
          <td>
            <!-- Cambiar la cantidad: un formulario por línea, como el stock en tallas.php -->
            <form action="../negocio/actualizar_carrito.php" method="post">
              <input type="hidden" name="id_talla" value="<?= $linea["id_talla"] ?>">
              <input type="number" name="cantidad" min="1" step="1" required value="<?= $linea["cantidad"] ?>">
              <button type="submit">Cambiar</button>
            </form>
          </td>
          <td><?= number_format($linea["subtotal"], 2, ",", ".") ?> €</td>
          <td>
            <!-- Quitar = poner la cantidad a 0 (actualizar_carrito.php la borra) -->
            <form action="../negocio/actualizar_carrito.php" method="post">
              <input type="hidden" name="id_talla" value="<?= $linea["id_talla"] ?>">
              <input type="hidden" name="cantidad" value="0">
              <button type="submit">Quitar</button>
            </form>
          </td>
        </tr>
      <?php } ?>
    </table>

    <p class="total">Total: <strong><?= number_format($total, 2, ",", ".") ?> €</strong></p>

    <!-- Confirmar no envía ni precios ni total: confirmar_compra.php lo recalcula todo
         desde la sesión y la BD (lo que llega del navegador no es de fiar) -->
    <form action="../negocio/confirmar_compra.php" method="post">
      <button type="submit" onclick="return confirm('¿Confirmar la compra por <?= number_format($total, 2, ",", ".") ?> €?')">Confirmar compra</button>
    </form>
  <?php } ?>
<?php require "pie.php"; ?>
</body>
</html>