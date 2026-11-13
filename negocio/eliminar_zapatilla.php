<?php
/* eliminar_zapatilla.php en la capa de negocio: borra una zapatilla. Lo llama por POST el
   botón «Eliminar» de admin_zapatillas.php. Sus tallas se borran solas (FK talla →
   zapatilla con ON DELETE CASCADE), pero si alguna talla ya se vendió, la FK compra →
   talla (ON DELETE RESTRICT) bloquea el borrado para conservar los tickets. */
session_start();
require_once "../datos/conexion.php";
require_once "../cliente/plantilla_mensaje.php"; // los mensajes de esta página salen con el diseño común

// Guardián doble: borrar es la acción más peligrosa del CRUD
if (!isset($_SESSION["id_usuario"]) || $_SESSION["rol"] !== "admin") {
  header("Location: ../cliente/login.php");
  exit;
}

// Borrar cambia datos → solo por POST (el botón es un formulario, nunca un enlace)
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Location: ../cliente/admin_zapatillas.php");
  exit;
}

// El id llega del campo oculto del botón: se puede manipular con F12 → se valida
$id = filter_var($_POST["id_zapatilla"] ?? "", FILTER_VALIDATE_INT);
if ($id === false) {
  echo "<p>Zapatilla no válida.</p>";
  echo '<a href="../cliente/admin_zapatillas.php">Volver a la lista</a>';
  exit;
}

// Aquí no hay "no borrarse a sí mismo": una zapatilla no es una cuenta
try {
  $bd = conectar();
  // Antes de borrar se apunta su foto, para borrar también el archivo si todo va bien
  $consulta = $bd->prepare("SELECT imagen FROM zapatilla WHERE id_zapatilla = :id");
  $consulta->execute([":id" => $id]);
  $imagen = $consulta->fetchColumn() ?: null;

  /* ¡Con WHERE! Un DELETE sin WHERE vacía la tabla entera.
     Por el CASCADE, MySQL borra también las tallas de esta zapatilla. */
  $consulta = $bd->prepare("DELETE FROM zapatilla WHERE id_zapatilla = :id");
  $consulta->execute([":id" => $id]);
} catch (PDOException $e) {
  /* Actúan DOS claves foráneas en cadena:
     - talla → zapatilla (CASCADE): MySQL intenta borrar las tallas de la zapatilla.
     - compra → talla (RESTRICT): si alguna de esas tallas está en una compra, no deja
       borrarla, y toda la operación se cancela (ni zapatilla ni tallas).
     1451 = "Cannot delete or update a parent row: a foreign key constraint fails". */
  if ($e->errorInfo[1] == 1451) {
    echo "<p>No se puede eliminar esta zapatilla porque alguna de sus tallas tiene ventas
          registradas. Los tickets deben conservarse.</p>";
  } else {
    echo "<p>No se ha podido eliminar la zapatilla. Inténtalo más tarde.</p>";
  }
  echo '<a href="../cliente/admin_zapatillas.php">Volver a la lista</a>';
  exit;
}

/* Borrado correcto → su foto ya no la usa nadie. Solo aquí, DESPUÉS del DELETE: si el
   RESTRICT lo hubiera impedido, la zapatilla seguiría en la tienda y necesitaría su foto. */
$carpeta_fotos = __DIR__ . "/../cliente/img/zapatillas/";
if ($imagen !== null && is_file($carpeta_fotos . basename($imagen))) {
  unlink($carpeta_fotos . basename($imagen));
}

// Borrado correcto → de vuelta a la lista (la zapatilla ya no aparece)
header("Location: ../cliente/admin_zapatillas.php");
exit;