<?php

$targetFileName='/opt/mk-auth/admin/addons/cachebank/.target';
$target="master";
if(is_file($targetFileName)){
    $target=file_get_contents($targetFileName)!=""?trim(file_get_contents($targetFileName)):"master";
}

$targetName=$target.".zip";

echo '
Baixando atualizações';

exec('rm  /opt/mk-auth/admin/addons/cachebank/mk-auth.zip;');
exec("wget  --no-check-certificate --no-cache --no-cookies --no-http-keep-alive https://github.com/CacheBank/modulo-mk-auth/archive/refs/heads/$targetName -O /opt/mk-auth/admin/addons/cachebank/mk-auth.zip");

    
echo '
Extraindo Dados';
exec('unzip  /opt/mk-auth/admin/addons/cachebank/mk-auth.zip -d /opt/mk-auth/admin/addons/cachebank;');


exec('touch /tmp/cachebank_log.txt');
exec('chown www-data:www-data /tmp/cachebank_log.txt');
exec('chmod 777 /tmp/cachebank_log.txt');

echo '
Copiando arquivos';
exec('cp -rf /opt/mk-auth/admin/addons/cachebank/modulo-mk-auth-'.$target.'/* /opt/mk-auth/admin/addons/cachebank/');

echo '
Remover arquivos desnecessários';
exec('rm -rf /opt/mk-auth/admin/addons/cachebank/modulo-mk-auth-'.$target.'/');

include dirname(__FILE__) . DIRECTORY_SEPARATOR .'setup.php';

?>