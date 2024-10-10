-- Database: mkradius

/*!40030 SET NAMES utf8 */;
/*!40030 SET GLOBAL max_allowed_packet=16777216 */;

/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;

CREATE TABLE `cachebank_webhook_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_id` varchar(255) DEFAULT NULL,
  `notification_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `idtransaction` varchar(255) DEFAULT NULL,
  `client_id` varchar(255) DEFAULT NULL,
  `sync` tinyint(1)	 DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;

/*!40000 ALTER TABLE `cachebank_webhook_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `cachebank_webhook_logs` ENABLE KEYS */;

/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;

