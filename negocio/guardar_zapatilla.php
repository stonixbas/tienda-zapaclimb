<?php
/* guardar_zapatilla.php en la capa de negocio: recibe el formulario de zapatilla_form.php,
   comprueba que quien envía es un admin, valida los datos y crea la zapatilla (INSERT) o,
   si llega el id oculto, la actualiza (UPDATE). Al terminar vuelve a la lista. */
session_start();
require_once "../datos/conexion.php";
require_once "../cliente/plantilla_mensaje.php"; // los mensajes de esta página salen con el diseño común

/* Guardián propio: el del formulario no basta, cualquiera podría mandar un POST
   directamente aquí sin pasar por zapatilla_form.php. */
if (!isset($_SESSION["id_usuario"]) || $_SESSION["rol"] !== "admin") {
  header("Location: ../cliente/login.php");
  exit;
}

// Este archivo solo CAMBIA datos → solo se entra por POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Location: ../cliente/admin_zapatillas.php");
  exit;
}

$modelo = trim($_POST["modelo"] ?? "");
$color = trim($_POST["color"] ?? "");
$precio = trim($_POST["precio"] ?? "");
$id = $_POST["id_zapatilla"] ?? "";
$editando = ($id !== "");

$errores = [];
// El id oculto se puede cambiar con F12: se valida como cualquier otro dato
if ($editando) {
  $id = filter_var($id, FILTER_VALIDATE_INT);
  if ($id === false) {
    $errores[] = "Zapatilla no válida.";
  }
}

if ($modelo === "" || $color === "" || $precio === "") {
  $errores[] = "Rellena todos los campos obligatorios.";
}
// Las mismas longitudes que las columnas: modelo varchar(100), color varchar(20)
if (mb_strlen($modelo) > 100) {
  $errores[] = "El modelo no puede pasar de 100 caracteres.";
}
if (mb_strlen($color) > 20) {
  $errores[] = "El color no puede pasar de 20 caracteres.";
}

/* PRECIO
   1. Si llega "89,99" (coma española) se cambia por "89.99": PHP y MySQL usan punto.
   2. FILTER_VALIDATE_FLOAT devuelve el número o false si no lo es ("abc", "8.9.9").
   3. Mayor que 0 y que quepa en decimal(8,2): como mucho 999999.99.
   4. round(..., 2) deja 2 decimales, igual que la columna. */
$precio = str_replace(",", ".", $precio);
$precio = filter_var($precio, FILTER_VALIDATE_FLOAT);
if ($precio === false || $precio <= 0 || $precio > 999999.99) {
  $errores[] = "El precio debe ser un número mayor que 0 (como mucho 999999,99).";
} else {
  $precio = round($precio, 2);
}

/* FOTO (opcional). Subir archivos es de lo más peligroso de una web: si se guardara tal
   cual, alguien podría subir "virus.php" y ejecutarlo abriendo su URL. Por eso:
   1. Si no se ha elegido archivo (UPLOAD_ERR_NO_FILE), no se toca la foto.
   2. Tamaño máximo 2 MB.
   3. Tipo REAL con getimagesize(): lee el contenido del archivo, no su nombre ni lo que
      dice el navegador (ambos se falsifican). Si no es una imagen, devuelve false.
   4. LISTA BLANCA de tipos (como el rol): solo JPG, PNG y WEBP, y la extensión la pone
      el servidor según el tipo real, nunca la del archivo subido.
   5. El nombre también lo pone el servidor (aleatorio): así no se pisan dos fotos y no
      se cuelan nombres raros como "../../index.php". */
$tipos_permitidos = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"];
$archivo = $_FILES["imagen"] ?? null;
$extension = null;   // se rellena solo si llega una foto válida

if ($archivo !== null && $archivo["error"] !== UPLOAD_ERR_NO_FILE) {
  if ($archivo["error"] !== UPLOAD_ERR_OK) {
    $errores[] = "No se ha podido subir la foto (puede que sea demasiado grande).";
  } elseif ($archivo["size"] > 2 * 1024 * 1024) {
    $errores[] = "La foto no puede pasar de 2 MB.";
  } else {
    $info = getimagesize($archivo["tmp_name"]);
    if ($info === false || !isset($tipos_permitidos[$info["mime"]])) {
      $errores[] = "La foto debe ser una imagen JPG, PNG o WEBP.";
    } else {
      $extension = $tipos_permitidos[$info["mime"]];
    }
  }
}

