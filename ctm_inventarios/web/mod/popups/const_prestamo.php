<?php
//var_dump($_POST);
//ini_set( 'display_errors', 'on' );
$prestamoid = Enmascarar_get('p1');
if($prestamoid){
        
                        $sql="   SELECT p.prestamoid, 
                                        p.fechaprestamo, 
                                        p.responsable, 
                                        p.id_pc, 
                                        p.id_impresoras, 
                                        p.id_monitores, 
                                        p.id_otros, 
                                        p.dispositivo, 
                                        p.observaciones, 
                                        p.devuelto, 
                                        p.fechadevolucion, 
                                        p.legajo, 
                                        p.centrocosto, 
                                        p.devolucion_estimada, 
                                        p.estadoid, 
                                        p.auditoria_usu, 
                                        p.auditoria_fecha 
                                   FROM inve_prestamos p 
                                  WHERE p.prestamoid = '$prestamoid';";
                        //echo $sql;
                        $row = sql_do_array(sql_do_query($sql,'1',$usu));                        
                        //var_dump($row);
?>                        
<!DOCTYPE html>
<html>
<head>
    <title>Diseño A4</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            width: 794px;
            height: 1123px;
            position: relative;
            font-family: Arial, sans-serif;
        }
        .page {
            position: relative;
            width: 100%;
            height: 100%;
            background-image: url('img/fondo_pdf.jpg');
            background-size: cover;
            background-position: center;
            page-break-after: always;
        }
        .header {
            width: 100%;
            height: 150px;
            /* background-color: rgba(255, 0, 0, 0.5);  Semi transparente */
            text-align: center;
            line-height: 60px; /* Centrado vertical de texto */
        }
        .content {
            padding: 20px;
            height: calc(100% - 270px); /* Altura total menos header y footer */
            /*background-color: rgba(0, 0, 255, 0.5);  Semi transparente */
            overflow-y: auto;
        }
        .footer {
            width: 100%;
            height: 80px;
            /* background-color: rgba(0, 255, 0, 0.5); Semi transparente */
            position: absolute;
            bottom: 0;
            text-align: center;
            line-height: 20px; /* Centrado vertical de texto */
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    
<div class="area">
    <div class="page">
        <div class="header">&nbsp;</div>
        <div class="content">
                <h2>Formulario de Préstamo de Equipo Informático</h2>
                <p>Área de Informática y Comunicaciones - Comisión Técnica de Salto Grande</p>
                <table>
                    <tr>
                        <th>ID de Préstamo</th>
                        <td><?php echo $row['PRESTAMOID']; ?></td>
                    </tr>
                    <tr>
                        <th>Fecha de Préstamo</th>
                        <td><?php echo enmascararFecha($row['FECHAPRESTAMO']); ?></td>
                    </tr>
                    <tr>
                        <th>Responsable</th>
                        <td>
                            <?php 
                                $resp = getUsuario($row['LEGAJO'], '1', $usu);
                                echo $resp['EMPNOMBRE']." (".$resp['EMPLEGAJO'].")";
                                //var_dump($resp);
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Equipamiento</th>
                        <td>
                            <?php 
                                    if($row['ID_PC']){
                                        $pc = getPc($row['ID_PC'],'1',$usu);
                                        echo "<p>".$pc['CODIGOPC']." - ".$pc['MARCA']." - ".$pc['TIPOPROCESADOR']." (".$pc['DISCO']." - ".$pc['MEMORIARAM'].") </p>";
                                    }
                                    if($row['ID_IMPRESORAS']){
                                        $pc = getImpresoras($row['ID_IMPRESORAS'],'1',$usu);
                                        echo "<p>".$pc['CODIGO']." - ".$pc['MARCA']." - ".$pc['NUMEROSERIE']."</p>";
                                    }   
                                    if($row['ID_MONITORES']){
                                        $pc = getMonitores($row['ID_MONITORES'],'1',$usu);
                                        echo "<p>".$pc['CODIGO']." - ".$pc['MARCA']." - ".$pc['NUMEROSERIE']."</p>";
                                    }
                                    if($row['ID_OTROS']){
                                        $pc = getOtros($row['ID_OTROS'],'1',$usu);
                                        echo "<p>".$pc['CODIGO']." - ".$pc['MARCA']." - ".$pc['NUMEROSERIE']."</p>";
                                    } 
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Dispositivo</th>
                        <td><?php echo $row['DISPOSITIVO']; ?></td>
                    </tr>
                    <tr>
                        <th>Observaciones</th>
                        <td><?php echo $row['OBSERVACIONES']; ?></td>
                    </tr>
                    <tr>
                        <th>Devuelto</th>
                        <td><?php echo $row['DEVUELTO']; ?></td>
                    </tr>
                    <tr>
                        <th>Fecha de Devolución</th>
                        <td><?php echo enmascararFecha($row['FECHADEVOLUCION']); ?></td>
                    </tr>
                    <tr>
                        <th>Centro de Costo</th>
                        <td>
                            <?php 
                            $cc = getCentroCosto($row['CENTROCOSTO'], '1', $usu);
                            echo $cc['CONNOMCC']." (".$cc['CONIDCC'].") "; 
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Devolución Estimada</th>
                        <td><?php echo $row['DEVOLUCION_ESTIMADA']; ?></td>
                    </tr>
                </table>
        </div>
        <div class="footer">&nbsp;</div>
    </div>
</div>
    
</body>
</html>
<script>
    window.onafterprint = function(){
       window.close(); // Intenta cerrar la ventana después de imprimir
    };

    window.onload = function() {
        window.print(); // Forzar la impresión al cargar la página
    };
</script>

<?php                        
}
?>

