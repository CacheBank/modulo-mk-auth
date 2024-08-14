<?php

shell_exec('
rm  /opt/mk-auth/admin/addons/cachebank/mk-auth.zip;
mkdir -p /opt/mk-auth/admin/addons/cachebank/;

');

shell_exec('wget  --no-check-certificate --no-cache --no-cookies --no-http-keep-alive https://github.com/CacheBank/modulo-mk-auth/archive/refs/heads/master.zip -O /opt/mk-auth/admin/addons/cachebank/mk-auth.zip');

echo '
Extraindo Dados';
shell_exec('yes | unzip  /opt/mk-auth/admin/addons/cachebank/mk-auth.zip -d /opt/mk-auth/admin/addons/cachebank;');

echo '
Copiando arquivos';
shell_exec('cp -rf /opt/mk-auth/admin/addons/cachebank/modulo-mk-auth-master/* /opt/mk-auth/admin/addons/cachebank/');

echo '
Remover arquivos desnecessários';
shell_exec('rm -rf /opt/mk-auth/admin/addons/cachebank/modulo-mk-auth-master/');


echo '
Configurando Cron
';
shell_exec('echo "* * * * * php /opt/mk-auth/admin/addons/cachebank/cronjob.php " |crontab -');

include '/opt/mk-auth/admin/addons/cachebank/update.php';