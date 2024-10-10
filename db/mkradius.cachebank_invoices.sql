-- Database: mkradius

/*!40030 SET NAMES utf8 */;
/*!40030 SET GLOBAL max_allowed_packet=16777216 */;

/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;

CREATE TABLE `cachebank_invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL,
  `id_lanc` int(11) DEFAULT NULL,
  `idtransaction` varchar(100),

  `linha_digitavel` varchar(200),
  `nosso_numero` varchar(200),
  `codigo_barra` varchar(200),

  `txid` varchar(35),
  `url_qrcode` text,
  `pix_copia_cola` text,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `status` varchar(20) NOT NULL,
  `amount_paid` 	decimal(12,2),
  `payment_date` datetime,

  PRIMARY KEY (`id`),
   UNIQUE (id_cliente,id_lanc),
  KEY `idx_id_cliente` (`id_cliente`),
  KEY `idx_id_lanc` (`id_lanc`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;


/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;

