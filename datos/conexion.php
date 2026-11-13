<?php
/* conexion.php · Capa de DATOS Contiene los datos de acceso a la base de datos "tienda_zapatillas" y la
  función que abre la conexión, controlando el caso de que esta falle.
  Lo usan los archivos de la capa de negocio (login, registro, carrito...)
  llamando a conectar(); así la conexión se escribe una sola vez.  */

/* Datos de conexión. Son constantes porque no deben cambiar durante la ejecución.
root sin contraseña solo vale en local (XAMPP): en un hosting real se usaría
un usuario con contraseña y solo los permisos necesarios. */
const BD_HOST = "localhost";
const BD_NOMBRE = "tienda_zapatillas";
const BD_USUARIO = "root";
const BD_CLAVE = "";

/* Abre la conexión con la base de datos. Devuelve: un objeto PDO listo para hacer consultas.
  Si la conexión falla, detiene la aplicación con un mensaje genérico. */
function conectar() {
  try {
    /* Cadena de conexión (DSN): tipo de BD, servidor, base de datos y juego de
    caracteres. utf8mb4 evita que las tildes y las eñes lleguen rotas a PHP. */
    $dsn = "mysql:host=" . BD_HOST . ";dbname=" . BD_NOMBRE . ";charset=utf8mb4";

    $opciones = [
      // Si una consulta falla, lanza una excepción en vez de devolver false sin avisar
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      // Las filas llegan como array con el nombre de la columna: $fila['correo']
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      /* Consultas preparadas reales en MySQL: separan la orden SQL de los datos
      que escribe el usuario y protegen contra la inyección SQL */
      PDO::ATTR_EMULATE_PREPARES => false
    ];

    // Crea la conexión y se la entrega a quien ha llamado a conectar()
    return new PDO($dsn, BD_USUARIO, BD_CLAVE, $opciones);

  } catch (PDOException $e) {
    /* Si no se puede conectar, la tienda no puede funcionar: se para la ejecución.
    el mensaje es genérico a propósito: el error real ($e->getMessage()) podría
    mostrar el usuario o el servidor de la base de datos a un atacante.*/
    die("No se pudo conectar a la tienda. Inténtalo de nuevo más tarde.");
  }
}
