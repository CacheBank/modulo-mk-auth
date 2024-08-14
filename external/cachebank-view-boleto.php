<?php
include '/opt/mk-auth/admin/addons/cachebank/includes/includes.hhvm';
//include '../includes/includes.hhvm';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $idLanca=(int)$_GET["titulo"];

          $query = "SELECT cinvoices.id_lanc as id_lanc, cinvoices.idtransaction
                          FROM cachebank_invoices cinvoices
                          WHERE cinvoices.id_lanc = :id_lanc";
                $stmt = $pdo->prepare($query);
                if (!$stmt) {
                    throw new Exception("Erro ao preparar declaração SQL para selecionar de pix_info: " . $pdo->error);
                }
                $stmt->bindParam(":id_lanc", $idLanca,  PDO::PARAM_INT);
                $stmt->execute();
                $resDb=$stmt->fetch(PDO::FETCH_ASSOC);

                if(empty($resDb["idtransaction"])){
                    echo "Cachê Bank: O sistema ainda não gerou este boleto";
                    return ;
                } ;
                $idtransaction=$resDb["idtransaction"];
                header('Location: https://fatura.cachebank.com.br/show/boleto/'.$idtransaction);
}