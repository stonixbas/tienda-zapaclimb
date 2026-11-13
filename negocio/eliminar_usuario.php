<?php

/* eliminar_usuario.php en la capa de negocio: borra un usuario. Lo llama por POST el
   botón «Eliminar» de admin_usuarios.php. Solo puede usarlo un admin, que no puede
   borrarse a sí mismo. Si el usuario tiene compras, la clave foránea de ticket
   (ON DELETE RESTRICT) impide el borrado para conservar el historial, y se muestra
   un mensaje explicándolo. Al terminar vuelve a la lista de usuarios. */
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
  header("Location: ../cliente/admin_usuarios.php");
  exit;
}

// El id llega del campo oculto del botón: se puede manipular con F12 → se valida
$id = filter_var($_POST["id_usuario"] ?? "", FILTER_VALIDATE_INT);
if ($id === false) {
  echo "<p>Usuario no válido.</p>";
  echo '<a href="../cliente/admin_usuarios.php">Volver a la lista</a>';
  exit;
}

/* La comprobación DE VERDAD de "no puedes borrarte a ti mismo".
   En la lista solo se ocultaba el botón (comodidad); aquí es donde se impide,
   porque alguien podría cambiar el id oculto de otra fila por el suyo con F12. */
if ($id == $_SESSION["id_usuario"]) {
  echo "<p>No puedes eliminar tu propia cuenta. Debe hacerlo otro admin.</p>";
  echo '<a href="../cliente/admin_usuarios.php">Volver a la lista</a>';
  exit;
}

try {
  $bd = conectar();
  // ¡Con WHERE! Un DELETE sin WHERE vacía la tabla entera
  $consulta = $bd->prepare("DELETE FROM usuario WHERE id_usuario = :id");
  $consulta->execute([":id" => $id]);
} catch (PDOException $e) {
  /* Si el usuario tiene compras, la FK con RESTRICT bloquea el borrado y MySQL
     lanza un error. Su código SQLSTATE es 23000 (el mismo que el UNIQUE duplicado),
     así que para saber que es ESTE caso se mira el código propio de MySQL:
     1451 = "Cannot delete or update a parent row: a foreign key constraint fails".
     errorInfo es un array: [0] = SQLSTATE, [1] = código de MySQL, [2] = mensaje. */
  if ($e->errorInfo[1] == 1451) {
    echo "<p>No se puede eliminar este usuario porque tiene compras registradas.
          Su historial debe conservarse.</p>";
  } else {
    echo "<p>No se ha podido eliminar el usuario. Inténtalo más tarde.</p>";
  }
  echo '<a href="../cliente/admin_usuarios.php">Volver a la lista</a>';
  exit;
}

// Borrado correcto → de vuelta a la lista (el usuario ya no aparece)
header("Location: ../cliente/admin_usuarios.php");
exit;