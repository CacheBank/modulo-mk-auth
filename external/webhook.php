<?php
 include '/opt/mk-auth/admin/addons/cachebank/db.hhvm';
 include '/opt/mk-auth/admin/addons/cachebank/includes/utils.hhvm';
 include '/opt/mk-auth/admin/addons/cachebank/includes/client_v2_api.php';

// Definir cabeçalhos de resposta para JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $config = getConfig($pdo);

    // Obter o conteúdo da solicitação
    $input = (string) file_get_contents('php://input');
    log_message("Payload recebido: " . $input);

    
    $data = json_decode($input, true);


        // Verificar se os dados são válidos
        $notification_id = isset($data['notification_id']) ? $data['notification_id'] : '';
        $idtransaction = isset($data['idtransaction']) ? $data['idtransaction'] : '';
        $client_id = isset($data['client_id']) ? trim($data['client_id']): '';
        $local_client_id=trim($config->client_id);
        if($local_client_id!=$client_id){
            log_message("Client ID diferente do recebido: " );
            return ;
        }
        // Checar se webhook log já existe
        $query = "SELECT wslog.notification_id,wslog.id
                    FROM cachebank_webhook_logs wslog 
                    WHERE wslog.notification_id = :notification_id";
        $stmt = $pdo->prepare($query);
        if (!$stmt) {
            throw new Exception("Erro ao preparar declaração SQL para selecionar de pix_info: " . $pdo->error);
        }
        $stmt->bindParam("notification_id", $notification_id,  PDO::PARAM_STR);
        $stmt->execute();
        $resDb=$stmt->fetch(PDO::FETCH_ASSOC);
        if(isset($resDb["id"])){
            $last_id=$resDb["id"];
        }else{
            // Inserir o payload completo na tabela de logs do webhook
            $stmt = $pdo->prepare("INSERT INTO cachebank_webhook_logs (notification_id, idtransaction,client_id) VALUES (:notification_id, :idtransaction, :client_id )");
            if (!$stmt) {
                throw new Exception("Erro ao preparar declaração SQL para inserir em cachebank_webhook_logs: " . $pdo->error);
            }
            $stmt->bindParam(':notification_id', $notification_id, PDO::PARAM_STR);
            $stmt->bindParam(':idtransaction', $idtransaction, PDO::PARAM_STR);
            $stmt->bindParam(':client_id', $client_id, PDO::PARAM_STR);

            if (!$stmt->execute()) {
                throw new Exception("Erro ao executar declaração SQL para inserir em cachebank_webhook_logs: " . $stmt->error);
            }
               // Obter o ID do registro inserido
            $last_id = $pdo->lastInsertId();
            log_message("Inserção bem-sucedida em cachebank_webhook_logs, ID: " . $last_id);

        }
     

        // Comparar txid com pix_info e atualizar sis_lanc
                $query = "SELECT cinvoices.idtransaction as idtransaction, cinvoices.id_cliente as id_cliente, cinvoices.id_lanc as id_lanc, sis_cliente.login as login_cliente 
                          FROM cachebank_invoices cinvoices
                          JOIN cachebank_webhook_logs wslog ON wslog.idtransaction = cinvoices.idtransaction
                          JOIN sis_cliente sis_cliente ON sis_cliente.id = cinvoices.id_cliente 
                          WHERE wslog.id = :wslogId order by wslog.notification_date";
                $stmt = $pdo->prepare($query);
                if (!$stmt) {
                    throw new Exception("Erro ao preparar declaração SQL para selecionar de pix_info: " . $pdo->error);
                }
                $stmt->bindParam("wslogId", $last_id,  PDO::PARAM_INT);
                $stmt->execute();
                $resDb=$stmt->fetch(PDO::FETCH_ASSOC);

                $idtransaction=$resDb["idtransaction"];
                $id_cliente=$resDb["id_cliente"];
                $id_lanc=$resDb["id_lanc"];
                $login_cliente=$resDb["login_cliente"];


        log_message("Consultando dados externos da transação WebHookId: " . $last_id);
        $paymentRes=obterDadosWebHookBoleto($pdo, $notification_id, $idtransaction);
        $amountPaid=$paymentRes["status"]===7?$paymentRes["valortotal"]:$paymentRes["valorpago"];
        $statusName=getStatusPaymentName($paymentRes["status"]);

        log_message("Atribuindo valores ao lançamento " . $last_id);

        // Atualizar invoiceLogs
        $updateQuery = "UPDATE cachebank_invoices 
            SET linha_digitavel = :linha_digitavel, 
                nosso_numero = :nosso_numero, 
                codigo_barra = :codigo_barra,
                updated_at = NOW(),
                status = :status, 
                txid = :txid,
                pix_copia_cola = :pix_copia_cola, 

                amount_paid = :amount_paid,
                payment_date = :payment_date 
            WHERE id_lanc = :id_lanc and id_cliente = :id_cliente";
        $stmt = $pdo->prepare($updateQuery);
        if (!$stmt) {
             throw new Exception("Erro ao preparar declaração SQL para atualizar sis_lanc: " . $pdo->error);
        }
        $stmt->bindParam(":linha_digitavel", $paymentRes["boleto"]["linhadigitavel"], PDO::PARAM_STR);
        $stmt->bindParam(":nosso_numero", $paymentRes["boleto"]["nossonumero"], PDO::PARAM_STR);
        $stmt->bindParam(":codigo_barra", $paymentRes["boleto"]["codigobarra"], PDO::PARAM_STR);

        $stmt->bindParam(":status", $statusName, PDO::PARAM_STR);

        $stmt->bindParam(":txid", $paymentRes["pix"]["txid"], PDO::PARAM_STR);
        $stmt->bindParam(":pix_copia_cola", $paymentRes["pix"]["qrcode"], PDO::PARAM_STR);

        $stmt->bindParam(":payment_date", $paymentRes["datapagamento"], PDO::PARAM_STR);

        $stmt->bindParam(":amount_paid", $amountPaid, PDO::PARAM_STR);
     


        $stmt->bindParam(":id_lanc", $id_lanc, PDO::PARAM_INT);
        $stmt->bindParam(":id_cliente", $id_cliente, PDO::PARAM_INT);
        
        if (!$stmt->execute()) {
            throw new Exception("Erro ao executar declaração SQL para atualizar sis_lanc: " . $stmt->error);
        }

        log_message("Aualizando lançamento " . $id_lanc);

        // Atualizar sis_lanc
        $updateQuery = "UPDATE sis_lanc SET formapag = 'dinheiro', status = :status, num_recibos = 1, datapag = :datapag, coletor = 'notificacao', valorpag = :valorpag WHERE id = :sis_lanc_id and login= :login";
        $stmt = $pdo->prepare($updateQuery);
        if (!$stmt) {
             throw new Exception("Erro ao preparar declaração SQL para atualizar sis_lanc: " . $pdo->error);
        }
        $datapagamento=$paymentRes["datapagamento"];
        $stmt->bindParam(":status", $statusName, PDO::PARAM_STR);
        $stmt->bindParam(":datapag", $datapagamento,  PDO::PARAM_STR);
        $stmt->bindParam(":valorpag", $amountPaid,  PDO::PARAM_STR);
        $stmt->bindParam(":sis_lanc_id", $id_lanc,  PDO::PARAM_INT);
        $stmt->bindParam(":login", $login_cliente,  PDO::PARAM_STR);

        if (!$stmt->execute()) {
            throw new Exception("Erro ao executar declaração SQL para atualizar sis_lanc: " . $stmt->error);
        }

        // Fim lançamento Financeiro


        log_message("Gerando log de pagamento " . $id_lanc);
         // Inserir log em sis_log
        $logMessage = "Atualização do titulo " . $id_lanc . " por cachebank - IP:127.0.0.1";
        $logQuery = "INSERT INTO sis_logs (registro, data, login, tipo, operacao,id) VALUES (:registro, NOW(), 'mk-bot', 'admin', 'OPERFALL',default)";
        $stmt = $pdo->prepare($logQuery);
        if (!$stmt) {
            throw new Exception("Erro ao preparar declaração SQL para inserir em sis_log: " . $pdo->error);
        }
        $stmt->bindParam(":registro", $logMessage,  PDO::PARAM_STR);

        if (!$stmt->execute()) {
            throw new Exception("Erro ao executar declaração SQL para inserir em sis_log: " . $stmt->error);
        }

        // Atualiza webhook como sincronizado
        
        $updateQuery = "UPDATE cachebank_webhook_logs 
            SET sync = 1
            WHERE notification_id = :notification_id";
        $stmt = $pdo->prepare($updateQuery);
        if (!$stmt) {
             throw new Exception("Erro ao preparar declaração SQL para atualizar sis_lanc: " . $pdo->error);
        }
        $stmt->bindParam(":notification_id", $notification_id, PDO::PARAM_STR);
        
        if (!$stmt->execute()) {
            throw new Exception("Erro ao executar declaração SQL para atualizar sis_lanc: " . $stmt->error);
        }

        echo "Executado";


}

$pdo=null;
?>

