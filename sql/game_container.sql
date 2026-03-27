-- MySQL dump 10.13  Distrib 8.0.38, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: game_container
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `developers`
--

DROP TABLE IF EXISTS `developers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `developers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(190) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `developers`
--

LOCK TABLES `developers` WRITE;
/*!40000 ALTER TABLE `developers` DISABLE KEYS */;
INSERT INTO `developers` VALUES (2,'CD Projekt RED'),(1,'FromSoftware'),(3,'Rockstar Games'),(4,'Sebastien Bénard'),(5,'Team Cherry'),(6,'Xbox Game Studios');
/*!40000 ALTER TABLE `developers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `game_genres`
--

DROP TABLE IF EXISTS `game_genres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `game_genres` (
  `game_id` int(11) NOT NULL,
  `genre_id` int(11) NOT NULL,
  PRIMARY KEY (`game_id`,`genre_id`),
  KEY `fk_gg_genre` (`genre_id`),
  CONSTRAINT `fk_gg_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_gg_genre` FOREIGN KEY (`genre_id`) REFERENCES `genres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `game_genres`
--

LOCK TABLES `game_genres` WRITE;
/*!40000 ALTER TABLE `game_genres` DISABLE KEYS */;
INSERT INTO `game_genres` VALUES (4,1),(4,2),(4,3),(5,1),(5,2),(5,3),(6,1),(6,2),(6,3),(6,4),(7,1),(7,2),(7,4),(7,5),(8,1),(8,2),(8,3),(8,6),(9,1),(9,2),(9,3),(9,6),(9,7),(10,1),(10,2),(10,3),(11,1),(11,2),(11,4),(11,8);
/*!40000 ALTER TABLE `game_genres` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `game_images`
--

DROP TABLE IF EXISTS `game_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `game_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `game_id` int(11) NOT NULL,
  `url` varchar(500) NOT NULL,
  `position` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_images_game` (`game_id`),
  CONSTRAINT `fk_images_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `game_images`
--

LOCK TABLES `game_images` WRITE;
/*!40000 ALTER TABLE `game_images` DISABLE KEYS */;
INSERT INTO `game_images` VALUES (17,4,'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1245620/ss_b1b91299d7e4b94201ac840aa64de54d9f5cb7f3.1920x1080.jpg?t=1748630546',1),(18,4,'https://files.tecnoblog.net/wp-content/uploads/2021/11/elden-ring-01-700x394.jpeg',2),(19,4,'https://substackcdn.com/image/fetch/$s_!kfzv!,f_auto,q_auto:good,fl_progressive:steep/https%3A%2F%2Fsubstack-post-media.s3.amazonaws.com%2Fpublic%2Fimages%2F164d2f62-6e5e-4e46-905e-4ee8f392c057_3840x2160.jpeg',3),(20,4,'https://sm.ign.com/ign_br/preview/e/elden-ring/elden-ring-the-first-preview_pctq.jpg',4),(25,5,'https://www.thewitcher.com/_next/image?url=%2F_next%2Fstatic%2Fmedia%2F1.4c59601c.jpg&w=3840&q=75',1),(26,5,'https://cdn.ome.lt/legacy/images/galerias/the-witcher3/the-witcher-09-14dejunho2013.jpg',2),(27,5,'https://sm.ign.com/ign_br/image/t/the-witche/the-witcher-3s-beautiful-ugliness_zbrg.png',3),(28,5,'https://s2-techtudo.glbimg.com/G_4EgBd_epfBEXKGd4jg1fN9m0s=/0x0:1920x1080/984x0/smart/filters:strip_icc()/s.glbimg.com/po/tt2/f/original/2017/02/01/2559202-the_witcher_3_wild_hunt_geralt_ready_to_deliver_the_final_blow.png',4),(33,6,'https://t2.tudocdn.net/355735?w=824&h=494',1),(34,6,'https://rollingstone.com.br/wp-content/uploads/2024/09/red-dead-redemption-2-game-arthur-morgan-foto-reproducao-rockstar.jpg',2),(35,6,'https://www.hollywoodreporter.com/wp-content/uploads/2018/11/red_dead_redemption_2_exclusive_gang_shot_-_publicity_-_h_2018.jpg?w=2000&h=1126&crop=1',3),(36,6,'https://www.digitaltrends.com/wp-content/uploads/2018/10/red-dead-redemption-2-review-feature-header.jpg?resize=1200%2C720&p=1',4),(41,7,'https://dropsdejogos.uai.com.br/wp-content/uploads/sites/10/2022/09/reproducao-gta-v-divulgacao-scaled.jpg',1),(42,7,'https://cdn.mos.cms.futurecdn.net/xKDjdfeeVakza67tMJCDYN.jpg',2),(43,7,'https://cdn.awsli.com.br/1340/1340393/produto/2088279230e15f51310.jpg',3),(44,7,'https://media.rockstargames.com/rockstargames/img/global/news/upload/1_gtavpc_03272015.jpg',4),(49,9,'https://cdn.awsli.com.br/2500x2500/2494/2494059/produto/2146704732d01a8f6e7.jpg',1),(50,9,'https://www.numerama.com/wp-content/uploads/2022/06/img-0002.jpg',2),(51,9,'https://preview.redd.it/34nbm9o1eil21.png?width=1080&crop=smart&auto=webp&s=8833f7a6efc84cbbe5475cb2ecca2ad94637a1e7',3),(52,9,'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/367520/ss_47f3523dbea462aff2ca4bc9f605faaf80a792b2.1920x1080.jpg?t=1695270428',4),(53,10,'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/374320/ss_27397db724cfd5648655c1056ff5d184147a4c50.1920x1080.jpg?t=1748630784',1),(54,10,'https://www.notebookcheck.info/fileadmin/Notebooks/News/_nc4/dark-souls-3-remaster.jpg',2),(55,10,'https://uploads.jovemnerd.com.br/wp-content/uploads/Dark-Souls-32.jpg',3),(56,10,'https://cdn.mos.cms.futurecdn.net/J5pxCRKSNAhwbdcbGitmdT.jpg',4),(57,8,'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEhxhCzmpcPdXC_OsP6MRPiDq2L98L2o-y3i1wB8TQxja2e_oT1P3oC-Ts-V2XKu2os6dSuRcuOsN0wswMuYycETbSbvcc2DNb_0Ht0gU-Z1-Cs4RbdhgIYj-6bthHIWLHHyLuvltmyJSfk/s1600/dead-cells-screenshots-01-ps4-us-25jan2018.jpg',1),(58,8,'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEgLG4P-rB3pTTh5RMNvGbHrvNtthU3Rmi9yEeS8qpZNLj4tqej9GV0QDzmQwCAtnBYxsH6R9sxVNPqeVcTM4_W9ycOExosXErUrUiwWfix3U8oMTR5d592PJvajlMeYSTMQFah3Codkco1a34nDwjLi3KxfLL86HzplLkSU0XG1HV_cptKDR8rwvnCJHwk/s3840/Photo%20Aug%2005%202024,%2010%2056%2028%20AM%20(21).jpg',2),(59,8,'https://dnm.nflximg.net/api/v6/2DuQlx0fM4wd1nzqm5BFBi6ILa8/AAAAQZLqxzozxryfwd7nm3K52azQFpjStyZPXx5Uu0zuHgJvvRO5EKriwAopNzSMej_6jxYVME95X4Sz0bmPRAusKRJCPjKEm_4RSh0Exo38fqr2En0KXJTZDnLrmjfLi5TOhCXgAQRbv2ka9l9AEQ.jpg?r=43e',3),(60,8,'https://cdn2.unrealengine.com/dead-cells-gameplay-1920x1080-033ddf5b1b37.jpg',4),(65,11,'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/976730/ss_62bbd86f4735893ef6cd53206cf8c93f87eb86ec.1920x1080.jpg?t=1740682623',1),(66,11,'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/976730/ss_3387e0040ce6b44a715b4945f2c9ba8be634ed9b.1920x1080.jpg?t=1740682623',2),(67,11,'https://cdn.mos.cms.futurecdn.net/zLCPzgG4VBQpXEwkpCj7N3-1200-80.jpg',3),(68,11,'https://assets.vg247.com/current//2014/11/master-chief-collection-halo-2-a-3.jpg',4);
/*!40000 ALTER TABLE `game_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `game_platforms`
--

DROP TABLE IF EXISTS `game_platforms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `game_platforms` (
  `game_id` int(11) NOT NULL,
  `platform_id` int(11) NOT NULL,
  PRIMARY KEY (`game_id`,`platform_id`),
  KEY `fk_gp_platform` (`platform_id`),
  CONSTRAINT `fk_gp_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_gp_platform` FOREIGN KEY (`platform_id`) REFERENCES `platforms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `game_platforms`
--

LOCK TABLES `game_platforms` WRITE;
/*!40000 ALTER TABLE `game_platforms` DISABLE KEYS */;
INSERT INTO `game_platforms` VALUES (4,1),(4,2),(4,3),(5,1),(5,2),(5,3),(5,4),(6,1),(6,2),(6,3),(7,1),(7,2),(7,3),(8,1),(8,2),(8,3),(8,4),(9,1),(9,2),(9,3),(9,4),(10,1),(10,2),(10,3),(11,1),(11,3);
/*!40000 ALTER TABLE `game_platforms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `games`
--

DROP TABLE IF EXISTS `games`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `games` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `cover_image` varchar(500) DEFAULT NULL,
  `developer_id` int(11) DEFAULT NULL,
  `released_at` date DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_games_developer` (`developer_id`),
  CONSTRAINT `fk_games_developer` FOREIGN KEY (`developer_id`) REFERENCES `developers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `games`
--

LOCK TABLES `games` WRITE;
/*!40000 ALTER TABLE `games` DISABLE KEYS */;
INSERT INTO `games` VALUES (4,'Elden Ring','Elden Ring é um jogo eletrônico de RPG de ação em terceira pessoa, desenvolvido pela FromSoftware e publicado pela Bandai Namco Entertainment. O jogo é um projeto colaborativo entre o diretor Hidetaka Miyazaki e o romancista de fantasia George R. R. Martin.',220.00,'https://image.api.playstation.com/vulcan/ap/rnd/202110/2000/YMUoJUYNX0xWk6eTKuZLr5Iw.jpg',1,'2022-02-25',NULL,'2025-10-27 09:18:33'),(5,'The Witcher 3','The Witcher 3: Wild Hunt é um jogo eletrônico de RPG de ação em mundo aberto desenvolvido pela CD Projekt RED e lançado no dia 19 de maio de 2015 para as plataformas Microsoft Windows, PlayStation 4, Xbox One e em outubro de 2019 para o Nintendo Switch, sendo o terceiro título da série de jogos The Witcher.',120.00,'https://cdn1.epicgames.com/offer/14ee004dadc142faaaece5a6270fb628/EGS_TheWitcher3WildHuntCompleteEdition_CDPROJEKTRED_S1_2560x1440-82eb5cf8f725e329d3194920c0c0b64f',2,'2015-05-19',NULL,'2025-10-27 09:53:42'),(6,'Red Dead Redemption 2','Red Dead Redemption 2 é um jogo eletrônico de ação-aventura desenvolvido e publicado pela Rockstar Games. É o terceiro título da série Red Dead e uma prequela de Red Dead Redemption, tendo sido lançado em outubro de 2018 para PlayStation 4 e Xbox One e em novembro de 2019 para Microsoft Windows e Google Stadia.',200.00,'https://cdn1.epicgames.com/b30b6d1b4dfd4dcc93b5490be5e094e5/offer/RDR2476298253_Epic_Games_Wishlist_RDR2_2560x1440_V01-2560x1440-2a9ebe1f7ee202102555be202d5632ec.jpg',3,'2018-10-26',NULL,'2025-10-27 10:08:24'),(7,'Grand Theft Auto V','Aproveite os fenômenos do entretenimento Grand Theft Auto V e Grand Theft Auto Online melhorados para uma nova geração, com gráficos deslumbrantes, tempos de carregamento mais rápidos, áudio 3D e mais, além de conteúdo exclusivo para jogadores do GTA Online.',100.00,'https://cdn1.epicgames.com/offer/b0cd075465c44f87be3b505ac04a2e46/EGS_GrandTheftAutoVEnhanced_RockstarNorth_S1_2560x1440-906d8ae76a91aafc60b1a54c23fab496',3,NULL,NULL,'2025-10-27 10:15:18'),(8,'Dead Cells','Dead Cells é um jogo eletrônico roguelike-metroidvania desenvolvido e publicado pela Motion Twin. Após cerca de um ano de acesso antecipado, Dead Cells foi lançado para Microsoft Windows, macOS, Linux, Nintendo Switch, PlayStation 4 e Xbox One em 7 de agosto de 2018',40.00,'https://cdn1.epicgames.com/1368a7f14c3344bbaededbae528fafed/offer/EGS_DeadCells_MotionTwin_S1-2560x1440-87045359c3856ef941959aeeb00dbc7f.jpg',4,NULL,NULL,'2025-10-27 10:25:09'),(9,'Hollow Knight','Hollow Knight é um jogo indie de gênero metroidvania desenvolvido e publicado pela Team Cherry, lançado para Microsoft Windows, macOS e Linux em 2017 e, posteriormente, para Nintendo Switch, Playstation 4 e Xbox One em 2018.',45.00,'https://assets.nintendo.com/image/upload/q_auto/f_auto/store/software/switch/70010000003208/4643fb058642335c523910f3a7910575f56372f612f7c0c9a497aaae978d3e51',5,NULL,NULL,'2025-10-27 10:31:54'),(10,'Dark Souls III','Dark Souls III é um jogo eletrônico do género RPG de ação, terceiro título da série Dark Souls. Desenvolvido pela FromSoftware e co-realizado por Hidetaka Miyazaki, o criador da série, e publicado pela Bandai Namco Games.',190.00,'https://xboxpower-wp.s3.amazonaws.com/wp-content/uploads/2017/01/06000719/Dark-Souls-III-1078x516.jpg',1,NULL,NULL,'2025-10-27 10:37:27'),(11,'Halo: The Master Chief Collection','Halo: The Master Chief Collection é uma compilação de jogos eletrônicos de tiro em primeira pessoa da série Halo, originalmente lançada em novembro de 2014 para o Xbox One e, posteriormente, no Windows entre 2019 e 2020. Uma versão aprimorada foi lançada para o Xbox Series X|S em novembro de 2020.',120.00,'https://oyster.ignimgs.com/mediawiki/apis.ign.com/halo-master-chief-collection/d/d9/Hmccmain.png',6,NULL,NULL,'2025-10-27 10:47:32');
/*!40000 ALTER TABLE `games` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `genres`
--

DROP TABLE IF EXISTS `genres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `genres` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `genres`
--

LOCK TABLES `genres` WRITE;
/*!40000 ALTER TABLE `genres` DISABLE KEYS */;
INSERT INTO `genres` VALUES (1,'Action'),(2,'Adventure'),(6,'Indie'),(7,'Puzzle'),(3,'RPG'),(4,'Shooter'),(5,'Simulation'),(8,'Strategy');
/*!40000 ALTER TABLE `genres` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `qty` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `game_id` (`game_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,1,10,190.00,1),(2,2,10,190.00,1);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(64) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `shipping_address` text DEFAULT NULL,
  `payment_method` varchar(32) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,'20251027E5E6B5F3',1,190.00,'processing','{\"name\":\"carlos\",\"street\":\"bairro paulo prata\",\"city\":\"barretos\",\"state\":\"SP\",\"zip\":\"11111-111\",\"country\":\"Brasil\"}','pix','2025-10-27 18:58:09'),(2,'202510285EA9CA4C',2,190.00,'processing','{\"name\":\"carlos\",\"street\":\"bairro paulo prata\",\"city\":\"barretos\",\"state\":\"SP\",\"zip\":\"11111-111\",\"country\":\"Brasil\"}','pix','2025-10-27 22:49:14');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `platforms`
--

DROP TABLE IF EXISTS `platforms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `platforms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `platforms`
--

LOCK TABLES `platforms` WRITE;
/*!40000 ALTER TABLE `platforms` DISABLE KEYS */;
INSERT INTO `platforms` VALUES (4,'Nintendo'),(1,'PC'),(2,'PlayStation'),(3,'Xbox');
/*!40000 ALTER TABLE `platforms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(80) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'carlos',NULL,'$2y$10$YCf6G.26QA3c2YcyCwPDt.3rESNTR8lHZXRfG.DVdB0cyvTFicaeS','admin','2025-10-27 00:30:50'),(2,'pedro','zed@gmail.com','$2y$10$4LI1yw2xRU0x6m7xgSCAO.HAwa5eBw1A4tIUmFGLh9ULj8HMDl/WK','user','2025-10-27 00:55:49');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-10-28  3:04:59
