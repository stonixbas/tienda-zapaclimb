<?php
/* actualizar_carrito.php en la capa de negocio: recibe los formularios de carrito.php.
   Cambia la cantidad de una talla del carrito o, si llega cantidad 0 (botón «Quitar»),
   la quita. La cantidad nueva SUSTITUYE a la anterior (en anadir_carrito.php se sumaba). */
session_start();
require_once "../datos/conexion.php";
require_once "../cliente/plantilla_mensaje.php"; // los mensajes de esta página salen con el diseño común

// Solo compra un cliente
if (!isset($_SESSION["id_usuario"]) || $_SESSION["rol"] !== "cliente") {
  header("Location: ../cliente/login.php");
  exit;
}

// Cambia datos (el carrito) → solo por POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Location: ../cliente/carrito.php");
  exit;
}

$id_talla = filter_var($_POST["id_talla"] ?? "", FILTER_VALIDATE_INT);
// Aquí el 0 SÍ vale: significa "quitar"
$cantidad = filter_var($_POST["cantidad"] ?? "", FILTER_VALIDATE_INT, ["options" => ["min_range" => 0]]);

/* Si los datos no son válidos, o la talla no está en el carrito (id cambiado con F12),
   no hay nada que actualizar: se vuelve al carrito sin tocarlo. */
if ($id_talla === false || $cantidad === false || !isset($_SESSION["carrito"][$id_talla])) {
  header("Location: ../cliente/carrito.php");
  exit;
}

// QUITAR: unset borra esa clave del array del carrito
if ($cantidad === 0) {
  unset($_SESSION["carrito"][$id_talla]);
  header("Location: ../cliente/carrito.php");
  exit;
}

// CAMBIAR: se vuelve a mirar el stock en la BD, como al añadir
try {
  $bd = conectar();
  $consulta = $bd->prepare("SELECT stock FROM talla WHERE id_talla = :id");
  $consulta->execute([":id" => $id_talla]);
  $talla = $consulta->fetch();
} catch (PDOException $e) {
  die("No se ha podido actualizar el carrito. Inténtalo más tarde.");
}

if ($talla === false || $cantidad > $talla["stock"]) {
  // Mismo criterio que en anadir_carrito.php: no se dice el stock exacto
  echo "<p>No hay stock suficiente de esa talla. Quedan pocas unidades: prueba con una cantidad menor.</p>";
  echo '<a href="../cliente/carrito.php">Volver al carrito</a>';
  exit;
}

$_SESSION["carrito"][$id_talla] = $cantidad;

// PRG: de vuelta al carrito con la cantidad nueva
header("Location: ../cliente/carrito.php");
exit;