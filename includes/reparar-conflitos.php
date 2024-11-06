<?php
    require_once '/opt/mk-auth/admin/addons/cachebank/db.hhvm';
    require_once '/opt/mk-auth/admin/addons/cachebank/includes/utils.hhvm';
    require_once '/opt/mk-auth/admin/addons/cachebank/includes/client_v2_api.php';

    $config = getConfig($pdo);

    syncBill($pdo, $conn);
    syncCaixa($pdo, $conn);

    function syncCaixa($pdo, $conn, $force=false){
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

        

    function syncBill($pdo, $conn){
      
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
            sis_lanc.login
        FROM `cachebank_invoices` ch 
        inner join sis_lanc sis_lanc on sis_lanc.id=ch.id_lanc
        where 
        ch.linha_digitavel <> sis_lanc.linhadig
        OR
        ch.nosso_numero <> sis_lanc.nossonum
        or 
        ch.status <> sis_lanc.status
        or
        ch.payment_date <> sis_lanc.datapag
        or gerourem <> 1;
        "; 
        $aberto_result = $conn->query($aberto_sql);

        while ($fatura = $aberto_result->fetch_assoc()) {
            $linha_digitavel=$fatura["linha_digitavel"];
            $nosso_numero=$fatura["nosso_numero"];
            $status=$fatura["status"];
            $payment_date=$fatura["payment_date"];
            $id_lanc=$fatura["sis_lanc_id"];
            $valorpag=$fatura["amount_paid"];
            $login=$fatura["login"];
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
                    WHERE id = :sis_lanc_id and login=:login";
                $stmt = $pdo->prepare($updateQuery);
                if (!$stmt) {
                    throw new Exception("Erro ao preparar declaração SQL para atualizar sis_lanc: " . $conn->error);
                }
                $stmt->bindParam(":payment_date", $payment_date, PDO::PARAM_STR);
                $stmt->bindParam(":nosso_numero", $nosso_numero,  PDO::PARAM_STR);
                $stmt->bindParam(":status", $status,  PDO::PARAM_STR);
                $stmt->bindParam(":linha_digitavel", $linha_digitavel,  PDO::PARAM_STR);
                $stmt->bindParam(":valorpag", $valorpag,  PDO::PARAM_STR);
                $stmt->bindParam(":login", $login,  PDO::PARAM_STR);
                
                $stmt->bindParam(":sis_lanc_id", $id_lanc,  PDO::PARAM_INT);
    
                if (!$stmt->execute()) {
                    throw new Exception("Erro ao executar declaração SQL para atualizar sis_lanc: " . $pdo->error);
                }
            }catch(Exception $ex){
                log_message("Erro ao sincronizar os qrcode pix".$ex->getMessage());
            }


        }
        
    }

    $conn->close();
?>