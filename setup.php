<?php
  include dirname(__FILE__) . DIRECTORY_SEPARATOR . 'db.hhvm';
  include dirname(__FILE__) . DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR. 'utils.hhvm';

  echo '
  Criando tabelas
  ';
  if(!tableExists($pdo, 'cachebank_webhook_logs')){
    $pdo->exec(file_get_contents('./db/cachebank_webhook_logs.sql'));
  }
  if(!tableExists($pdo, 'cachebank_invoices')){
    $pdo->exec(file_get_contents('./db/mkradius.cachebank_invoices.sql'));
  }
  if(!tableExists($pdo, 'cachebank_config')){
    $pdo->exec(file_get_contents('./db/mkradius.cachebank_config.sql'));
  }

  // Check if Row Config exists
  $sql = 'SELECT * from cachebank_config LIMIT 1';
  $stmt = $pdo->prepare($sql);
  $stmt->execute();

  if(!$stmt->fetchColumn()){
        $logQuery = "INSERT INTO cachebank_config (client_id, client_secret, webhook_url) VALUES ('client_id','client_secret','')";
        $stmt = $pdo->prepare($logQuery);

        if (!$stmt->execute()) {
            throw new Exception("Erro ao executar declaração SQL para inserir em sis_log: " . $stmt->error);
        }
  }

  echo '
  Modificando rotas
  ';
  //  $htaccessFileBoleto=dirname(__FILE__) . DIRECTORY_SEPARATOR."tmptest".DIRECTORY_SEPARATOR.".htaccess";
    $htaccessFileBoleto="/opt/mk-auth/boleto/.htaccess";
    $htaccesstoAdd1='
RewriteRule ^boleto.hhvm(.*)$ /cachebank/cachebank-view-boleto.php$1 [R=301,NC]';
    $htaccesstoAdd2='RewriteRule ^carne.hhvm(.*)$ /cachebank/cachebank-view-carne.php$1 [R=301,NC]';

   
    // check if Contains Boleto Redirects
   if(!strpos(file_get_contents($htaccessFileBoleto), $htaccesstoAdd1) !== false) {
      $currentHtaccess = file_get_contents($htaccessFileBoleto);
      // Append a new person to the file
      $currentHtaccess .=$htaccesstoAdd1;
      $currentHtaccess .= "\n";
      // Write the contents back to the file
      file_put_contents($htaccessFileBoleto, $currentHtaccess);
    }

     // check if Contains Carne Redirects
   if(!strpos(file_get_contents($htaccessFileBoleto), $htaccesstoAdd2) !== false) {
      $currentHtaccess = file_get_contents($htaccessFileBoleto);
      $currentHtaccess .=$htaccesstoAdd2;
      $currentHtaccess .= "\n";
      file_put_contents($htaccessFileBoleto, $currentHtaccess);
    }


  // Adicionar Rotas ADMIN
    $addonRoutes="/opt/mk-auth/admin/addons/addon.js";

    $textRoute1='
const addon_url_cachebank = window.location.protocol + "//" + window.location.hostname + (window.location.port ? ';
    $textRoute1 = $textRoute1. " ':' + window.location.port: '') ";
    $textRoute1 = $textRoute1. ' + "/admin/addons/cachebank/page";';

      // check if Contains Const Addon URL
    if(!strpos(file_get_contents($addonRoutes), $textRoute1) !== false) {
        $current = file_get_contents($addonRoutes);
        $current .=$textRoute1;
        $current .= "\n";
        file_put_contents($addonRoutes, $current);
    }

    $textRoute2 = '
add_menu.provedor(';
    $textRoute2  = $textRoute2. "'{";
    $textRoute2  = $textRoute2. '"plink": "';
    $textRoute2  = $textRoute2.  "' + addon_url_cachebank + '/index.php";
    $textRoute2  = $textRoute2. '", "ptext": "Cachê Bank"}';
    $textRoute2  = $textRoute2. "   ');";

      // check if Contains Const Addon URL
    if(!strpos(file_get_contents($addonRoutes), $textRoute2) !== false) {
        $current = file_get_contents($addonRoutes);
        $current .=$textRoute2;
        $current .= "\n";
        file_put_contents($addonRoutes, $current);
    }


    // Cria pasta na raiz webserver
    shell_exec('mkdir -p /var/www/cachebank');

    // Move arquivos WEB essenciais
    $dirEssential=dirname(__FILE__) . DIRECTORY_SEPARATOR.'external';
    $moveCMD='
    cp -rf '.$dirEssential.'/* /var/www/cachebank/;
    ';
    echo $moveCMD;
    shell_exec($moveCMD);


  

?>