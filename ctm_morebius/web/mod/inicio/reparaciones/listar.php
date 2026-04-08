<div class="h-100 w-100">
<?php
////ini_set( 'display_errors', 'on' );
/**
 * RECIBO LAS VARIABLES PARA OPERAR
**/

//var_dump($_SESSION);

$baja = Enmascarar_post('baja');
$buscar = Enmascarar_post('buscar');


?>
<div class="row flex-column align-items-start m-2">
    <strong class="fs-3">Reparaciones</strong>
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
                <th>Id</th>
                <th>Problema</th>
                <th>Observaciones</th>
                <th>Codigo</th>
                <th>Fecha del Prestamo</th>
                <th>Estado</th>     
                <th>Opciones</th>
            </tr>
        </thead>
        <tbody>
            <?php
              
            if($nivel < '9'){
                $filtro = " AND p.devuelto <> '1'";
            }else{
                $filtro = ' ';
            }
                         
            $sql="SELECT p.reparacionid, 
                         p.id_aspnetuser, 
                         p.id_zonas_inventario,
                         p.fecha_envio, 
                         p.id_pc, 
                         p.id_impresoras, 
                         p.id_monitores,
                         p.id_otros, 
                         p.id_proveedores, 
                         p.problema, 
                         p.observaciones, 
                         p.reparado, 
                         p.fecha_devolucion, 
                         p.estadoid, 
                         p.auditoria_usu, 
                         p.auditoria_fecha 
                    FROM more_reparaciones p
                   WHERE (UPPER(p.reparacionid) like UPPER('$buscar%') 
                          OR UPPER(p.observaciones) like UPPER('$buscar%')
                   	  OR UPPER(p.id_pc) like UPPER('$buscar%')
                      OR UPPER(p.fecha_envio) like UPPER('$buscar%')) $filtro
		ORDER BY p.reparacionid DESC ";
                //echo $sql;
                $result = sql_do_query($sql,'1',$usu);
                $n=0;
                while($row=sql_do_array($result)) {
                        $n++;
                        if($row['REPARADO']!='1'){
                            $img_estado = '0.png';
                        }else{
                            $img_estado = '1.png'; 
                        }
                ?>
                        <tr>
                            <td width="30" align="center"> <?php echo $n;?>&nbsp;</td>
                            <td width="30" align="center"> <?php echo $row['REPARACIONID'];?>&nbsp;</td>
                            <td width="100" align="center"><?php echo $row['PROBLEMA'];?>&nbsp;</td>
                            <td width="100" align="center"><?php echo $row['OBSERVACIONES'];?>&nbsp;</td>
                            <td width="100" align="rigth"><?php echo " PC: ".$row['ID_PC']."<br />"." Impresora: ".$row['ID_IMPRESORAS']."<br />"." Monitores: ".$row['ID_MONITORES']."<br />"." Otros: ".$row['ID_OTROS'];?>&nbsp;</td>
                            <td width="100" align="center"><?php echo enmascararFecha($row['FECHA_ENVIO']);?>&nbsp;</td>
                            <td width="80" align="center"  valign="middle">
                                <table width="80" border="0">
                                    <tr>
                                        <td width="30" align="center"><img src="img/ico/devuelto/<?php echo $img_estado; ?>" width="30" height="30" /></td>
                                        <td width="20" align="center">&nbsp;</td>
                                        <td width="30" align="center"><a href="#" onClick="window.open('popups_manager.php?estado=101&p1=<?php echo $row['REPARACIONID'];?>', 'child', 'width=1000,status=no,directories=no,menubar=no,toolbar=no,scrollbars=no,location=no,resizable=no,titlebar=no'); return false;"><img src="img/ico/documento.png"   width="30" height="30" /></a></td>
                                    </tr>
                                </table>
                            </td>
                            <td width="200" align="center">
                            <?php
                                        $variables = array("prestamoid" => $row['REPARACIONID']);
                                        $estados = array("12" => "Resumen");
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

