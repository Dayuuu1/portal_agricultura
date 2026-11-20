-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: localhost    Database: test_wordpress
-- ------------------------------------------------------

DROP TABLE IF EXISTS `wp_posts`;
CREATE TABLE `wp_posts` (
  `ID` bigint unsigned NOT NULL AUTO_INCREMENT,
  `post_author` bigint unsigned NOT NULL DEFAULT '0',
  `post_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_content` longtext NOT NULL,
  `post_title` text NOT NULL,
  `post_excerpt` text NOT NULL,
  `post_status` varchar(20) NOT NULL DEFAULT 'publish',
  `post_name` varchar(200) NOT NULL DEFAULT '',
  `post_type` varchar(20) NOT NULL DEFAULT 'post',
  `post_mime_type` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`),
  KEY `type_status_date` (`post_type`,`post_status`,`post_date`,`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

LOCK TABLES `wp_posts` WRITE;
INSERT INTO `wp_posts` VALUES (10,1,'2025-03-10 09:00:00','[vc_row][vc_column][vc_column_text]Los agricultores de Iberia recibieron semillas certificadas de cacao para renovar sus parcelas.\n\nLa entrega se realizó en coordinación con la \"Agencia Agraria\" y el Gobierno Regional de Madre de Dios, que destacó el compromiso de los productores.[/vc_column_text][/vc_column][/vc_row][vc_row][vc_column][vc_gallery interval=\"3\" images=\"20,21\" img_size=\"full\"][/vc_column][/vc_row]','GOREMAD ENTREGÓ SEMILLAS DE CACAO A AGRICULTORES DE IBERIA','','publish','goremad-entrego-semillas','post',''),(11,1,'2020-06-23 16:02:46','Курс биткоина к доллару онлайн график текущей цены','Курс биткоина к доллару','','publish','kurs-bitkoina','post',''),(12,1,'2025-04-02 10:00:00','[vc_row][vc_column][vc_column_text]LINK: [ddownload id=\"30\"][/vc_column_text][/vc_column][/vc_row]','RESOLUCION DIRECTORAL N° 015-2025-GOREMAD-GRDE/DRA','','publish','resolucion-directoral-015-2025','post',''),(13,1,'2025-05-20 11:00:00','<ul><li>[ddownload id=\"31\"]</li><li>[ddownload id=\"32\"]</li></ul>','RESULTADO FINAL DE CONVOCATORIA CAS N° 01-2025-GOREMAD-GRDE/DRA','','publish','resultado-final-cas-01-2025','post',''),(14,1,'2018-08-01 10:00:00','<p>La Oficina de Control Institucional es el órgano encargado de ejecutar el control gubernamental en la entidad.</p>\n<ul>\n<li>Ejecutar las acciones de control.</li>\n<li>Recibir y atender denuncias.</li>\n</ul>','Oficina de Control Institucional','','publish','oficina-de-control-institucional','page','');
INSERT INTO `wp_posts` VALUES (20,1,'2025-03-10 08:00:00','','foto1','','inherit','foto1','attachment','image/jpeg'),(21,1,'2025-03-10 08:00:00','','foto2','','inherit','foto2','attachment','image/jpeg'),(30,1,'2025-04-02 09:30:00','','RESOLUCION DIRECTORAL N° 015-2025-GOREMAD-GRDE/DRA','','publish','rd-015-2025','dedo_download',''),(31,1,'2025-05-01 09:00:00','','Bases CAS 01-2025','','publish','bases-cas','dedo_download',''),(32,1,'2025-05-20 10:00:00','','Resultado final CAS 01-2025','','publish','resultado-cas','dedo_download',''),(33,1,'2025-06-30 09:00:00','','PRODUCCION DE LOS PRINCIPALES CULTIVOS 2025','','publish','produccion-2025','dedo_download',''),(34,1,'2025-07-01 09:00:00','','Copia: produccion de los principales cultivos','','publish','produccion-copia','dedo_download',''),(35,1,'2025-07-02 09:00:00','','Borrador sin publicar','','draft','borrador','dedo_download','');
UNLOCK TABLES;

DROP TABLE IF EXISTS `wp_postmeta`;
CREATE TABLE `wp_postmeta` (
  `meta_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint unsigned NOT NULL DEFAULT '0',
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext,
  PRIMARY KEY (`meta_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

LOCK TABLES `wp_postmeta` WRITE;
INSERT INTO `wp_postmeta` VALUES (1,10,'_thumbnail_id','20'),(2,10,'wpb_post_neder_views_count','57'),(3,20,'_wp_attached_file','2025/03/foto1.jpg'),(4,21,'_wp_attached_file','2025/03/foto2.jpg'),(5,30,'_dedo_file_url','https://www.dramdd.gob.pe/wp-content/uploads/delightful-downloads/2025/04/rd-015.pdf'),(6,31,'_dedo_file_url','https://www.dramdd.gob.pe/wp-content/uploads/delightful-downloads/2025/05/cas-bases.pdf'),(7,32,'_dedo_file_url','https://www.dramdd.gob.pe/wp-content/uploads/delightful-downloads/2025/05/cas-final.pdf'),(8,33,'_dedo_file_url','https://www.dramdd.gob.pe/wp-content/uploads/delightful-downloads/2025/06/produccion.pdf'),(9,34,'_dedo_file_url','https://www.dramdd.gob.pe/wp-content/uploads/delightful-downloads/2025/07/produccion-copia.pdf'),(10,35,'_dedo_file_url','https://www.dramdd.gob.pe/wp-content/uploads/delightful-downloads/2025/07/borrador.pdf'),(11,10,'_edit_lock','1700000000:1');
UNLOCK TABLES;

DROP TABLE IF EXISTS `wp_terms`;
CREATE TABLE `wp_terms` (
  `term_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL DEFAULT '',
  `slug` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`term_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

LOCK TABLES `wp_terms` WRITE;
INSERT INTO `wp_terms` VALUES (1,'Sin categoría','sin-categoria'),(11,'Noticias','noticias');
UNLOCK TABLES;

DROP TABLE IF EXISTS `wp_term_taxonomy`;
CREATE TABLE `wp_term_taxonomy` (
  `term_taxonomy_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `term_id` bigint unsigned NOT NULL DEFAULT '0',
  `taxonomy` varchar(32) NOT NULL DEFAULT '',
  `count` bigint NOT NULL DEFAULT '0',
  PRIMARY KEY (`term_taxonomy_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

LOCK TABLES `wp_term_taxonomy` WRITE;
INSERT INTO `wp_term_taxonomy` VALUES (1,1,'category',1),(11,11,'category',3);
UNLOCK TABLES;

DROP TABLE IF EXISTS `wp_term_relationships`;
CREATE TABLE `wp_term_relationships` (
  `object_id` bigint unsigned NOT NULL DEFAULT '0',
  `term_taxonomy_id` bigint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`object_id`,`term_taxonomy_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

LOCK TABLES `wp_term_relationships` WRITE;
INSERT INTO `wp_term_relationships` VALUES (10,11),(11,1),(12,11),(13,11);
UNLOCK TABLES;

DROP TABLE IF EXISTS `wp_ddownload_statistics`;
CREATE TABLE `wp_ddownload_statistics` (
  `ID` bigint unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(10) NOT NULL DEFAULT 'success',
  `date` datetime NOT NULL,
  `post_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL DEFAULT '0',
  `user_ip` varbinary(16) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

LOCK TABLES `wp_ddownload_statistics` WRITE;
INSERT INTO `wp_ddownload_statistics` VALUES (1,'success','2025-04-03 10:00:00',30,0,_binary '\0\0\0','Mozilla/5.0 (Windows; \'test\')'),(2,'success','2025-04-04 10:00:00',30,0,_binary 'abcd','Mozilla/5.0'),(3,'success','2025-04-05 10:00:00',30,0,_binary 'abcd','Mozilla/5.0'),(4,'failed','2025-04-06 10:00:00',30,0,_binary 'abcd','Mozilla/5.0');
UNLOCK TABLES;
