<?php
/* guardar_talla.php en la capa de negocio: recibe los formularios de tallas.php.
   Si llega id_talla, actualiza el stock de esa talla (UPDATE); si no, añade una talla
   nueva a la zapatilla (INSERT). Al terminar vuelve a tallas.php de esa zapatilla. */
session_start();
require_once "../datos/conexion.php";
require_once "../cliente/plantilla_mensaje.php"; // los mensajes de esta página salen con el diseño común

// Guardián propio: cualquiera podría mandar un POST directamente aquí
if (!isset($_SESSION["id_usuario"]) || $_SESSION["rol"] !== "admin") {
  header("Location: ../cliente/login.php");
  exit;
}

// Solo CAMBIA datos → solo por POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Location: ../cliente/admin_zapatillas.php");
  exit;
}

$id_zapatilla = filter_var($_POST["id_zapatilla"] ?? "", FILTER_VALIDATE_INT);
$id_talla = $_POST["id_talla"] ?? "";
$editando = ($id_talla !== ""); // con id_talla = cambiar stock; sin él = talla nueva
$numero_talla = trim($_POST["numero_talla"] ?? "");
$stock = trim($_POST["stock"] ?? "");

// Sin zapatilla válida no sabemos ni a dónde volver → a la lista
if ($id_zapatilla === false) {
  header("Location: ../cliente/admin_zapatillas.php");
  exit;
}

$errores = [];
// Los ids ocultos se pueden cambiar con F12: se validan como cualquier otro dato
if ($editando) {
  $id_talla = filter_var($id_talla, FILTER_VALIDATE_INT);
  if ($id_talla === false) {
    $errores[] = "Talla no válida.";
  }
}

/* STOCK: entero y >= 0. Con min_range, FILTER_VALIDATE_INT ya rechaza los negativos.
   (La columna es UNSIGNED: la BD es la segunda barrera.) */
$stock = filter_var($stock, FILTER_VALIDATE_INT, ["options" => ["min_range" => 0]]);
if ($stock === false) {
  $errores[] = "El stock debe ser un número entero igual o mayor que 0.";
}

/* TALLA (solo al añadir; al cambiar el stock no se envía):
   número entre 30 y 50, en tallas enteras o medias (42 o 42,5).
   fmod($talla * 2, 1) es el resto de dividir entre 1: si 42,5 * 2 = 85 → resto 0 → vale;
   si 42,3 * 2 = 84,6 → resto 0,6 → no es ni entera ni media. */
if (!$editando) {
  $numero_talla = str_replace(",", ".", $numero_talla);
  $numero_talla = filter_var($numero_talla, FILTER_VALIDATE_FLOAT);
  if ($numero_talla === false || $numero_talla < 30 || $numero_talla > 50
      || fmod($numero_talla * 2, 1) != 0) {
    $errores[] = "La talla debe ser un número entre 30 y 50, entera o media (ej. 42 o 42,5).";
  }
}

if (count($errores) > 0) {
  foreach ($errores as $error) {
    echo "<p>" . $error . "</p>";
  }
  echo '<a href="javascript:history.back()">Volver</a>';
  exit;
}

try {
  $bd = conectar();

  if ($editando) {
    /* CAMBIAR STOCK. El AND id_zapatilla es un seguro extra: aunque alguien cambie
       el id_talla con F12, solo puede tocar tallas de ESTA zapatilla. */
    $sql = "UPDATE talla SET stock = :stock
            WHERE id_talla = :id_talla AND id_zapatilla = :id_zapatilla";
    $datos = [
      ":stock" => $stock,
      ":id_talla" => $id_talla,
      ":id_zapatilla" => $id_zapatilla
    ];
  } else {
    // TALLA NUEVA
    $sql = "INSERT INTO talla (id_zapatilla, numero_talla, stock)
            VALUES (:id_zapatilla, :numero_talla, :stock)";
    $datos = [
      ":id_zapatilla" => $id_zapatilla,
      ":numero_talla" => $numero_talla,
      ":stock" => $stock
    ];
  }

  $consulta = $bd->prepare($sql);
  $consulta->execute($datos);
} catch (PDOException $e) {
  /* Mismo SQLSTATE 23000 para varios casos → se mira el código de MySQL:
     1062 = duplicado (UNIQUE id_zapatilla + numero_talla: esa talla ya existe)
     1452 = la zapatilla no existe (la FK de talla no encuentra al "padre") */
  if ($e->errorInfo[1] == 1062) {
    echo "<p>Esta zapatilla ya tiene esa talla. Cambia su stock en la tabla.</p>";
  } elseif ($e->errorInfo[1] == 1452) {
    echo "<p>La zapatilla no existe.</p>";
  } else {
    echo "<p>No se ha podido guardar la talla. Inténtalo más tarde.</p>";
  }
  echo '<a href="javascript:history.back()">Volver</a>';
  exit;
}

// PRG: de vuelta a las tallas de ESTA zapatilla (para eso viaja id_zapatilla oculto)
header("Location: ../cliente/tallas.php?id=" . $id_zapatilla);
exit;