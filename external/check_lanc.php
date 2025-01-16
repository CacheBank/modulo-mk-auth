<?php
 include '/opt/mk-auth/admin/addons/cachebank/db.hhvm';
 include '/opt/mk-auth/admin/addons/cachebank/includes/utils.hhvm';
 include '/opt/mk-auth/admin/addons/cachebank/includes/client_v2_api.php';

// Definir cabeçalhos de resposta para JSON
header('Content-Type: application/json');

  // Rota para conciliar cachebank x mk-auth
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $config = getConfig($pdo);
  
    $input = (string) file_get_contents('php://input');
    $data = json_decode($input, true);

        // Verificar se os dados são válidos
        $client_id = isset($data['client_id']) ? trim($data['client_id']): '';
        $client_secret = isset($data['client_secret']) ? trim($data['client_secret']): '';

        $lancId = isset($data['lanc_id']) ? trim($data['lanc_id']): '';

        $local_client_id=trim($config->client_id);
        $local_client_secret=trim($config->client_secret);

        if(!$client_id || $client_id==""){
            log_message("Client ID diferente do recebido" );
            return 'Client ID diferente do recebido';
        }
        else if($local_client_id!=$client_id){
            echo 'not found';
            return 'not found' ;
        }else if($local_client_secret!=$client_secret){
            echo 'not found';
            return 'not found';
        }
       
        $aberto_sql2 = "SELECT id, datapag, nossonum, recibo, valorpag, `login`, datapag, coletor,`status`, formapag,num_recibos,referencia,datavenc,deltitulo from sis_lanc  left join cachebank_invoices cinvoices on cinvoices.id_lanc=sis_lanc.id WHERE sis_lanc.id = ".$lancId." ";
        $aberto_result2 = $conn->query($aberto_sql2);
        while ($fatura = $aberto_result2->fetch_assoc()) {
            $json_data = json_encode($fatura);

            echo $json_data;
    
            return $json_data;
        }

}

$pdo=null;
?>

