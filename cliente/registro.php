<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Alta de cliente</title>
  <!-- Icono de la pestaña y hoja de estilos común (capa cliente: CSS) -->
  <link rel="icon" href="img/logo.svg">
  <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
  <?php require "cabecera.php"; // logo + menú común ?>
  <h1>Hazte cliente</h1>

  <!-- Envío de datos por POST (para que no aparezca en la URL) al archivo de la capa de negocio -->
  <form method="post" action="../negocio/procesar_registro.php">

    <label for="nombre_apellidos">Nombre completo *</label>
    <input type="text" id="nombre_apellidos" name="nombre_apellidos" maxlength="100" required>

    <label for="nombre_usuario">Usuario *</label>
    <input type="text" id="nombre_usuario" name="nombre_usuario" maxlength="40" required>

    <label for="correo">Correo electrónico *</label>
    <input type="email" id="correo" name="correo" maxlength="150" required>

    <label for="password">Contraseña *</label>
    <input type="password" id="password" name="password" minlength="8" required>

    <label for="direccion">Dirección *</label>
    <input type="text" id="direccion" name="direccion" maxlength="150" required>

    <label for="telefono">Teléfono *</label>
    <input type="tel" id="telefono" name="telefono" maxlength="15" required>

    <label for="genero">Género</label>
    <select id="genero" name="genero">
      <option value="">-- Sin indicar --</option>  
      <option value="masculino">Masculino</option>
      <option value="femenino">Femenino</option>
    </select>

    <label for="fecha_nacimiento">Fecha de nacimiento</label>
    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento">

    <button type="submit">Regístrame</button>
        
  </form>
<p>Los campos con * son obligatorios.</p>

<?php require "pie.php"; ?>

</body>
</html>