<link href="../includes/style.css" rel="stylesheet" type="text/css" />
<form name="form" id="form" method="post" action="configuration.php">

<?php

    include dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'.DIRECTORY_SEPARATOR.  'db.hhvm';
    include dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR. 'utils.hhvm';
    $config=getConfig($pdo);
    $clientId=$config->client_id;
    $clientSecret=$config->client_secret;
    $webhookUrl=$config->webhook_url?str_replace("/cachebank/webhook", "", $config->webhook_url):$_SERVER['HTTP_HOST'];
   

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
        $clientId=$_POST["clientId"];
        $clientSecret=$_POST["clientSecret"];
        $webhookUrl=$_POST["webhookUrl"].'/cachebank/webhook';

        // Atualizar sis_lanc
        $updateQuery = "UPDATE cachebank_config SET client_id = :client_id, client_secret = :client_secret, webhook_url = :webhook_url";
        $stmt = $pdo->prepare($updateQuery);
        if (!$stmt) {
            throw new Exception("Erro ao preparar declaração SQL para atualizar sis_lanc: " . $pdo->error);
        }
        $stmt->bindParam(":client_id", $clientId, PDO::PARAM_STR);
        $stmt->bindParam(":client_secret", $clientSecret,  PDO::PARAM_STR);
        $stmt->bindParam(":webhook_url", $webhookUrl,  PDO::PARAM_STR);

        if (!$stmt->execute()) {
            throw new Exception("Erro ao executar declaração SQL para atualizar sis_lanc: " . $stmt->error);
        }


        echo  "<script>alert('Dados salvos com sucesso');</script>";

    header("Location: index.php");

    }
?>



<h4 class="subtitle is-7 has-text-weight-bold has-text-grey"> Configuração </h4>


    <div class="form-cell">
        <label for="clientIdInput">Client ID</label>
        <input
          required
          style="width:350px"
          placeholder="DIGITE SEU CLIENT ID"
          type="text"
          id="clientIdInput"
          name="clientId"
          value="<?php echo $clientId ?>"
        />
        <br />
        <small>Disponível no Portal do cliente > Menu -> Integração</small>
    </div>



            <br/>

    <div class="form-cell">
        <label for="clientsecretInput">Client Secret</label>
        <input
          required
          style="width:350px"
          placeholder="DIGITE SEU CLIENT SECRET"
          type="password"
          name="clientSecret"
          id="clientsecretInput"
          value="<?php echo $clientSecret ?>"
        />
        <br/>
        <small>Disponível no Portal do cliente > Menu -> Integração</small>
    </div>

    <br/>

    <div class="form-cell">
        <label for="clientsecretInput">URL do Mk-Auth</label>
        <input
          required
          style="width:350px"
          placeholder="Client Secret"
          type="text"
          id="clientsecretInput"
          name="webhookUrl"
          value="<?php echo $webhookUrl ?>"
        />
        <br/>
    </div>

        <br/>
        <br/>

    <div class="form-row">
        <div class="form-cell btn-group">
            <input type="submit" value="Salvar Alteração" />
        </div>
    </div>
</form>



