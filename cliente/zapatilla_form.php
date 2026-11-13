<?php
/* zapatilla_form.php en la capa cliente: formulario con el que el administrador crea
   una zapatilla nueva o, si llega ?id= en la URL, modifica una existente. Envía los
   datos por POST a negocio/guardar_zapatilla.php. */
session_start();
require_once "../datos/conexion.php";
// Mismo guardián doble que la lista: este formulario también es solo para el admin
if (!isset($_SESSION["id_usuario"]) || $_SESSION["rol"] !== "admin") {
  header("Location: login.php");
  exit;
}
// Valores por defecto = formulario vacío (modo CREAR)
$zapatilla = [
  "modelo" => "",
  "color"  => "",
  "precio" => "",
  "imagen" => null   // sin foto
];
$id = null;

// ¿Viene un id en la URL? → modo MODIFICAR
if (isset($_GET["id"])) {
  // El id llega por la URL y se puede manipular → filter_var devuelve el entero o false
  $id = filter_var($_GET["id"], FILTER_VALIDATE_INT);
  if ($id === false) {
    header("Location: admin_zapatillas.php");
    exit;
  }

  try {
    $bd = conectar();
    // Aquí SÍ hay un dato del usuario (el id) → consulta preparada
    $consulta = $bd->prepare("SELECT modelo, color, precio, imagen FROM zapatilla WHERE id_zapatilla = :id");
    $consulta->execute([":id" => $id]);
    $zapatilla = $consulta->fetch();
  } catch (PDOException $e) {
    die("No se ha podido cargar la zapatilla.");
  }

  // Un id que no existe (ej. ?id=999) → fetch() devuelve false → vuelta a la lista
  if ($zapatilla === false) {
    header("Location: admin_zapatillas.php");
    exit;
  }
}

// Una variable con nombre claro para no repetir "$id !== null" por todo el HTML
$editando = ($id !== null);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $editando ? "Modificar zapatilla" : "Nueva zapatilla" ?></title>
  <!-- Icono de la pestaña y hoja de estilos común (capa cliente: CSS) -->
  <link rel="icon" href="img/logo.svg">
  <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
  <?php require "cabecera.php"; // logo + menú común ?>
  <h1><?= $editando ? "Modificar zapatilla" : "Nueva zapatilla" ?></h1>

  <!-- enctype="multipart/form-data": OBLIGATORIO para enviar archivos. Sin él, el
       navegador solo manda el nombre de la foto y $_FILES llega vacío. -->
  <form method="post" action="../negocio/guardar_zapatilla.php" enctype="multipart/form-data">

    <?php if ($editando) { ?>
      <!-- El id viaja OCULTO para que guardar sepa QUÉ zapatilla actualizar.
           Se puede cambiar con F12 → guardar lo vuelve a validar. -->
      <input type="hidden" name="id_zapatilla" value="<?= $id ?>">
    <?php } ?>

    <!-- maxlength igual que en la BD: modelo varchar(100), color varchar(20) -->
    <label for="modelo">Modelo *</label>
    <input type="text" id="modelo" name="modelo" maxlength="100" required
    value="<?= htmlspecialchars($zapatilla["modelo"]) ?>">

    <label for="color">Color *</label>
    <input type="text" id="color" name="color" maxlength="20" required
    value="<?= htmlspecialchars($zapatilla["color"]) ?>">

    <!-- Precio: type="number" con step="0.01" (céntimos) y min="0.01" (ni gratis ni negativo).
         max = el mayor valor que cabe en decimal(8,2). Ayuda al usuario, pero se puede
         saltar: guardar_zapatilla.php vuelve a comprobar que precio > 0. -->
    <label for="precio">Precio (€) *</label>
    <input type="number" id="precio" name="precio" step="0.01" min="0.01" max="999999.99" required
    value="<?= htmlspecialchars($zapatilla["precio"]) ?>">

    <!-- FOTO (opcional). accept solo filtra lo que enseña el explorador de archivos:
         se puede saltar, así que guardar_zapatilla.php comprueba el tipo REAL del archivo. -->
    <label for="imagen">Foto (JPG, PNG o WEBP, máx. 2 MB)</label>
    <?php if ($zapatilla["imagen"] !== null) { ?>
      <!-- Al modificar se enseña la foto actual; si no se elige otra, se conserva -->
      <img class="foto-actual" src="img/zapatillas/<?= htmlspecialchars($zapatilla["imagen"]) ?>" alt="Foto actual">
      <small>Déjalo vacío para conservar la foto actual.</small>
    <?php } ?>
    <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp">

    <button type="submit">Guardar</button>
  </form>

  <p><a href="admin_zapatillas.php">Volver a la lista</a></p>
<?php require "pie.php"; ?>
</body>
</html>