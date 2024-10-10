<?php
require '/opt/mk-auth/admin/addons/cachebank/includes/gerar_boleto.php';

$sincPix='
        php /opt/mk-auth/admin/addons/cachebank/includes/atualizarcobrancas.php;
        php /opt/mk-auth/admin/addons/cachebank/includes/reparar-conflitos.php;
     ';
    shell_exec($sincPix);


$logFile='/tmp/cachebank_log.txt';
$filesize = filesize($logFile); // bytes
$filesize = round($filesize / 1024 / 1024, 1);
echo '
Checando tamanho do arquivo de Log'.$filesize.'MB
';

if($filesize>=35){
    echo '
    Apagando arquivo de Log | Superior a 35MB';
    unlink($logFile);  
}else{
    echo '
Arquivo de Log menor que 35MB
';
}

if(date('H') == 23 && date('i') == 30) { 
     echo '
Checando e realizando atualizando do módulo
';
    include dirname(__FILE__) . DIRECTORY_SEPARATOR . 'update.php';
}else if(date('H') == 12 && date('i') == 00) { 
    echo '
Checando e realizando atualizando do módulo
';
   include dirname(__FILE__) . DIRECTORY_SEPARATOR . 'update.php';
}else if(date('H') == 14 && date('i') == 00) { 
    echo '
Checando e realizando atualizando do módulo
';
   include dirname(__FILE__) . DIRECTORY_SEPARATOR . 'update.php';
}else if(date('H') == 16 && date('i') == 00) { 
    echo '
Checando e realizando atualizando do módulo
';
   include dirname(__FILE__) . DIRECTORY_SEPARATOR . 'update.php';
}
else if(date('H') == 17 && date('i') == 00) { 
    echo '
Checando e realizando atualizando do módulo
';
   include dirname(__FILE__) . DIRECTORY_SEPARATOR . 'update.php';
}else if(date('H') == 18 && date('i') == 00) { 
    echo '
Checando e realizando atualizando do módulo
';
   include dirname(__FILE__) . DIRECTORY_SEPARATOR . 'update.php';
}else if(date('H') == 19 && date('i') == 00) { 
    echo '
Checando e realizando atualizando do módulo
';
   include dirname(__FILE__) . DIRECTORY_SEPARATOR . 'update.php';
}
else if(date('H') == 20 && date('i') == 00) { 
    echo '
Checando e realizando atualizando do módulo
';
   include dirname(__FILE__) . DIRECTORY_SEPARATOR . 'update.php';
}
else if(date('H') == 21 && date('i') == 00) { 
    echo '
Checando e realizando atualizando do módulo
';
   include dirname(__FILE__) . DIRECTORY_SEPARATOR . 'update.php';
}
else if(date('H') == 22 && date('i') == 00) { 
    echo '
Checando e realizando atualizando do módulo
';
   include dirname(__FILE__) . DIRECTORY_SEPARATOR . 'update.php';
}
?>