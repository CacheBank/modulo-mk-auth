<?php

$update= exec(' php /opt/mk-auth/admin/addons/cachebank/update.php;');
var_dump($update);

$cronjob= exec(' php /opt/mk-auth/admin/addons/cachebank/cronjob.php');
var_dump($cronjob);
