
<?php
// INCLUE FUNCOES DE ADDONS -----------------------------------------------------------------------
include('addons.class.php');

?>
<!DOCTYPE html>
<html lang="pt-BR" class="has-navbar-fixed-top">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta charset="utf-8">
<title>MK-AUTH - Módulo Cachê</title>

<link href="../../../estilos/mk-auth.css" rel="stylesheet" type="text/css" />
<link href="../../../estilos/font-awesome.css" rel="stylesheet" type="text/css" />

<link href="../../../estilos/bi-icons.css" rel="stylesheet" type="text/css" />

<script src="../../../scripts/jquery.js"></script>
<script src="../../../scripts/mk-auth.js"></script>

</head>
<body>

<?php 
 include('/opt/mk-auth/admin/topo.php'); ?>


<nav class="breadcrumb has-bullet-separator is-centered" aria-label="breadcrumbs">
<ul>
<li><a href="#"> ADDON</a></li>
<li class="is-active"><a href="#" aria-current="page"> Cachê Bank - V1 </a></li>
</ul>
</nav>


<?php include('configuration.php'); ?>

<?php include('../../../baixo.php'); ?>

<script src="../../../menu.js.hhvm"></script>

</body>
