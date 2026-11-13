<?php
/* index.php: puerta de entrada de la web (http://localhost:8080/tienda/).
   La portada es la tienda, abierta a todos: se redirige al catálogo. */
header("Location: cliente/catalogo.php");
exit;
