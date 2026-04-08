<div class="h-100 w-100">
<?php
//ini_set( 'display_errors', 'on' );
/**
 * RECIBO LAS VARIABLES PARA OPERAR
**/

//var_dump($_SESSION);

$baja = Enmascarar_post('baja');
$buscar = Enmascarar_post('buscar');


?>
<div class="row flex-column align-items-start m-2">
    <strong class="fs-3">Script de Migraci&oacute;n</strong>
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
                <th>Id</th>
                <th>Descripci&oacute;n</th>
                <th>Automatico</th>
                <th>Parametros</th>
                <th>Ejecuci&oacute;n</th>
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
                         
            $sql="SELECT ms.migracionid, 
                         ms.descripcion, 
                         ms.automatico, 
                         ms.parametros,
                         ms.ejecucion_usu,
                         ms.ejecucion_fecha,
                         ms.estadoid, 
                         ms.auditoria_usu, 
                         ms.auditoria_fecha 
                    FROM inve_migracion_script ms ";
                //echo $sql;
                $result = sql_do_query($sql,'1',$usu);
                $n=0;
                while($row=sql_do_array($result)) {
                        $n++;
                        if($row['ESTADOID']!='1'){
                            $img_estado = 'no.gif';
                        }else{
                            $img_estado = 'si.gif'; 
                        }
                ?>
                        <tr>
                            <td width="30" align="center"><?php echo $row['MIGRACIONID'];?>&nbsp;</td>
                            <td width="100" align="center"><?php echo $row['DESCRIPCION'];?>&nbsp;</td>
                            <td width="100" align="center"><?php echo $row['AUTOMATICO'];?>&nbsp;</td>
                            <td width="100" align="rigth"><?php echo $row['PARAMETROS'];?>&nbsp;</td>
                            <td width="100" align="rigth"><?php echo $row['EJECUCION_USU']." - ".$row['EJECUCION_FECHA'];?>&nbsp;</td>
                            <td width="50" align="center"  valign="middle">
                                <table width="50" border="0">
                                    <tr>
                                        <td width="30" align="center"><img src="img/ico/<?php echo $img_estado; ?>" width="30" height="30" /></td>
                                    </tr>
                                </table>
                            </td>
                            <td width="50" align="center"  valign="middle">
                                <table width="50" border="0">
                                    <tr>
                                        <td width="30" align="center"><td width="30" align="center"><a href="#" onClick="window.open('web/int/script_manager.php?estado=<?php echo $row['MIGRACIONID'];?>&p1=<?php echo $row['PARAMETROS'];?>', 'child', 'width=1000,status=no,directories=no,menubar=no,toolbar=no,scrollbars=no,location=no,resizable=no,titlebar=no'); return false;"><img src="img/ico/documento.png"   width="30" height="30" /></a></td></td>
                                    </tr>
                                </table>
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

