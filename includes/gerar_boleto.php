<?php
    require '/opt/mk-auth/admin/addons/cachebank/db.hhvm';
    require '/opt/mk-auth/admin/addons/cachebank/includes/utils.hhvm';

    require '/opt/mk-auth/admin/addons/cachebank/includes/client_v2_api.php';
    echo '
        Iniciando emissão de boletos 
    ';
    $config = getConfig($pdo);
    log_message("Iniciando geração");

    if(checkLockTransaction()){
       echo 'Erro ao executar | Já existe uma execução em andamento';
        log_message("Erro ao executar | Já existe uma execução em andamento");
        return false;
    }

    lockTransaction();

    try{
        $aberto_sql = "SELECT 
            sis_lanc.id as sis_lanc_id,
            sis_lanc.uuid_lanc as sis_lanc_uuid_lanc,
            sis_lanc.datavenc as lanc_datavenc,
            sis_lanc.nossonum as lanc_nossonum,
            sis_lanc.datapag as lanc_datapag,
            sis_lanc.status as lanc_status,
            sis_lanc.valor as lanc_valor,
            sis_lanc.referencia as lanc_referencia,
            sis_lanc.tipo as lanc_tipo,

            sis_boleto.desconto as lanc_desconto,
            sis_boleto.tipo_desc as lanc_tipo_desc,
            sis_boleto.multa as lanc_multa,
            sis_boleto.juros * 30 as lanc_juros,


            sis_cliente.id as cliente_id,
            sis_cliente.nome as cliente_nome,
            sis_cliente.email as cliente_email,
            sis_cliente.cpf_cnpj as cliente_cpf_cnpj,
            coalesce(sis_cliente.celular, sis_cliente.fone)  as cliente_phone,
            sis_cliente.endereco_res as cliente_endereco_logradouro,
            sis_cliente.bairro_res as cliente_endereco_bairro,
            sis_cliente.cidade_res as cliente_endereco_cidade,
            sis_cliente.cep_res as cliente_endereco_cep,
            sis_cliente.estado_res as cliente_endereco_estado,
            sis_cliente.complemento_res as cliente_endereco_complemento,
            sis_cliente.numero_res as cliente_endereco_numero,
            sis_boleto.nome as nome_conta

            FROM sis_lanc 
            inner join sis_cliente on sis_cliente.login=sis_lanc.login
            inner join sis_boleto on sis_boleto.id=sis_cliente.conta
            inner join sis_plano on sis_plano.nome=sis_cliente.plano
            WHERE 
                sis_lanc.status = 'aberto' 
                AND deltitulo =0  
                AND (
                LOWER(trim(sis_boleto.nome))='cachebank'
                or 
                LOWER(trim(sis_boleto.nome))='cachêbank'
                )
                AND sis_lanc.datapag is null
                AND sis_lanc.nossonum is null
                and not exists (
                    select 1 from cachebank_invoices cbinvoice where cbinvoice.id_cliente=sis_cliente.id
                    and cbinvoice.id_lanc=sis_lanc.id
                );
        "; 
        $aberto_result = $conn->query($aberto_sql);

        while ($fatura = $aberto_result->fetch_assoc()) {
            $sis_lanc_id=$fatura['sis_lanc_id'];
            $sis_lanc_uuid_lanc=$fatura['sis_lanc_uuid_lanc'];

            $res=generateBoleto($pdo, $fatura);
            if(empty($res["success"])){
                log_message("Error emissão boleto". json_encode($res));
                var_dump($res);
                continue;
            }
            if($res["success"]==false){
                log_message("Error emissão boleto .".$fatura["cliente_nome"].": " . json_encode($res));
                continue;
            }

            $res=$res["transacao"];

            $idtransacao=$res["idtransacao"];
            $nosso_numero=$res["boleto"]["nossonumero"];
            $linha_digitavel=$res["boleto"]["linhaDigitavel"];
            $codigo_barra=$res["boleto"]["codigoDeBarra"];

            $txId=$res["pix"]["txid"];
            $url_qrcode='https://fatura.cachebank.com.br/api/v3/show/qrcode/'.$res["idtransacao"];
            $pix_copia_cola=$res["pix"]["qrcode"];
            $statusName=getStatusPaymentName($res["status"]);

            $stmt = $pdo->prepare("INSERT INTO cachebank_invoices ( id_cliente,id_lanc,idtransaction, linha_digitavel,nosso_numero,codigo_barra, txid, url_qrcode, pix_copia_cola, created_at,updated_at, status ) VALUES ( :cliente_id, :sis_lanc_id, :idtransacao, :linha_digitavel, :nosso_numero, :codigo_barra, :txId, :url_qrcode, :pix_copia_cola, NOW(), NOW(), :status )");

            $stmt->bindParam(':cliente_id', $fatura['cliente_id'], PDO::PARAM_INT);
            $stmt->bindParam(':sis_lanc_id', $fatura['sis_lanc_id'], PDO::PARAM_INT);
            $stmt->bindParam(':idtransacao', $idtransacao, PDO::PARAM_STR);
            $stmt->bindParam(':linha_digitavel', $linha_digitavel, PDO::PARAM_STR);
            $stmt->bindParam(':nosso_numero', $nosso_numero, PDO::PARAM_STR);
            $stmt->bindParam(':codigo_barra', $codigo_barra, PDO::PARAM_STR);
            $stmt->bindParam(':txId', $txId, PDO::PARAM_STR);
            $stmt->bindParam(':url_qrcode', $url_qrcode, PDO::PARAM_STR);
            $stmt->bindParam(':pix_copia_cola', $pix_copia_cola, PDO::PARAM_STR);
            $stmt->bindParam(':status', $statusName, PDO::PARAM_STR);

            if ($stmt->execute() === FALSE) {
                log_message("Falha no salvamento do boleto |  SQL: ".$conn->error);
            }
            $stmt=null;



            // Atualizar sis_lanc
            $updateQuery = "UPDATE sis_lanc SET  linhadig = :linhadig, nossonum =:nossonum, usergerou='mk-bot', imp='sim'  WHERE id = :sis_lanc_id";
            $stmt = $pdo->prepare($updateQuery);
            if (!$stmt) {
                throw new Exception("Erro ao preparar declaração SQL para atualizar sis_lanc: " . $conn->error);
            }
            $stmt->bindParam(":linhadig", $linha_digitavel, PDO::PARAM_STR);
            $stmt->bindParam(":nossonum", $nosso_numero, PDO::PARAM_STR);
            $stmt->bindParam(":sis_lanc_id", $sis_lanc_id,  PDO::PARAM_INT);

            if (!$stmt->execute()) {
                throw new Exception("Erro ao executar declaração SQL para atualizar sis_lanc: " . $conn->error);
            }



            if(tableExists($pdo, 'sis_qrpix')){
               // Atualizar qrcode
                $updateQuery = "INSERT INTO sis_qrpix ( titulo , qrcode ) VALUES ( :titulo, :qrcode )";
                $stmt = $pdo->prepare($updateQuery);
                if (!$stmt) {
                    throw new Exception("Erro ao preparar declaração SQL para atualizar sis_lanc: " . $conn->error);
                }
                $stmt->bindParam(":titulo", $sis_lanc_uuid_lanc, PDO::PARAM_STR);
                $stmt->bindParam(":qrcode", $pix_copia_cola, PDO::PARAM_STR);

                if (!$stmt->execute()) {
                    throw new Exception("Erro ao executar declaração SQL para atualizar sis_lanc: " . $conn->error);
                }
            }

           

           

        }
    }catch(Exception $ex){
        unlockTransaction();
    }

    unlockTransaction();
    $conn->close();
?>