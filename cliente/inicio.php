<?php
// Para LEER la sesión también hace falta session_start(), y otra vez lo primero
session_start();
require_once "../datos/conexion.php";

/* Guardián de la página si no hay id en la sesión, no ha pasado por el login lo cual sin
esto cualquiera podria escribir en la url de esta página y entrar */
if (!isset($_SESSION["id_usuario"])) {
  header("Location: login.php");
  exit;
}

$rol = $_SESSION["rol"];

/* PANEL DE INICIO: en vez de repetir los enlaces del menú, el inicio resume el estado
   de la tienda con consultas de AGREGADO: COUNT(*) cuenta filas y SUM() suma una columna.
   COALESCE(SUM(total), 0): si no hay ningún ticket, SUM devuelve NULL; así sale 0. */
try {
  $bd = conectar();

  if ($rol === "admin") {
    /* Una sola consulta con SUBCONSULTAS: cada (SELECT ...) devuelve un número y todos
       llegan en la misma fila. Sin datos del usuario → query() basta. */
    $resumen = $bd->query("SELECT
        (SELECT COUNT(*) FROM usuario WHERE rol = 'cliente') AS clientes,
        (SELECT COUNT(*) FROM zapatilla) AS zapatillas,
        (SELECT COUNT(*) FROM talla WHERE stock = 0) AS sin_stock,
        (SELECT COUNT(*) FROM ticket) AS ventas,
        (SELECT COALESCE(SUM(total), 0) FROM ticket) AS facturado")->fetch();
  } else {
    // Cliente: sus compras. Aquí SÍ hay un dato de la sesión (su id) → consulta preparada
    $consulta = $bd->prepare("SELECT COUNT(*) AS compras, COALESCE(SUM(total), 0) AS gastado
                              FROM ticket WHERE id_usuario = :id");
    $consulta->execute([":id" => $_SESSION["id_usuario"]]);
    $resumen = $consulta->fetch();
    $resumen["zapatillas"] = $bd->query("SELECT COUNT(*) FROM zapatilla")->fetchColumn();
  }
} catch (PDOException $e) {
  die("No se ha podido cargar el inicio.");
}

// Unidades del carrito (solo cliente). Si aún no existe, 0
$unidades = array_sum($_SESSION["carrito"] ?? []);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $rol === "admin" ? "Panel de administración" : "Zona de clientes" ?></title>
  <!-- Icono de la pestaña y hoja de estilos común (capa cliente: CSS) -->
  <link rel="icon" href="img/logo.svg">
  <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
  <?php require "cabecera.php"; // logo + menú común ?>
  <!-- htmlspecialchars: el usuario lo escribió el cliente al registrarse;
       si metió código HTML/JS, así se muestra como texto y no se ejecuta (XSS)-->
  <h1>Hola, <?= htmlspecialchars($_SESSION["nombre_usuario"]) ?></h1>

  <!-- Tarjetas del panel: las que llevan a otra página son enlaces <a>;
       las que solo informan (ventas, compras) son <div> -->
  <div class="panel">
  <?php if ($rol === "admin") { ?>
    <a class="panel-tarjeta" href="admin_usuarios.php">
      <span class="panel-numero"><?= $resumen["clientes"] ?></span>
      <span class="panel-titulo">Clientes</span>
      <span class="panel-texto">Gestionar usuarios →</span>
    </a>
    <a class="panel-tarjeta" href="admin_zapatillas.php">
      <span class="panel-numero"><?= $resumen["zapatillas"] ?></span>
      <span class="panel-titulo">Zapatillas</span>
      <span class="panel-texto">Gestionar modelos y tallas →</span>
    </a>
    <!-- Aviso útil para el admin: tallas que hay que reponer -->
    <a class="panel-tarjeta<?= $resumen["sin_stock"] > 0 ? " panel-alerta" : "" ?>" href="admin_zapatillas.php">
      <span class="panel-numero"><?= $resumen["sin_stock"] ?></span>
      <span class="panel-titulo">Tallas sin stock</span>
      <span class="panel-texto"><?= $resumen["sin_stock"] > 0 ? "Hay que reponer →" : "Todo con stock" ?></span>
    </a>
    <div class="panel-tarjeta">
      <span class="panel-numero"><?= $resumen["ventas"] ?></span>
      <span class="panel-titulo">Ventas</span>
      <span class="panel-texto"><?= number_format($resumen["facturado"], 2, ",", ".") ?> € facturados</span>
    </div>
  <?php } else { ?>
    <a class="panel-tarjeta" href="catalogo.php">
      <span class="panel-numero"><?= $resumen["zapatillas"] ?></span>
      <span class="panel-titulo">Modelos en la tienda</span>
      <span class="panel-texto">Ir a la tienda →</span>
    </a>
    <a class="panel-tarjeta" href="carrito.php">
      <span class="panel-numero"><?= $unidades ?></span>
      <span class="panel-titulo">En tu carrito</span>
      <span class="panel-texto">Ver carrito →</span>
    </a>
    <div class="panel-tarjeta">
      <span class="panel-numero"><?= $resumen["compras"] ?></span>
      <span class="panel-titulo">Compras realizadas</span>
      <span class="panel-texto"><?= number_format($resumen["gastado"], 2, ",", ".") ?> € en total</span>
    </div>
  <?php } ?>
  </div>
<?php require "pie.php"; ?>
</body>
</html>
