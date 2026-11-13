<?php
/* catalogo.php en la capa cliente: catálogo de la tienda, ABIERTO A TODOS.
   Muestra cada zapatilla con sus tallas. Mirar es libre; comprar exige cuenta:
   - cliente:     elige talla y cantidad y la añade al carrito (negocio/anadir_carrito.php)
   - sin sesión:  ve los artículos y un botón para iniciar sesión y comprar
   - admin:       ve la tienda como la ve el cliente, pero sin comprar
   Las tallas sin stock se ven pero no se pueden elegir. */
session_start();
require_once "../datos/conexion.php";

/* Sin guardián: aquí puede entrar cualquiera (como en una tienda real).
   La protección de verdad está en anadir_carrito.php, carrito.php y confirmar_compra.php,
   que siguen exigiendo sesión y rol cliente: aunque alguien fabrique el formulario
   a mano, el servidor no le deja comprar. */
$rol = $_SESSION["rol"] ?? null;   // null = visitante sin sesión

try {
  $bd = conectar();
  // Sin datos del usuario en las consultas → query() basta
  $zapatillas = $bd->query("SELECT id_zapatilla, modelo, color, precio, imagen FROM zapatilla ORDER BY modelo")->fetchAll();
  $filas_tallas = $bd->query("SELECT id_talla, id_zapatilla, numero_talla, stock FROM talla ORDER BY numero_talla")->fetchAll();
} catch (PDOException $e) {
  die("No se ha podido cargar el catálogo.");
}

/* Se agrupan las tallas por zapatilla: $tallas[3] = tallas de la zapatilla 3.
   Así son 2 consultas en total, en vez de una consulta de tallas por cada zapatilla. */
$tallas = [];
foreach ($filas_tallas as $talla) {
  $tallas[$talla["id_zapatilla"]][] = $talla;
}

// Unidades que hay en el carrito, para el enlace de arriba. Si aún no existe, 0
$unidades = array_sum($_SESSION["carrito"] ?? []);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tienda</title>
  <!-- Icono de la pestaña y hoja de estilos común (capa cliente: CSS) -->
  <link rel="icon" href="img/logo.svg">
  <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
  <?php require "cabecera.php"; // logo + menú común ?>
  <h1>Tienda</h1>
  <!-- El texto de bienvenida cambia según quién mira la tienda -->
  <?php if ($rol === "cliente") { ?>
    <p>Elige tu modelo y tu talla. <a href="carrito.php">Ver carrito (<?= $unidades ?>)</a></p>
  <?php } elseif ($rol === "admin") { ?>
    <p class="aviso">Vista de administrador: ves la tienda como un cliente, pero los administradores no compran.</p>
  <?php } else { ?>
    <p>Echa un vistazo a nuestras zapatillas. Para comprar, <a href="login.php?volver=catalogo">inicia sesión</a> o <a href="registro.php">hazte cliente</a>.</p>
  <?php } ?>

  <?php if (count($zapatillas) === 0) { ?>
    <p>Todavía no hay zapatillas en la tienda.</p>
  <?php } ?>

  <!-- Rejilla de tarjetas: el CSS (grid) pone tantas columnas como quepan en la pantalla -->
  <div class="rejilla">
  <?php foreach ($zapatillas as $zapatilla) { ?>
    <article class="tarjeta">
      <!-- Con foto (columna imagen) se enseña la foto; si es NULL, el dibujo genérico -->
      <?php if ($zapatilla["imagen"] !== null) { ?>
        <div class="tarjeta-imagen con-foto">
          <img src="img/zapatillas/<?= htmlspecialchars($zapatilla["imagen"]) ?>" alt="<?= htmlspecialchars($zapatilla["modelo"]) ?>">
        </div>
      <?php } else { ?>
        <div class="tarjeta-imagen">
          <img src="img/zapatilla.svg" alt="<?= htmlspecialchars($zapatilla["modelo"]) ?>">
        </div>
      <?php } ?>
      <div class="tarjeta-cuerpo">
    <h2><?= htmlspecialchars($zapatilla["modelo"]) ?></h2>
    <p class="color"><?= htmlspecialchars($zapatilla["color"]) ?></p>
    <p class="precio"><?= number_format($zapatilla["precio"], 2, ",", ".") ?> €</p>

    <?php if (empty($tallas[$zapatilla["id_zapatilla"]])) { ?>
      <!-- Zapatilla sin tallas dadas de alta: se muestra, pero no se puede comprar -->
      <p class="agotada">Sin tallas disponibles.</p>
    <?php } elseif ($rol !== "cliente") { ?>
      <!-- Visitante o admin: se ven las tallas (las agotadas, tachadas), pero sin formulario -->
      <p class="tallas">
        <?php foreach ($tallas[$zapatilla["id_zapatilla"]] as $talla) { ?>
          <span class="<?= $talla["stock"] > 0 ? "talla" : "talla agotada-talla" ?>"><?= str_replace(".", ",", (float) $talla["numero_talla"]) ?></span>
        <?php } ?>
      </p>
      <?php if ($rol === null) { ?>
        <!-- ?volver=catalogo: tras iniciar sesión, procesar_login.php devuelve a la tienda -->
        <a class="boton" href="login.php?volver=catalogo">Inicia sesión para comprar</a>
      <?php } ?>
    <?php } else { ?>
      <!-- Solo se envían el id de la talla y la cantidad. El precio NO viaja en el
           formulario: se lee de la BD, así no se puede cambiar con F12 -->
      <form action="../negocio/anadir_carrito.php" method="post">
        <label>Talla
          <select name="id_talla" required>
            <option value="">-- Elige --</option>
            <?php foreach ($tallas[$zapatilla["id_zapatilla"]] as $talla) { ?>
              <!-- Sin stock: la talla se ve pero disabled impide elegirla.
                   (Se puede saltar con F12 → anadir_carrito.php vuelve a mirar el stock) -->
              <option value="<?= $talla["id_talla"] ?>" <?= $talla["stock"] > 0 ? "" : "disabled" ?>>
                <?= str_replace(".", ",", (float) $talla["numero_talla"]) ?><?= $talla["stock"] > 0 ? "" : " (sin stock)" ?>
              </option>
            <?php } ?>
          </select>
        </label>
        <label>Cantidad
          <input type="number" name="cantidad" min="1" step="1" value="1" required>
        </label>
        <button type="submit">Añadir al carrito</button>
      </form>
    <?php } ?>
      </div>
    </article>
  <?php } ?>
  </div>
<?php require "pie.php"; ?>
</body>
</html>