<div class="h-100 w-100">
<?php
////ini_set( 'display_errors', 'on' );

$baja = Enmascarar_post('baja');
$buscar = Enmascarar_post('buscar');

/*
$sql_max_pc = "SELECT MAX(id) AS id
                 FROM dbo.PC; ";
//echo $sql_max_pc;
$row_max_pc = sql_do_array(sql_do_query($sql_max_pc, '1', $usu));

$sql_id_pc = "SELECT id_tabla AS id
                FROM more_id 
               WHERE tabla = 'more_PC'; ";
//echo $sql_id_pc;
$row_id_pc = sql_do_array(sql_do_query($sql_id_pc, '1', $usu));

if($row_max_pc['ID']!=($row_id_pc['ID']+1)){
    $udp_id_pc = "UPDATE more_id
                     SET id_tabla = '".$row_max_pc['ID']."'
                   WHERE tabla = 'more_PC'; ";
    //echo $udp_id_pc;
    sql_do_query($udp_id_pc, '1', $usu);

}
*/

?>
<div class="row flex-column align-items-start m-2">
    <strong class="fs-3">PCs</strong>
</div>
<div class="row flex-column m-2">
    <form id="form1" name="form1" method="post" action="">
        <input type="text" class="form-control" name="buscar" id="buscar" placeholder="Filtro de Busqueda" onchange="submit();" />
    </form>
</div>
<div class="row flex-column m-2 overflow-auto" style="height: 100vh;">
                    <table class="table table-bordered table-striped"  style="background-color: white;">
                    <thead>
                        <tr>
                            <th>Ord</th>
                            <th>Código PC</th>
                            <th>IP</th>
                            <th>Marca</th>
                            <th>Tipo de Procesador</th>
                            <th>Velocidad Procesador</th>
                            <th>Memoria RAM</th>
                            <th>Estado</th>
                            <th>Opciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        
                        if($nivel >= '9'){
                            $filtro = " AND a.baja <> '1'";
                        }else{
                            $filtro = ' ';
                        }
                        
                        $sql = "SELECT a.pcid,
                                       a.codigopc,
                                       a.ip,
                                       a.marca, 
                                       a.tipoprocesador, 
                                       a.velocidadprocesador, 
                                       a.memoriaram,
                                       a.baja
                                  FROM more_pc a
                                 WHERE (UPPER(a.codigopc) like UPPER('%$buscar%') 
                                       OR UPPER(a.ip) like UPPER('%$buscar%')
                                       OR UPPER(a.marca) like UPPER('%$buscar%')
                                       OR UPPER(a.centrocosto) like UPPER('%$buscar%')
                                       OR UPPER(a.velocidadprocesador) like UPPER('%$buscar%')
                                       OR UPPER(a.memoriaram) like UPPER('%$buscar%')) $filtro
                              ORDER BY a.pcid DESC";
                        //echo $sql;
                        $result = sql_do_query($sql, '1', $usu);
                        $n = 0;
                        while ($row = sql_do_array($result)) {
                            $n++;

                            if ($row['BAJA'] != '1') {
                                $img_estado = 'si.gif';
                            } else {
                                $img_estado = 'no.gif';
                            }
                            
                        /*    
                        $sql_prueba = "SELECT pcid, codigopc
                                         FROM more_pc_extras
                                        WHERE pcid = '".$row['ID']."'; ";
                        //echo $sql;
                        $row_prueba = sql_do_array(sql_do_query($sql_prueba, '1', $usu));
                        if(!$row_prueba['CODIGOPC']){
                            
                                    $sql_ins = "INSERT INTO  more_pc_extras(
                                                             pcid,
                                                             codigopc
                                                )VALUES(  
                                                             ".$row['ID'].",
                                                             '".$row['CODIGOPC']."'
                                                );";
                                    //echo $sql;
                                    $ins_prueba = sql_do_query($sql_ins, '1', $usu);
                            
                        }
                        */    
                            
                        ?>
                            <tr>
                                <td align="center" valign="middle" ><?php echo $n; ?>&nbsp;</td>
                                <td align="center" valign="middle" ><?php echo $row['CODIGOPC']; ?>&nbsp;</td>
                                <td align="center" valign="middle" ><?php echo $row['IP']; ?>&nbsp;</td>
                                <td align="center" valign="middle" ><?php echo $row['MARCA']; ?>&nbsp;</td>
                                <td align="center" valign="middle" ><?php echo $row['TIPOPROCESADOR']; ?>&nbsp;</td>
                                <td align="center" valign="middle" ><?php echo $row['VELOCIDADPROCESADOR']; ?>&nbsp;</td>
                                <td align="center" valign="middle" ><?php echo $row['MEMORIARAM']; ?>&nbsp;</td>
                                <td align="center" valign="middle" width="30"><img src="img/ico/<?php echo $img_estado; ?>" width="30" height="30" /></td>
                                <td align="center">
                                    <?php
                                    $variables = array("codigopc" => $row['CODIGOPC']);
                                    $estados = array("24" => "Resumen", "17" => "Prestar", "15" => "Reparar");
                                    
                                    echo impSelectHerramienta($variables, $estados, $n);
                                    ?>
                                </td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
<br />

