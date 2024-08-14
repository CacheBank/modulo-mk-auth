<?php
require dirname(__FILE__) . DIRECTORY_SEPARATOR .'includes'.DIRECTORY_SEPARATOR.  'gerar_boleto.php';

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

if(date('D') == 'Sun') { 
     echo '
Checando e realizando atualizando do módulo
';
    include dirname(__FILE__) . DIRECTORY_SEPARATOR . 'update.php';
}

?>