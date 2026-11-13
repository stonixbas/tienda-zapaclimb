<?php
/* session_start() siempre lo primero, antes de cualquier echo o html ya que 
envía la cookie de sesion en las cabeceras, y las cabeceras van antes que el contenido. */

session_start();

require_once "../datos/conexion.php";
require_once "../cliente/plantilla_mensaje.php"; // los mensajes de esta página salen con el diseño común

// solo se entra enviando el formulario en post para que no lleve la info en la url
if ($_SERVER["REQUEST_METHOD"] != "POST") {
  header("Location: ../cliente/login.php");
  exit;
}

/* Recogemos el usuario con trim que quita espacios de los lado y la contraseña
sin trim ya que un espacio puede formar parte de esta */
$nombre_usuario = trim($_POST["nombre_usuario"] ?? "");
$password = $_POST["password"] ?? "";

try {
  $conectar = conectar();

  /* Se busca solo por el usuario ya que no se puede poner "And password = :password":
  el hash lleva una sal aleatoria, asi que no coincide con nada escrito y por eso 
  se pide solo las columnas necesarias, no select *. */
  $sql = "SELECT id_usuario, nombre_usuario, password, rol FROM usuario WHERE nombre_usuario = :nombre_usuario";
  $consulta = $conectar->prepare($sql);
  $consulta->execute([":nombre_usuario" => $nombre_usuario]);

  // Fetch() devuelve una fila como array, o false si no hay ningun usuario
  $usuario = $consulta->fetch();

} catch (PDOException $e) {
  // Mensaje genérico de error ya que el real podria mostrar la consulta o la estructura de la base de datos.
  die("No se ha podido iniciar sesión. Inténtelo más tarde.");
}

/* Un unico if para los dos fallos, pero la misma respuesta para ambos, asi un atacante
no puede saber si el usuario existe o no,si $usuario es false, el || ya no evalua lo de la derecha
y no se intenta leer $usuario ["password"] de algo que no existe*/ 
/* ¿Hay que volver a la tienda al entrar? Solo se acepta el valor "catalogo" (LISTA BLANCA,
   como el rol): si se aceptara cualquier dirección, un atacante podría mandar un enlace
   de login que, tras entrar, llevara a una web falsa (redirección abierta). */
$volver_tienda = (($_POST["volver"] ?? "") === "catalogo");

if ($usuario === false || !password_verify($password, $usuario["password"])) {
  // Si venía de la tienda, se conserva el "volver" para el siguiente intento
  header("Location: ../cliente/login.php?error=1" . ($volver_tienda ? "&volver=catalogo" : ""));
  exit;
}

/* Login correcto, session_regenerate_id(true) cambia la "llave" de la sesion al entrar
y si alguien te habia colado una llave antes (fijación de sesion), deja de valer ya que
el true borra la sesión antigua */
session_regenerate_id(true);

/* Se guarda solo lo necesario para las demás páginas, NUNCA el hash de la contraseña.
   El rol servirá para dejar pasar solo al admin a su panel. */
$_SESSION["id_usuario"] = $usuario["id_usuario"];
$_SESSION["nombre_usuario"] = $usuario["nombre_usuario"];
$_SESSION["rol"] = $usuario["rol"];

// Venía de "Inicia sesión para comprar" → de vuelta a la tienda; si no, a su inicio
header("Location: ../cliente/" . ($volver_tienda ? "catalogo.php" : "inicio.php"));
exit;