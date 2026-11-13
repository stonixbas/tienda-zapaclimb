# ZapaClimb · Tienda de zapatillas

Trabajo de enfoque de **Desarrollo web en entorno servidor** (DAW). Tienda online de zapatillas hecha en PHP con PDO y MySQL/MariaDB, organizada en tres capas.

## Requisitos funcionales

- **RF01 · Usuarios:** registro de clientes, inicio de sesión con contraseñas cifradas (`password_hash`) y CRUD de usuarios para el administrador.
- **RF02 · Zapatillas y tallas:** CRUD de zapatillas (con foto) y de sus tallas con stock, solo para el administrador.
- **RF03 · Compra:** catálogo abierto a todos, carrito en sesión para clientes y confirmación de la compra en una transacción (ticket + líneas + resta de stock).

## Estructura (tres capas)

```
tienda/
├── cliente/   capa de presentación: páginas, CSS, imágenes
├── negocio/   capa de lógica: valida, decide y redirige
├── datos/     capa de datos: conexion.php (PDO)
└── bd/        volcado SQL de la base de datos
```

## Instalación en local (XAMPP)

1. Copia la carpeta `tienda` en `C:\xampp\htdocs\`.
2. Arranca Apache y MySQL desde el panel de XAMPP.
3. En phpMyAdmin, importa `bd/tienda_zapatillas.sql` (crea la base de datos `tienda_zapatillas` con datos de prueba).
4. Abre `http://localhost:8080/tienda/` (o el puerto que use tu Apache).

> `datos/conexion.php` usa el usuario `root` sin contraseña, válido solo para XAMPP en local.
