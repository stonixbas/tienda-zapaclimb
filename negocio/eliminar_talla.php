<?php
/* eliminar_talla.php en la capa de negocio: borra una talla de una zapatilla. Lo llama
   por POST el botón «Eliminar» de tallas.php. Si la talla ya se vendió, la FK compra →
   talla (ON DELETE RESTRICT) lo impide para conservar el historial de tickets. */
session_start();
require_once "../datos/conexion.php";
require_once "../cliente/plantilla_mensaje.php"; // los mensajes de esta página salen con el diseño común

// Guardián doble: borrar es la acción más peligrosa
if (!isset($_SESSION["id_usuario"]) || $_SESSION["rol"] !== "admin") {
  header("Location: ../cliente/login.php");
  exit;
}

// Borrar cambia datos → solo por POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Location: ../cliente/admin_zapatillas.php");
  exit;
}

// Los dos ids llegan ocultos y se pueden manipular con F12 → se validan
$id_talla = filter_var($_POST["id_talla"] ?? "", FILTER_VALIDATE_INT);
$id_zapatilla = filter_var($_POST["id_zapatilla"] ?? "", FILTER_VALIDATE_INT);
if ($id_talla === false || $id_zapatilla === false) {
  echo "<p>Talla no válida.</p>";
  echo '<a href="../cliente/admin_zapatillas.php">Volver a la lista</a>';
  exit;
}

try {
  $bd = conectar();
  /* ¡Con WHERE! Y con el mismo seguro que en guardar_talla.php: solo se borra
     si la talla es de ESTA zapatilla. */
  $consulta = $bd->prepare("DELETE FROM talla WHERE id_talla = :id_talla AND id_zapatilla = :id_zapatilla");
  $consulta->execute([":id_talla" => $id_talla, ":id_zapatilla" => $id_zapatilla]);
} catch (PDOException $e) {
  // 1451: la talla está en alguna compra (FK con RESTRICT) → no se borra
  if ($e->errorInfo[1] == 1451) {
    echo "<p>No se puede eliminar esta talla porque ya se ha vendido.
          Los tickets deben conservarse. Si no quieres venderla más, pon su stock a 0.</p>";
  } else {
    echo "<p>No se ha podido eliminar la talla. Inténtalo más tarde.</p>";
  }
  echo '<a href="../cliente/tallas.php?id=' . $id_zapatilla . '">Volver a las tallas</a>';
  exit;
}

// Borrado correcto → de vuelta a las tallas de ESTA zapatilla
header("Location: ../cliente/tallas.php?id=" . $id_zapatilla);
exit;