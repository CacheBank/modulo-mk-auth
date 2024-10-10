<form id="myForm" action="https://fatura.cachebank.com.br/checkout/v1/show/selected_invoices" method="POST">

<?php
include '/opt/mk-auth/admin/addons/cachebank/db.hhvm';
 include '/opt/mk-auth/admin/addons/cachebank/includes/utils.hhvm';
 

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if(empty($_GET["carne"])){
        echo "Carnê não encontrado";
        return ;
    }
    
        $carneId= filter_input(INPUT_GET, 'carne', FILTER_SANITIZE_SPECIAL_CHARS);

        

        $aberto_sql = "SELECT cinvoices.id_lanc as id_lanc, cinvoices.idtransaction
                          FROM cachebank_invoices  cinvoices
                          inner join sis_lanc on sis_lanc.id=cinvoices.id_lanc
                          WHERE sis_lanc.codigo_carne = '".$carneId."'";
                

    $aberto_result = $conn->query($aberto_sql);

    $faturas_aberto = array();
    $requests_data = array();

    while ($fatura = $aberto_result->fetch_assoc()) {
        echo '<input type="hidden" name="invoices_id[]" value="'.htmlentities($fatura["idtransaction"]).'">';
    }
}

?>
<script type="text/javascript">
    document.getElementById('myForm').submit();
</script>