if (count($errores) > 0) {
  foreach ($errores as $error) {
    echo "<p>" . $error . "</p>";
  }
  echo '<a href="javascript:history.back()">Volver al formulario</a>';
  exit;
}

/* Datos válidos y hay foto nueva → se mueve de la carpeta temporal de PHP a la nuestra.
   move_uploaded_file() solo mueve archivos que PHP ha recibido de verdad en ESTA petición. */
$imagen_nueva = null;
$carpeta_fotos = __DIR__ . "/../cliente/img/zapatillas/";
if ($extension !== null) {
  $imagen_nueva = bin2hex(random_bytes(8)) . "." . $extension;  // ej. "3f9a1c0b7e2d4a65.jpg"
  if (!move_uploaded_file($archivo["tmp_name"], $carpeta_fotos . $imagen_nueva)) {
    echo "<p>No se ha podido guardar la foto. Inténtalo más tarde.</p>";
    echo '<a href="javascript:history.back()">Volver al formulario</a>';
    exit;
  }
}

$imagen_antigua = null;
try {
  $bd = conectar();

  if ($editando) {
    // Si se cambia la foto, se apunta la antigua para borrar su archivo al terminar
    if ($imagen_nueva !== null) {
      $consulta = $bd->prepare("SELECT imagen FROM zapatilla WHERE id_zapatilla = :id");
      $consulta->execute([":id" => $id]);
      $imagen_antigua = $consulta->fetchColumn() ?: null;
    }
    /* MODIFICAR: el WHERE es imprescindible, sin él se cambiarían TODAS las zapatillas.
       COALESCE(:imagen, imagen): si no se ha subido foto (:imagen es NULL), deja la que
       había; si se ha subido, pone la nueva. */
    $sql = "UPDATE zapatilla SET modelo = :modelo, color = :color, precio = :precio,
            imagen = COALESCE(:imagen, imagen)
            WHERE id_zapatilla = :id";
    $datos = [
      ":modelo" => $modelo,
      ":color" => $color,
      ":precio" => $precio,
      ":imagen" => $imagen_nueva,
      ":id" => $id
    ];
  } else {
    // CREAR (sin foto, imagen queda NULL y la tienda enseña el dibujo genérico)
    $sql = "INSERT INTO zapatilla (modelo, color, precio, imagen)
            VALUES (:modelo, :color, :precio, :imagen)";
    $datos = [
      ":modelo" => $modelo,
      ":color" => $color,
      ":precio" => $precio,
      ":imagen" => $imagen_nueva
    ];
  }

  // Un solo prepare/execute para los dos casos: solo cambian el SQL y los datos
  $consulta = $bd->prepare($sql);
  $consulta->execute($datos);
} catch (PDOException $e) {
  // La BD no ha guardado nada → la foto recién subida sobra: se borra para no dejar basura
  if ($imagen_nueva !== null) {
    unlink($carpeta_fotos . $imagen_nueva);
  }
  // 23000 = se ha roto una restricción; aquí, el UNIQUE (modelo, color)
  if ($e->getCode() === "23000") {
    echo "<p>Ya existe una zapatilla con ese modelo y ese color.</p>";
  } else {
    echo "<p>No se ha podido guardar la zapatilla. Inténtalo más tarde.</p>";
  }
  echo '<a href="javascript:history.back()">Volver al formulario</a>';
  exit;
}

// Foto cambiada y guardada → el archivo de la antigua ya no lo usa nadie
// (basename: aunque el nombre viene de nuestra BD, se asegura que no salga de la carpeta)
if ($imagen_antigua !== null && is_file($carpeta_fotos . basename($imagen_antigua))) {
  unlink($carpeta_fotos . basename($imagen_antigua));
}

// PRG: redirigir evita que un F5 reenvíe el formulario y se cree otra zapatilla igual
header("Location: ../cliente/admin_zapatillas.php");
exit;