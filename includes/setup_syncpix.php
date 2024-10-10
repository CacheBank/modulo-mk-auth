<?php
    require '/opt/mk-auth/admin/addons/cachebank/db.hhvm';
    require '/opt/mk-auth/admin/addons/cachebank/includes/utils.hhvm';

    if(!tableExists($pdo, 'sis_qrpix')){
        return;
    }
    echo '
        Iniciando sincronização de píx
    ';
    $config = getConfig($pdo);
    log_message("Iniciando sincronização de pix");

  

    try{
        $aberto_sql = "SELECT DISTINCT(sis_lanc.uuid_lanc) as uuid_lanc, ch.pix_copia_cola 
            FROM `cachebank_invoices` ch 
            inner join sis_lanc sis_lanc on sis_lanc.id=ch.id_lanc 
            where 
                not exists( 
                    select 1 
                        from sis_qrpix sis_qrpix 
                    where sis_qrpix.titulo=sis_lanc.uuid_lanc 
                );
        "; 
        $aberto_result = $conn->query($aberto_sql);


        while ($fatura = $aberto_result->fetch_assoc()) {
            $uuid_lanc=$fatura['uuid_lanc'];
            $pix_copia_cola=$fatura['pix_copia_cola'];
            

            $stmt = $pdo->prepare("INSERT INTO sis_qrpix ( titulo , qrcode ) VALUES ( :titulo, :qrcode )");

            $stmt->bindParam(':titulo', $uuid_lanc, PDO::PARAM_STR);
            $stmt->bindParam(':qrcode', $pix_copia_cola, PDO::PARAM_STR);

            if ($stmt->execute() === FALSE) {
                log_message("Falha na atualização do boleto  ".$conn->error);
            }
            $stmt=null;

        }
    }catch(Exception $ex){
        log_message("Erro ao sincronizar");
    }

 
    $conn->close();
?>