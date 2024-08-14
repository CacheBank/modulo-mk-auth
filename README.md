# Instalação

Para realizar a instalação, execute os comandos abaixo
```SHELL
cd /root;
wget https://raw.githubusercontent.com/CacheBank/modulo-mk-auth/master/instalador-inicial.php;
php instalador-inicial.php;
```

## Configure as credencias API
1 - Vá até o menu "Provedor" -> "Cachê Bank"

2 - Defina os dados do CLIENT ID, CLIENT SECRET e a URL do WEBHOOK.

A URL do webhook deve ser o endereço do seu Mk-Auth seguido por /cachebank/webhook.php


### Cadastrar Conta Bancária
1 - Vá até o menu Provedor e na opção "Contas Bancárias"

2 - Crie uma nova conta bancária, com os dados:

Nome da conta: cachebank

O nome da conta precisa ser exatamente "cachebank"

Dados do Banco: Boleto Próprio

Cedente: Nome da sua empresa


#### Definição de Juros/Multa

Percentual multa: Defina a multa após o vencimento

Percentual juros:  Defina juros diário que deve ser cobrado.

Você pode definir por exemplo 0,03% diariamente | Equivalente á 0,03 * 30 = 0,9% ao mês.

O modulo irá multiplicar o juros diário por 30 para equivaler a 1 mês


#### Definição de Desconto
Usar valor tipo: Aplique se quer oferecer desconto antes do vencimento.

### Outras opções
Recalcular 2º via data/valor: - Marque como não

Maximo dias para recalcular: Marque como não

Aperte em Gravar

Agora, a partir do momento em que cadastrar um cliente no MK-AUTH e definir a conta cachebank, o sistema irá sincronizar com a Cachê Bank para gerar os boletos a cada 1 minuto.



Menu -> Financeiro -> Todos os titulos -> Visualizar

## Observações Importante

Para que o sistema gere os boletos corretamente, é preciso que estejam preenchidos corretamente
- Nome do cliente
- E-mail correto ou vazio
- Telefone
- CPF/CNPJ válido
- Endereço (Logradouro, Nº, Bairro, Cidade e Estado)
- Plano criado e vinculado

### Ver boletos emitidos
Após emitir um boleto, você poderá visualizar o boleto através do próprio mka. 


### Ver Carnês emitidos
Após emitir um carnê, você poderá visualizar através do próprio mka.
Menu -> Financeiro -> Carnês em aberto -> Abrir | Escolha entre ver a capa ou a lista de boletos do carnê.


