-- Database: mkradius

/*!40030 SET NAMES utf8 */;
/*!40030 SET GLOBAL max_allowed_packet=16777216 */;

/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;

CREATE TABLE `cachebank_config` (
  `client_id` varchar(200),
  `client_secret` varchar(200),
  `webhook_url` varchar(300)
) ENGINE=InnoDB CHARSET=utf8;


/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;

