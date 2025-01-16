<?php
    require_once '/opt/mk-auth/admin/addons/cachebank/db.hhvm';
    require_once '/opt/mk-auth/admin/addons/cachebank/includes/utils.hhvm';
    require_once '/opt/mk-auth/admin/addons/cachebank/includes/client_v2_api.php';

    $config = getConfig($pdo);
    log_message("
    Iniciando sincronização das cobranças canceladas e excluidas do MK-AUTH");


    $listLastUpBill = "SELECT 
            DISTINCT(sis_lanc.uuid_lanc) as uuid_lanc, 
            sis_lanc.id as sis_lanc_id,
            ch.pix_copia_cola,
            ch.idtransaction,
            ch.linha_digitavel,
            ch.nosso_numero,
            ch.codigo_barra,
            ch.status,
            ch.amount_paid,
            ch.payment_date,
            sis_lanc.deltitulo
        FROM `cachebank_invoices` ch 
        inner join sis_lanc sis_lanc on sis_lanc.id=ch.id_lanc
        where ch.updated_at>=SUBDATE(CURRENT_DATE, INTERVAL 1 Hour)"; 
    $aberto_result = $conn->query($listLastUpBill);

    while ($fatura = $aberto_result->fetch_assoc()) {
        
        // Sincronizar Pagamentos Recebidos
        syncPay($pdo, $conn, $fatura);

        if($fatura["deltitulo"]==1){
            syncCancelBillDeleted($pdo, $fatura);
        }
        
        if(tableExists($pdo, 'sis_qrpix')){
            // Sincronizar QrCodePix
            syncPix($pdo, $conn, $fatura);
        }

        $stmt=null;

    }
   
    function syncCancelBillDeleted($pdo, $fatura){
        $idtransaction=$fatura["idtransaction"];
        log_message("
        Cancelando cobrança excluida do Mk-Auth");

        try{
            $paymentRes=cancelBill($pdo, $idtransaction);

        }catch(Exception $ex){
            log_message("
            Erro ao cancelar cobrança excluida do Mk-Auth");
        }
    }

    function syncPay($pdo, $conn, $fatura){
        $statusName=$fatura["status"];
        $payment_date=$fatura["payment_date"];
        $amount_paid=$fatura["amount_paid"];
        $id_lanc=$fatura["sis_lanc_id"];

        log_message("
        Sincronizando Pagamentos ID ".$id_lanc);

        try{
            // Atualizar sis_lanc
            $updateQuery = "UPDATE sis_lanc 
                SET 
                    formapag = 'dinheiro', 
                    status = :status, 
                    num_recibos = 1, 
                    datapag = :datapag, 
                    coletor = 'notificacao', 
                    valorpag = :valorpag 
                WHERE id = :sis_lanc_id;";
            $stmt = $pdo->prepare($updateQuery);
            if (!$stmt) {
                throw new Exception("Erro ao preparar declaração SQL para atualizar sis_lanc: " . $conn->error);
            }
            $stmt->bindParam(":status", $statusName, PDO::PARAM_STR);
            $stmt->bindParam(":datapag", $payment_date,  PDO::PARAM_STR);
            $stmt->bindParam(":valorpag", $amount_paid,  PDO::PARAM_STR);
            $stmt->bindParam(":sis_lanc_id", $id_lanc,  PDO::PARAM_INT);

            if (!$stmt->execute()) {
                throw new Exception("Erro ao executar declaração SQL para atualizar sis_lanc: " . $conn->error);
            }
        }catch(Exception $ex){
            log_message("syncPay | Erro ao sincronizar os qrcode pix");
        }
        
    }
    function syncPix($pdo, $conn, $fatura){
        try{
            $uuid_lanc=$fatura['uuid_lanc'];
            $pix_copia_cola=$fatura['pix_copia_cola'];

            $stmt = $pdo->prepare("INSERT IGNORE  INTO sis_qrpix ( titulo , qrcode ) VALUES ( :titulo, :qrcode )");

            $stmt->bindParam(':titulo', $uuid_lanc, PDO::PARAM_STR);
            $stmt->bindParam(':qrcode', $pix_copia_cola, PDO::PARAM_STR);

            if ($stmt->execute() === FALSE) {
                log_message("Falha na atualização do boleto  ".$conn->error);
            }
        }catch(Exception $ex){
            log_message("syncPix | Erro ao sincronizar os qrcode pix");
        }
    }
 

    // $listBillDuplicated = "SELECT 
    //         DISTINCT(sis_lanc.uuid_lanc) as uuid_lanc, 
    //         sis_lanc.id as sis_lanc_id,
    //         ch.pix_copia_cola,
    //         ch.idtransaction,
    //         ch.linha_digitavel,
    //         ch.nosso_numero,
    //         ch.codigo_barra,
    //         ch.status,
    //         ch.amount_paid,
    //         ch.payment_date,
    //         sis_lanc.deltitulo
    //     FROM `cachebank_invoices` ch 
    //     left join sis_lanc sis_lanc on sis_lanc.id=ch.id_lanc
    //     inner join sis_cliente on sis_cliente.login=sis_lanc.login
    //     inner join sis_boleto on sis_boleto.id=sis_cliente.conta
    //     where ch.updated_at>=SUBDATE(CURRENT_DATE, INTERVAL 1 Hour)
    //     AND (
    //             LOWER(trim(sis_boleto.nome))='cachebank'
    //             or 
    //             LOWER(trim(sis_boleto.nome))='cachêbank'
    //         )
    //     "; 
    // $aberto_result = $conn->query($listBillDuplicated);

    // while ($fatura = $aberto_result->fetch_assoc()) {
        
    //     try{
    //         syncCancelBillDeleted($pdo, $fatura);
    //     }catch(Exception $ex){
    //     }

    //     $stmt=null;

    // }

    $conn->close();
?>