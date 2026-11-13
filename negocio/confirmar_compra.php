<?php
/* confirmar_compra.php en la capa de negocio: convierte el carrito de la sesión en una
   compra real dentro de una TRANSACCIÓN:
   1. lee de la BD el precio actual de cada talla y calcula el total,
   2. crea el ticket (cabecera),
   3. por cada línea: resta el stock SOLO si hay suficiente e inserta la línea en compra.
   Si algo falla, rollBack() deshace todo: ni ticket a medias ni stock descontado. */
session_start();
require_once "../datos/conexion.php";
require_once "../cliente/plantilla_mensaje.php"; // los mensajes de esta página salen con el diseño común

// Solo compra un cliente
if (!isset($_SESSION["id_usuario"]) || $_SESSION["rol"] !== "cliente") {
  header("Location: ../cliente/login.php");
  exit;
}

// Comprar cambia datos → solo por POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Location: ../cliente/carrito.php");
  exit;
}

$carrito = $_SESSION["carrito"] ?? [];
if (count($carrito) === 0) {
  header("Location: ../cliente/carrito.php");
  exit;
}

try {
  $bd = conectar();

  /* 1. PRECIOS Y TOTAL desde la BD (decisión A: se paga el precio actual, el que el
        cliente ha visto en el carrito). Nada de lo que llega del navegador se usa. */
  $consulta = $bd->prepare("SELECT z.precio FROM talla t
                            JOIN zapatilla z ON t.id_zapatilla = z.id_zapatilla
                            WHERE t.id_talla = :id");
  $lineas = [];
  $total = 0;
  foreach ($carrito as $id_talla => $cantidad) {
    $consulta->execute([":id" => $id_talla]);
    $fila = $consulta->fetch();
    if ($fila === false) {
      // La talla ya no existe: se quita del carrito y se avisa
      unset($_SESSION["carrito"][$id_talla]);
      echo "<p>Una de las zapatillas de tu carrito ya no está a la venta y se ha quitado. Revisa tu carrito.</p>";
      echo '<a href="../cliente/carrito.php">Volver al carrito</a>';
      exit;
    }
    $lineas[] = ["id_talla" => $id_talla, "cantidad" => $cantidad, "precio" => $fila["precio"]];
    $total += $fila["precio"] * $cantidad;
  }

  // --- A partir de aquí, todo o nada ---
  $bd->beginTransaction();

  // 2. TICKET (cabecera): quién compra, cuándo (NOW() = fecha y hora del servidor MySQL) y el total
  $consulta = $bd->prepare("INSERT INTO ticket (id_usuario, fecha_hora, total) VALUES (:id_usuario, NOW(), :total)");
  $consulta->execute([":id_usuario" => $_SESSION["id_usuario"], ":total" => $total]);
  // lastInsertId(): el id que MySQL acaba de dar al ticket, para enlazar sus líneas
  $id_ticket = $bd->lastInsertId();

  /* 3. LÍNEAS. SEGUNDA comprobación del stock, en el mismo UPDATE:
        "resta solo si stock >= cantidad". Si otro cliente compró antes, no se cumple,
        rowCount() devuelve 0 filas cambiadas y se deshace TODO.
        (:cantidad1 y :cantidad2 valen lo mismo: con prepares reales no se puede repetir un nombre) */
  $restar = $bd->prepare("UPDATE talla SET stock = stock - :cantidad1
                          WHERE id_talla = :id_talla AND stock >= :cantidad2");
  $insertar = $bd->prepare("INSERT INTO compra (id_ticket, id_talla, cantidad, precio_unitario)
                            VALUES (:id_ticket, :id_talla, :cantidad, :precio)");

  foreach ($lineas as $linea) {
    $restar->execute([
      ":cantidad1" => $linea["cantidad"],
      ":id_talla" => $linea["id_talla"],
      ":cantidad2" => $linea["cantidad"]
    ]);
    if ($restar->rowCount() === 0) {
      // No había stock suficiente → se deshace el ticket y los stocks ya restados
      $bd->rollBack();
      echo "<p>Lo sentimos: mientras comprabas se ha agotado alguna de las tallas de tu carrito.
            No se ha cobrado nada. Revisa las cantidades e inténtalo de nuevo.</p>";
      echo '<a href="../cliente/carrito.php">Volver al carrito</a>';
      exit;
    }
    // precio_unitario: el precio de HOY queda guardado en el ticket para siempre
    $insertar->execute([
      ":id_ticket" => $id_ticket,
      ":id_talla" => $linea["id_talla"],
      ":cantidad" => $linea["cantidad"],
      ":precio" => $linea["precio"]
    ]);
  }

  // Todo ha ido bien → se guardan de verdad los cambios
  $bd->commit();

} catch (PDOException $e) {
  // Cualquier error de la BD a mitad → deshacer lo que se hubiera hecho
  if (isset($bd) && $bd->inTransaction()) {
    $bd->rollBack();
  }
  echo "<p>No se ha podido completar la compra. No se ha cobrado nada. Inténtalo más tarde.</p>";
  echo '<a href="../cliente/carrito.php">Volver al carrito</a>';
  exit;
}

// Compra hecha → el carrito se vacía
unset($_SESSION["carrito"]);

/* Aquí no se redirige: se muestra la confirmación. Si el cliente pulsa F5 y el navegador
   reenvía el POST, el carrito ya está vacío y este archivo lo devuelve al carrito
   (no se puede comprar dos veces). */
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Compra realizada</title>
  <link rel="icon" href="../cliente/img/logo.svg">
  <link rel="stylesheet" href="../cliente/css/estilos.css">
</head>
<body>
  <?php $ruta = "../cliente/"; // esta página está en negocio/: los enlaces del menú necesitan el prefijo
  require "../cliente/cabecera.php"; ?>
  <h1>¡Compra realizada!</h1>
  <p>Número de ticket: <?= $id_ticket ?></p>
  <p>Total pagado: <?= number_format($total, 2, ",", ".") ?> €</p>
  <p><a href="../cliente/catalogo.php">Volver a la tienda</a> · <a href="../cliente/inicio.php">Inicio</a></p>
<?php require "../cliente/pie.php"; ?>
</body>
</html>