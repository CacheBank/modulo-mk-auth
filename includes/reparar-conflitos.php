<?php
    require_once '/opt/mk-auth/admin/addons/cachebank/db.hhvm';
    require_once '/opt/mk-auth/admin/addons/cachebank/includes/utils.hhvm';
    require_once '/opt/mk-auth/admin/addons/cachebank/includes/client_v2_api.php';
    $minutoAtual=date('i');

    $config = getConfig($pdo);

    syncBill($pdo, $conn);
    syncCaixaPayment($pdo, $conn, true);
    syncCaixaFeesPayment($pdo, $conn, true);

    if(in_array($minutoAtual, [15,30,45,60])) { 
        syncInternalInvoices($pdo, $conn, 'pago');
    }

    function syncCaixaPayment($pdo, $conn, $force=false){
        if($force){
            $aberto_sql = "SELECT 
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
                sis_lanc.deltitulo,
                sis_lanc.login ,
                sis_cliente.nome ,
                concat('Recebimento do titulo ',sis_lanc.id,' / ',sis_lanc.login) as descricaoCaixa
            FROM `cachebank_invoices` ch 
            inner join sis_lanc sis_lanc on sis_lanc.id=ch.id_lanc
            JOIN sis_cliente sis_cliente ON sis_cliente.login = sis_lanc.login
            where not exists (
                select 1 from sis_caixa sis_caixa WHERE sis_caixa.historico = concat('Recebimento do titulo ',sis_lanc.id,' / ',sis_lanc.login)
            )
            and ch.amount_paid>0
            "; 
        }else {
            $aberto_sql = "SELECT 
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
                sis_lanc.deltitulo,
                sis_lanc.login ,
                sis_cliente.nome ,
                concat('Recebimento do titulo ',sis_lanc.id,' / ',sis_lanc.login) as descricaoCaixa
            FROM `cachebank_invoices` ch 
            inner join sis_lanc sis_lanc on sis_lanc.id=ch.id_lanc
            JOIN sis_cliente sis_cliente ON sis_cliente.login = sis_lanc.login
            where 
                ch.updated_at>=SUBDATE(CURRENT_DATE, INTERVAL 1 Hour)
                and not exists (
                    select 1 from sis_caixa sis_caixa WHERE sis_caixa.historico = concat('Recebimento do titulo ',sis_lanc.id,' / ',sis_lanc.login)
                )
                and ch.amount_paid>0"; 
        }
        
            $aberto_result = $conn->query($aberto_sql);
    
            while ($fatura = $aberto_result->fetch_assoc()) {
                $uuid_caixa=uniqid();
                $data=$fatura["payment_date"];
                $historico=$fatura["descricaoCaixa"];
                $entrada=$fatura["amount_paid"];
                // Atualizar qrcode
                $updateQuery = "INSERT INTO sis_caixa ( uuid_caixa, usuario,data ,historico,entrada,tipomov ,planodecontas ) VALUES ( :uuid_caixa, 'cachebank', :data, :historico, :entrada, 'tipomov', 'Outros'   )";
                $stmt = $pdo->prepare($updateQuery);
                if (!$stmt) {
                    throw new Exception("Erro ao preparar declaração SQL para atualizar sis_lanc: " . $conn->error);
                }
                $stmt->bindParam(":uuid_caixa", $uuid_caixa, PDO::PARAM_STR);
                $stmt->bindParam(":data", $data, PDO::PARAM_STR);
                $stmt->bindParam(":historico", $historico, PDO::PARAM_STR);
                $stmt->bindParam(":entrada", $entrada, PDO::PARAM_STR);

                if (!$stmt->execute()) {
                    throw new Exception("Erro ao executar declaração SQL para atualizar sis_caixa: " . $conn->error);
                }
    
    
            }
            
    }

    function syncCaixaFeesPayment($pdo, $conn, $force=false){
        if($force){
            $aberto_sql = "SELECT 
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
                ch.amount_fees,
                sis_lanc.deltitulo,
                sis_lanc.login ,
                sis_cliente.nome ,
                concat('Tarifa do titulo ',sis_lanc.id,' / ',sis_lanc.login) as descricaoCaixa
            FROM `cachebank_invoices` ch 
            inner join sis_lanc sis_lanc on sis_lanc.id=ch.id_lanc
            JOIN sis_cliente sis_cliente ON sis_cliente.login = sis_lanc.login
            where not exists (
                select 1 from sis_caixa sis_caixa WHERE sis_caixa.historico = concat('Tarifa do titulo ',sis_lanc.id,' / ',sis_lanc.login)
            )
            and ch.amount_paid>0
            "; 
        }else {
            $aberto_sql = "SELECT 
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
                ch.amount_fees,
                sis_lanc.deltitulo,
                sis_lanc.login ,
                sis_cliente.nome ,
                concat('Tarifa do titulo ',sis_lanc.id,' / ',sis_lanc.login) as descricaoCaixa
            FROM `cachebank_invoices` ch 
            inner join sis_lanc sis_lanc on sis_lanc.id=ch.id_lanc
            JOIN sis_cliente sis_cliente ON sis_cliente.login = sis_lanc.login
            where 
                ch.updated_at>=SUBDATE(CURRENT_DATE, INTERVAL 1 Hour)
                and not exists (
                    select 1 from sis_caixa sis_caixa WHERE sis_caixa.historico = concat('Tarifa do titulo ',sis_lanc.id,' / ',sis_lanc.login)
                )
                and ch.amount_paid>0"; 
        }
        
            $aberto_result = $conn->query($aberto_sql);
    
            while ($fatura = $aberto_result->fetch_assoc()) {
                $uuid_caixa=uniqid();
                $data=$fatura["payment_date"];
                $historico=$fatura["descricaoCaixa"];
                $saida=$fatura["amount_fees"];
                // Atualizar qrcode
                $updateQuery = "INSERT INTO sis_caixa ( uuid_caixa, usuario,data ,historico,saida,tipomov ,planodecontas ) VALUES ( :uuid_caixa, 'cachebank', :data, :historico, :saida, 'saida', 'Outros'   )";
                $stmt = $pdo->prepare($updateQuery);
                if (!$stmt) {
                    throw new Exception("Erro ao preparar declaração SQL para atualizar sis_lanc: " . $conn->error);
                }
                $stmt->bindParam(":uuid_caixa", $uuid_caixa, PDO::PARAM_STR);
                $stmt->bindParam(":data", $data, PDO::PARAM_STR);
                $stmt->bindParam(":historico", $historico, PDO::PARAM_STR);
                $stmt->bindParam(":saida", $saida, PDO::PARAM_STR);

                if (!$stmt->execute()) {
                    throw new Exception("Erro ao executar declaração SQL para atualizar sis_caixa: " . $conn->error);
                }
    
            }
            
    }
        

    function syncBill($pdo, $conn){
        log_message("
       syncBill");
    $aberto_sql = "SELECT 
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
            sis_lanc.deltitulo,
            sis_lanc.login,
               ch.linha_digitavel ,sis_lanc.linhadig
        FROM `cachebank_invoices` ch 
        inner join sis_lanc sis_lanc on sis_lanc.id=ch.id_lanc
         where 
        (
            ch.linha_digitavel is not null
            and (sis_lanc.linhadig is null or sis_lanc.linhadig <>  ch.linha_digitavel)
         )
         OR
         (
            ch.nosso_numero is not null
            and (sis_lanc.nossonum is null or sis_lanc.nossonum <>  ch.nosso_numero)
         )
         
         OR
         (
            ch.status is not null
            and (sis_lanc.status is null or sis_lanc.status <>  ch.status)
         )
         OR
         (
            ch.status is not null
            and (sis_lanc.status is null or sis_lanc.status <>  ch.status)
         )
         
         OR
         (
            ch.payment_date is not null
            and (sis_lanc.datapag is null or sis_lanc.datapag <>  ch.payment_date)
         )
         
         OR
         (
            sis_lanc.gerourem is null or sis_lanc.gerourem <>  1
         );"; 
        $aberto_result = $conn->query($aberto_sql);

        while ($fatura = $aberto_result->fetch_assoc()) {
            $linha_digitavel=$fatura["linha_digitavel"];
            $nosso_numero=$fatura["nosso_numero"];
            $status=$fatura["status"];
            $payment_date=$fatura["payment_date"];
            $id_lanc=$fatura["sis_lanc_id"];
            $valorpag=$fatura["amount_paid"];
            try{
                // Atualizar sis_lanc
                $updateQuery = "UPDATE sis_lanc 
                    SET 
                        formapag = 'dinheiro', 
                        datapag = :payment_date, 
                        nossonum  = :nosso_numero, 
                        status = :status, 
                        linhadig = :linha_digitavel, 
                        valorpag = :valorpag,
                        gerourem = 1
                    WHERE id = :sis_lanc_id";
                $stmt = $pdo->prepare($updateQuery);
                if (!$stmt) {
                    throw new Exception("Erro ao preparar declaração SQL para atualizar sis_lanc: " . $conn->error);
                }
                $stmt->bindParam(":payment_date", $payment_date, PDO::PARAM_STR);
                $stmt->bindParam(":nosso_numero", $nosso_numero,  PDO::PARAM_STR);
                $stmt->bindParam(":status", $status,  PDO::PARAM_STR);
                $stmt->bindParam(":linha_digitavel", $linha_digitavel,  PDO::PARAM_STR);
                $stmt->bindParam(":valorpag", $valorpag,  PDO::PARAM_STR);
               
                $stmt->bindParam(":sis_lanc_id", $id_lanc,  PDO::PARAM_INT);
    
                if (!$stmt->execute()) {
                    throw new Exception("Erro ao executar declaração SQL para atualizar sis_lanc: " . $pdo->error);
                }
            }catch(Exception $ex){
                log_message("Erro ao sincronizar os qrcode pix".$ex->getMessage());
            }


        }
        
    }

    function syncInternalInvoices($pdo, $conn, $paymentStatus){
        if($paymentStatus=='pago'){
            echo '
       syncInternalInvoices - pago
        ';
            $aberto_sql = "SELECT * FROM `cachebank_invoices` where amount_fees is null  "; 
        }

        $aberto_result = $conn->query($aberto_sql);
    
        while ($fatura = $aberto_result->fetch_assoc()) {

            $idtransaction=$fatura["idtransaction"];

            $paymentRes=obterTransacao($pdo,  $idtransaction);
            $amountPaid=$paymentRes["status"]===7?$paymentRes["valortotal"]:$paymentRes["valorpago"];
            $statusName=getStatusPaymentName($paymentRes["status"]);
            $amount_fees=$paymentRes["custo"];

            log_message("Atribuindo valores a transacao " . $idtransaction);

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
                    amount_fees = :amount_fees,
                    payment_date = :payment_date 
                WHERE idtransaction = :idtransaction";
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
            $stmt->bindParam(":amount_fees", $amount_fees, PDO::PARAM_STR);

            $stmt->bindParam(":idtransaction", $idtransaction, PDO::PARAM_STR);
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao executar declaração SQL para atualizar sis_lanc: " . $stmt->error);
            }
        
        }
            
    }


    $conn->close();
?>