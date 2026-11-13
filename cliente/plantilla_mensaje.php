<?php
/* plantilla_mensaje.php en la capa cliente: da el diseño común (cabecera, menú y pie)
   a los mensajes que escriben los archivos de negocio/ ("Ese usuario ya está registrado",
   "No hay stock suficiente"...). Sin esto salían en una página blanca sin estilo.

   Cómo funciona, sin tener que cambiar cada echo de negocio/:
   1. ob_start() abre un "buffer" (búfer de salida): todo lo que la página escribe con
      echo o die() se guarda en memoria en vez de mandarse ya al navegador.
   2. register_shutdown_function() deja apuntada una función que PHP ejecuta al terminar
      la página, también cuando termina con exit o die().
   3. Esa función recoge lo guardado (ob_get_clean) y, si hay texto, lo escribe dentro
      de la página completa con cabecera.php y pie.php.

   Si la página no ha escrito nada (por ejemplo, porque ha hecho header("Location: ...")),
   no se añade nada y la redirección funciona igual. Si ya ha escrito una página completa
   (confirmar_compra.php con su "¡Compra realizada!"), tampoco se toca.

   Se incluye con require_once al principio de cada archivo de negocio/. */

ob_start();

register_shutdown_function(function () {
  $contenido = ob_get_clean();

  // Nada que mostrar (redirección) o ya es una página completa → se deja tal cual
  if ($contenido === false || trim($contenido) === "" || stripos($contenido, "<!DOCTYPE") !== false) {
    echo $contenido;
    return;
  }

  // El archivo de negocio está en otra carpeta: los enlaces del menú necesitan "../cliente/"
  $ruta = "../cliente/";
  ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ZapaClimb</title>
  <link rel="icon" href="../cliente/img/logo.svg">
  <link rel="stylesheet" href="../cliente/css/estilos.css">
</head>
<body>
  <?php require __DIR__ . "/cabecera.php"; ?>
  <!-- Tarjeta con el mensaje que escribió el archivo de negocio -->
  <div class="mensaje">
    <?= $contenido ?>
  </div>
  <?php require __DIR__ . "/pie.php"; ?>
</body>
</html>
<?php
});
