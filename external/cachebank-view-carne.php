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
include '/opt/mk-auth/admin/addons/cachebank/includes/includes.hhvm';
//include '../includes/includes.hhvm';


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if(empty($_GET["carne"])){
        echo "Carnê não encontrado";
        return ;
    }
    
        $carneId= filter_input(INPUT_GET, 'carne', FILTER_SANITIZE_SPECIAL_CHARS);
}
$linkCapa="/cachebank/cachebank-view-capa.php?carne=". $carneId;
$linkCarne="/cachebank/cachebank-view-carne-invoices.php?carne=". $carneId;
?>
<br>
<center>
<a class="btn btn-primary button" href="<?php echo $linkCapa ?>">Ver Capa</a>
<a class="btn btn-secondary button button2" href="<?php echo $linkCarne ?>">Ver Carnê</a>
</center>
