/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.16-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: tienda_zapatillas
-- ------------------------------------------------------
-- Server version	10.11.16-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `tienda_zapatillas`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `tienda_zapatillas` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `tienda_zapatillas`;

--
-- Table structure for table `compra`
--

DROP TABLE IF EXISTS `compra`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `compra` (
  `id_compra` int(11) NOT NULL AUTO_INCREMENT,
  `id_ticket` int(11) NOT NULL,
  `id_talla` int(11) NOT NULL,
  `cantidad` int(10) NOT NULL,
  `precio_unitario` decimal(8,2) NOT NULL,
  PRIMARY KEY (`id_compra`),
  KEY `fk_compra_talla` (`id_talla`),
  KEY `fk_compra_ticket` (`id_ticket`),
  CONSTRAINT `fk_compra_talla` FOREIGN KEY (`id_talla`) REFERENCES `talla` (`id_talla`),
  CONSTRAINT `fk_compra_ticket` FOREIGN KEY (`id_ticket`) REFERENCES `ticket` (`id_ticket`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compra`
--

LOCK TABLES `compra` WRITE;
/*!40000 ALTER TABLE `compra` DISABLE KEYS */;
INSERT INTO `compra` VALUES
(4,4,7,2,129.99),
(5,5,7,1,129.99),
(6,6,17,2,89.90);
/*!40000 ALTER TABLE `compra` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `talla`
--

DROP TABLE IF EXISTS `talla`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `talla` (
  `id_talla` int(11) NOT NULL AUTO_INCREMENT,
  `id_zapatilla` int(10) NOT NULL,
  `numero_talla` decimal(3,1) NOT NULL,
  `stock` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_talla`),
  UNIQUE KEY `uq_talla_zapatilla_numero` (`id_zapatilla`,`numero_talla`),
  CONSTRAINT `fk_talla_zapatilla` FOREIGN KEY (`id_zapatilla`) REFERENCES `zapatilla` (`id_zapatilla`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `talla`
--

LOCK TABLES `talla` WRITE;
/*!40000 ALTER TABLE `talla` DISABLE KEYS */;
INSERT INTO `talla` VALUES
(7,1,42.0,5),
(8,1,42.5,2),
(14,2,31.0,2),
(15,2,32.0,2),
(16,2,33.0,2),
(17,2,40.0,0),
(18,2,42.0,2),
(19,10,43.0,2),
(21,10,44.0,2),
(22,10,46.0,2),
(23,3,43.0,2),
(24,3,46.0,2),
(25,11,46.0,3),
(26,11,39.0,2),
(27,2,43.5,4);
/*!40000 ALTER TABLE `talla` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket`
--

DROP TABLE IF EXISTS `ticket`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket` (
  `id_ticket` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `total` decimal(8,2) NOT NULL,
  PRIMARY KEY (`id_ticket`),
  KEY `fk_ticket_usuario` (`id_usuario`),
  CONSTRAINT `fk_ticket_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket`
--

LOCK TABLES `ticket` WRITE;
/*!40000 ALTER TABLE `ticket` DISABLE KEYS */;
INSERT INTO `ticket` VALUES
(4,8,'2026-11-12 10:26:42',259.98),
(5,8,'2026-11-13 10:19:12',129.99),
(6,14,'2026-11-13 12:27:19',179.80);
/*!40000 ALTER TABLE `ticket` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario`
--

DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_apellidos` varchar(100) NOT NULL,
  `rol` varchar(20) NOT NULL,
  `nombre_usuario` varchar(40) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `direccion` varchar(150) NOT NULL,
  `telefono` varchar(15) NOT NULL,
  `genero` varchar(20) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  UNIQUE KEY `correo` (`correo`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` VALUES
(4,'prueba 1','cliente','prueba1','prueba@prueba.com','$2y$10$m81JSnnWceOBoq5JR3gUteiTcQAPWYRWmYBFi6sLfFPl/oA.gH/Z6','Calle currucote 48','123456789',NULL,NULL),
(7,'admin1','admin','admin1','admin@admin.com','$2y$10$uFdcLfIpAZ9HTcD9ucH2huHFHSOlKaoi6RCa22CrXSZg0Wbw4Vd0u','Calle lupion 85','123456789',NULL,'1990-11-02'),
(8,'prueba2','cliente','prueba2','prueba2@prueba2.com','$2y$10$7EOBjn3VmnFzz6VXajAn.urDg5z4kNgS0iA1NDbaP6aWyUN0GFPEm','Calle Cozar 5','123456789',NULL,NULL),
(9,'Prueba Tres','cliente','prueba3','prueba3@prueba.com','$2y$10$h/1Ogp.qC9gbytrEiayC8e.dWmQSh.DH1jMmkipkZn2/0ZnH6IjS.','Calle Modificada 33','612345678','femenino','2000-05-10'),
(14,'prueba4','cliente','prueba4','prueba4@prueba.com','$2y$10$m0mVBWYaqL9rEyeC.6YWBe5C3M3VUYRMDfDvrhLNMfwwW1OM6uetK','Calle belmez 3','123123223',NULL,NULL);
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zapatilla`
--

DROP TABLE IF EXISTS `zapatilla`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `zapatilla` (
  `id_zapatilla` int(10) NOT NULL AUTO_INCREMENT,
  `modelo` varchar(100) NOT NULL,
  `color` varchar(20) NOT NULL,
  `precio` decimal(8,2) NOT NULL,
  `imagen` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_zapatilla`),
  UNIQUE KEY `uq_zapatilla_modelo_color` (`modelo`,`color`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zapatilla`
--

LOCK TABLES `zapatilla` WRITE;
/*!40000 ALTER TABLE `zapatilla` DISABLE KEYS */;
INSERT INTO `zapatilla` VALUES
(1,'Nike Air Max','Blanco',129.99,'a1ea6a0e15598692.webp'),
(2,'Adidas Samba','Blanco',89.90,'ac1e63302dab2e2d.webp'),
(3,'Nike Air Max','Negro',129.99,'7115d5c315040f65.webp'),
(10,'Nike Air Force 1','Blanco',98.99,'ce9e0395f0b49bc5.webp'),
(11,'Nike Air Force 1','Negro',98.99,'d474dc7390e5b24d.webp');
/*!40000 ALTER TABLE `zapatilla` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed
