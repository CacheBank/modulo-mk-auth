<style>
.button {
  background-color: #04AA6D; /* Green */
  border: none;
  color: white;
  padding: 15px 32px;
  text-align: center;
  text-decoration: none;
  display: inline-block;
  font-size: 16px;
  margin: 4px 2px;
  cursor: pointer;
}

.button2 {background-color: #008CBA;} /* Blue */
.button3 {background-color: #f44336;} /* Red */ 
.button4 {background-color: #e7e7e7; color: black;} /* Gray */ 
.button5 {background-color: #555555;} /* Black */
</style>

<?php

 include '/opt/mk-auth/admin/addons/cachebank/db.hhvm';
 include '/opt/mk-auth/admin/addons/cachebank/includes/utils.hhvm';


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $carneId= filter_input(INPUT_GET, 'carne', FILTER_SANITIZE_SPECIAL_CHARS);

    if(empty($carneId)){
        echo "Carnê não encontrado";
        return ;
    }

    $query = "SELECT sis_carne.codigo as codigo FROM sis_carne sis_carne 
              inner join sis_cliente on sis_cliente.login=sis_carne.login
              inner join sis_boleto on sis_boleto.id=sis_cliente.conta
              where sis_carne.codigo=:id_lanc
                AND (
                LOWER(trim(sis_boleto.nome))='cachebank'
                or 
                LOWER(trim(sis_boleto.nome))='cachêbank'
                )";

        $stmt = $pdo->prepare($query);
        if (!$stmt) {
        throw new Exception("Erro ao preparar declaração SQL para selecionar : " . $pdo->error);
        }

        $stmt->bindParam(":id_lanc", $carneId,  PDO::PARAM_STR);
        $stmt->execute();
        $resDb=$stmt->fetch(PDO::FETCH_ASSOC);

        if(empty($resDb["codigo"])){
          header('Location: /boleto/carne2.hhvm?carne='.$carneId);
          return ;
        };

    
}
$linkCapa="/cachebank/cachebank-view-capa.php?carne=". $carneId;
$linkCarne="/cachebank/cachebank-view-carne-invoices.php?carne=". $carneId;
?>
<br>
<center>
<a class="btn btn-primary button" href="<?php echo $linkCapa ?>">Ver Capa</a>
<a class="btn btn-secondary button button2" href="<?php echo $linkCarne ?>">Ver Carnê</a>
</center>
