<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acceso a clientes</title>
  <!-- Icono de la pestaña y hoja de estilos común (capa cliente: CSS) -->
  <link rel="icon" href="img/logo.svg">
  <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
  <?php require "cabecera.php"; // logo + menú común ?>
  <h1>Acceso a clientes</h1>
    <?php
    /* Si procesar_login.php falla, nos devuelve aquí con ?error=1 en la URL.
    $_GET lee lo que va en la URL; isset() comprueba si existe.
    El mensaje es el mismo falle el usuario o la clave para asi evitar la enumeración de clientes. */
    if (isset($_GET["error"])) {
      echo "<p class=\"alerta\">Usuario o contraseña incorrectos</p>";
    }
    ?>

    <!-- POST: la clave no debe aparecer nunca en la URL -->
    <form method="post" action="../negocio/procesar_login.php">
      <?php if (($_GET["volver"] ?? "") === "catalogo") { ?>
        <!-- Viene de «Inicia sesión para comprar»: al entrar, volverá a la tienda -->
        <input type="hidden" name="volver" value="catalogo">
      <?php } ?>

      <label for="nombre_usuario">Usuario</label>
      <input type="text" id="nombre_usuario" name="nombre_usuario" maxlength="40" required>

    <!-- type="password" oculta lo que se escribe. En el login no se pone minlength:
         solo se comprueba si coincide; las reglas de longitud son cosa del registro -->  
      <label for="password">Contraseña</label>
      <input type="password" id="password" name="password" required>

      <button type="submit">Entrar</button>
    </form>

    <!-- Enlace para quien aún no tiene cuenta -->
    <p>¿No eres cliente? <a href="registro.php">Hazte cliente</a></p>

  <?php require "pie.php"; ?>

  </body>
</html>
