<?php
/* anadir_carrito.php en la capa de negocio: recibe el formulario de catalogo.php y
   añade una talla al carrito, que se guarda en la SESIÓN (no en la BD):
   $_SESSION["carrito"][id_talla] = cantidad. Comprueba que la talla existe y que hay
   stock suficiente. El stock NO se resta aquí: solo al confirmar la compra. */
session_start();
require_once "../datos/conexion.php";
require_once "../cliente/plantilla_mensaje.php"; // los mensajes de esta página salen con el diseño común

// Mismo guardián que el catálogo: solo compra un cliente
if (!isset($_SESSION["id_usuario"]) || $_SESSION["rol"] !== "cliente") {
  header("Location: ../cliente/login.php");
  exit;
}

// Cambia datos (el carrito) → solo por POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Location: ../cliente/catalogo.php");
  exit;
}

$id_talla = filter_var($_POST["id_talla"] ?? "", FILTER_VALIDATE_INT);
// Cantidad entera y al menos 1 (min_range, igual que el stock en guardar_talla.php)
$cantidad = filter_var($_POST["cantidad"] ?? "", FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

$errores = [];
if ($id_talla === false) {
  $errores[] = "Elige una talla.";
}
if ($cantidad === false) {
  $errores[] = "La cantidad debe ser un número entero mayor que 0.";
}

if (count($errores) === 0) {
  try {
    $bd = conectar();
    // El stock se lee de la BD: es el dato de verdad, no lo que diga el formulario
    $consulta = $bd->prepare("SELECT stock FROM talla WHERE id_talla = :id");
    $consulta->execute([":id" => $id_talla]);
    $talla = $consulta->fetch();
  } catch (PDOException $e) {
    die("No se ha podido añadir al carrito. Inténtalo más tarde.");
  }

  if ($talla === false) {
    // id inventado con F12
    $errores[] = "Esa talla no existe.";
  } else {
    /* PRIMERA comprobación del stock: lo que ya había en el carrito + lo que se añade.
       (La segunda será al confirmar: entre medias otro cliente puede comprar.) */
    $en_carrito = $_SESSION["carrito"][$id_talla] ?? 0;
    if ($en_carrito + $cantidad > $talla["stock"]) {
      // No se dice el stock exacto (decisión: no dar esa información a la competencia)
      $errores[] = "No hay stock suficiente de esa talla. Quedan pocas unidades: prueba con una cantidad menor.";
    }
  }
}

if (count($errores) > 0) {
  foreach ($errores as $error) {
    echo "<p>" . $error . "</p>";
  }
  echo '<a href="../cliente/catalogo.php">Volver a la tienda</a>';
  exit;
}

/* Todo bien → se guarda en la sesión. Si la talla ya estaba, se SUMA la cantidad.
   Solo se guardan id_talla y cantidad: precio, modelo y talla se leen siempre de la BD. */
$_SESSION["carrito"][$id_talla] = $en_carrito + $cantidad;

// PRG: al carrito, donde se ve lo que se acaba de añadir
header("Location: ../cliente/carrito.php");
exit;