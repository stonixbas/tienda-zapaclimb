<?php
/* cabecera.php en la capa cliente: cabecera COMÚN de todas las páginas (logo + menú).
   Se incluye con require justo después de <body>, así el menú se escribe una sola vez
   (misma idea que conectar() en conexion.php). Abre el <main> que cierra pie.php.

   El menú cambia según quién esté conectado:
   - sin sesión: Tienda, Entrar y Hazte cliente
   - cliente:    Inicio, Tienda, Carrito (con las unidades) y Salir
   - admin:      Inicio, Usuarios, Zapatillas, Ver tienda y Salir

   $ruta: prefijo de los enlaces. Las páginas de cliente/ no lo necesitan; una página de
   otra carpeta (negocio/confirmar_compra.php) pone $ruta = "../cliente/" antes del require. */
$ruta = $ruta ?? "";

/* login.php y registro.php no llaman a session_start(): en ellas $_SESSION no existe.
   Por eso se comprueba con isset antes de leer el rol (si no, PHP daría un aviso). */
$rol = isset($_SESSION["rol"]) ? $_SESSION["rol"] : null;
$unidades = array_sum($_SESSION["carrito"] ?? []);

// Página actual (ej. "catalogo.php"), para marcar en el menú dónde está el usuario
$actual = basename($_SERVER["PHP_SELF"]);

// Devuelve ' class="activo"' si el enlace es el de la página actual
function activo($pagina, $actual) {
  return $pagina === $actual ? ' class="activo"' : "";
}
?>
<header class="cabecera">
  <div class="cabecera-dentro">
    <a class="marca" href="<?= $ruta ?><?= $rol ? "inicio.php" : "catalogo.php" ?>">
      <img src="<?= $ruta ?>img/logo.svg" alt="" width="40" height="40">
      <span>Zapa<strong>Climb</strong></span>
    </a>

    <nav class="menu" aria-label="Menú principal">
      <?php if ($rol === "cliente") { ?>
        <a href="<?= $ruta ?>inicio.php"<?= activo("inicio.php", $actual) ?>>Inicio</a>
        <a href="<?= $ruta ?>catalogo.php"<?= activo("catalogo.php", $actual) ?>>Tienda</a>
        <a href="<?= $ruta ?>carrito.php"<?= activo("carrito.php", $actual) ?>>Carrito <span class="globo"><?= $unidades ?></span></a>
      <?php } elseif ($rol === "admin") { ?>
        <a href="<?= $ruta ?>inicio.php"<?= activo("inicio.php", $actual) ?>>Inicio</a>
        <a href="<?= $ruta ?>admin_usuarios.php"<?= activo("admin_usuarios.php", $actual) ?>>Usuarios</a>
        <a href="<?= $ruta ?>admin_zapatillas.php"<?= activo("admin_zapatillas.php", $actual) ?>>Zapatillas</a>
        <!-- El admin también puede ver la tienda (sin comprar) para revisar cómo la ve el cliente -->
        <a href="<?= $ruta ?>catalogo.php"<?= activo("catalogo.php", $actual) ?>>Ver tienda</a>
      <?php } else { ?>
        <!-- Visitante: la tienda está abierta a todos; para comprar hay que entrar -->
        <a href="<?= $ruta ?>catalogo.php"<?= activo("catalogo.php", $actual) ?>>Tienda</a>
        <a href="<?= $ruta ?>login.php"<?= activo("login.php", $actual) ?>>Entrar</a>
        <a href="<?= $ruta ?>registro.php"<?= activo("registro.php", $actual) ?>>Hazte cliente</a>
      <?php } ?>

      <?php if ($rol) { ?>
        <!-- El nombre lo escribió el usuario al registrarse → htmlspecialchars (XSS) -->
        <span class="usuario"><?= htmlspecialchars($_SESSION["nombre_usuario"]) ?></span>
        <a class="salir" href="<?= $ruta ?>../negocio/logout.php">Salir</a>
      <?php } ?>
    </nav>
  </div>
</header>
<main class="contenedor">
