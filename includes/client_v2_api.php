<?php

function client($pdo, $params, $methodUrl, $method){

    $config = getConfig($pdo);

    $fields_string = json_encode($params);

    //open connection
    $ch = curl_init();

    //set the url, number of POST vars, POST data
    curl_setopt($ch,CURLOPT_URL, 'https://api.cachebank.com.br/api/v2/'.$methodUrl);
    curl_setopt($ch,CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

     curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

    $headers = array(
        'Content-type: application/json',
        'client_id: '.$config->client_id,
        'client_secret: '.$config->client_secret
    );
   
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    //execute post
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        log_message("curl_error : " . $error_msg);

    }
        
    //close connection
    curl_close($ch);

    $responseJson=json_decode($result, true);

    return $responseJson;
}

function mountParamsCreatePayment($pdo,$params){
    $config = getConfig($pdo);
    
    $payload=[];

    $errorData=[
        'nome_cliente' => $params["cliente_nome"] 
    ];

    $payload["urlnotificacao"] = $config->webhook_url.'/cachebank/webhook.php';

    $payload["partner"]="mkauth-owner";

    // Definir Juros
    if(isset($params["lanc_multa"]) or isset($params["lanc_juros"])){
        if($params["lanc_multa"]>0 && $params["lanc_juros"]>0){
            $payload['aplicarmulta'] = true;
            $payload['multa_juros'] = $params["lanc_juros"];
            $payload['multa_valor'] = $params["lanc_multa"];
        }else{
            $payload['aplicarmulta'] = false;
        }
    }else{
        $payload['aplicarmulta'] = false;
    }

    // Definição de Desconto
    if( isset($params["lanc_desconto"]) ){
        if($params["lanc_desconto"]>0){
            $payload['aplicardesconto'] = true;
            $payload['desconto_limite'] = 0;

            if($params['lanc_tipo_desc']=='perc'){
                $payload['desconto_porcento'] = $params["lanc_desconto"];
            }else{
                $payload['desconto_valorfixo'] = $params["lanc_desconto"];
            }
        } else{
            $payload['aplicardesconto'] = false;
        }
    }else{
        $payload['aplicardesconto'] = false;
    }

    if(isset($params["lanc_datavenc"])){
        $payload['vencimento'] = $params["lanc_datavenc"];
    }else{
        $errorData["vencimento"]= "não definido";
    }

    if(isset($params["sis_lanc_id"])){
        $payload['referenciapedido'] = 'ref-'.$params["sis_lanc_id"];
    }

    // Itens
    if(isset($params["lanc_datavenc"])){
        $payload['items'][0] = [
            'qtd' => 1,
            'descricao' => ucfirst($params["lanc_tipo"]).' '.$params["lanc_referencia"],
            'valor' => $params["lanc_valor"],
        ];
    }else{
        $errorData["itens"]= "não definido";
    }

    
    // Dados do cliente
    if(isset($params["cliente_nome"])){
        $payload['cliente']["nomepessoa"] = $params["cliente_nome"];
    }else{
        $errorData["nomepessoa"]= "não definido";
    }
    if(isset($params["cliente_cpf_cnpj"])){
        $payload["cliente"]["cpf_cnpj"] = preg_replace('/\D/', '', $params["cliente_cpf_cnpj"]);
    }else{
        $errorData["cpf_cnpj"]= "não definido";
    }
    if(isset($params["cliente_email"])){
        $payload["cliente"]["email"] = $params["cliente_email"];
    }else{
        $payload["cliente"]["email"] = null;
    }

    if(isset($params["cliente_phone"])){
        $payload["cliente"]["telefone"] =preg_replace('/\D/', '',$params["cliente_phone"]);
    }else{
        
        $errorData["telefone"]= "não definido";
    }

    if(isset($params["cliente_endereco_cep"])){
        $payload["cliente"]["cep"] = preg_replace('/\D/', '',$params["cliente_endereco_cep"]);
    }

    if(isset($params["cliente_endereco_logradouro"])){
        $payload["cliente"]["logradouro"] = $params["cliente_endereco_logradouro"];
    }

    if(isset($params["cliente_endereco_numero"])){
        $payload["cliente"]["numero"] = preg_replace('/\D/', '',$params["cliente_endereco_numero"]);
    }else{
        $payload["cliente"]["numero"] =  0;

    }

    if(isset($params["cliente_endereco_bairro"])){
        $payload["cliente"]["bairro"] = $params["cliente_endereco_bairro"];
    }

    if(isset($params["cliente_endereco_cidade"])){
        $payload["cliente"]["cidade"] = $params["cliente_endereco_cidade"];
    }

    if(isset($params["cliente_endereco_estado"])){
        $payload["cliente"]["estado"] = $params["cliente_endereco_estado"];
    }

    if(isset($params["cliente_endereco_complemento"])){
        $payload["cliente"]["complemento"] = $params["cliente_endereco_complemento"];
    }else{
        $payload["cliente"]["complemento"] = null;
    }


    log_message("Dados da transação : " . json_encode($payload));


    return $payload;
}
function generateBoleto($pdo,$params){
    $payload=mountParamsCreatePayment($pdo,$params);
    
    $client=client($pdo, $payload, 'transacao/boleto','POST');

    return $client;
}

function cancelBill($pdo,$idtransaction){
    $payload=[
        'idtransacao' => $idtransaction
    ];
    
    $client=client($pdo, $payload, 'transacao/markcancelled','POST');

    return $client;
}

function obterDadosWebHookBoleto($pdo,$notification_id, $idtransaction){
    $payload=[
        'notification_id' => $notification_id,
        'idtransaction' => $idtransaction
    ];
    $client=client($pdo,$payload, 'notificacao/transacao','POST');

    return $client;
}

function obterTransacao($pdo,$idtransaction){
    $payload=[
        'id' => $idtransaction
    ];
    $client=client($pdo,$payload, 'transacao/exibir/'.$idtransaction,'GET');

    return $client;
}