<?php
    include_once('../../conf/funcion.php');
    include_once('../../conf/seccion.php');
$estado = substr($_GET['estado'], 0, 3);
?>
<link href="conf/estilo.css" rel="stylesheet" type="text/css" />
<style>
    body{
        margin:0px;
    	padding:0px;
    }
    #popup{
    	width:800px;
    	float:right;
    }
</style>
<?php
    $direcion='../mod/acerca_de/migracion/script/'.$estado.'.php';
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//ES" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<script type="text/javascript" language='javascript' src="conf/javascript.js"></script>
<script type="text/javascript" src="conf/jquery.js"></script>
<title>POPUP</title>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- DataTables CSS y JS básicos -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css">
<script type="text/javascript" src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>

<!-- DataTables Buttons plugin CSS y JS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.7.1/css/buttons.dataTables.min.css">
<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>

<!-- JSZIP (necesario para el exportado de Excel) -->
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>

<!-- PDFMake (necesario para el exportado a PDF) -->
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

<!-- Plugins adicionales para los botones de DataTables -->
<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.print.min.js"></script>

</head>
<body>
<html>
<?php 
    //echo $direcion;
    include_once "$direcion"; 
    $sql="UPDATE more_migracion_script SET ejecucion_usu = '$usu', ejecucion_fecha = CONVERT(datetime, '".substr($hoy,0,10)."', 120) WHERE migracionid = '$estado';";
    echo $sql." <br />";
    $result_0 = sql_do_query($sql,'1',$usu);
    
?>
</html>
</body>