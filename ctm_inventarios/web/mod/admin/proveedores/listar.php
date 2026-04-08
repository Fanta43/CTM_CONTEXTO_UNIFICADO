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
    <strong class="fs-3">Proveedores</strong>
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
                <th>Nombre</th>
                <th>Direcci&oacute;n</th>
                <th>Telefono</th>
                <th>Estado</th>     
                <th>Opciones</th>
            </tr>
        </thead>
        <tbody>
                <?php
                         
                   $sql="SELECT a.proveedorid,
                                a.nombre, 
                                a.direccion, 
                                a.telefono, 
                                a.estadoid, 
                                a.auditoria_usu, 
                                a.auditoria_fecha 
                    FROM inve_proveedores a
                   WHERE a.estadoid = '1'
                     AND (UPPER(a.proveedorid) like UPPER('$buscar%') 
                   	  OR UPPER(a.nombre) like UPPER('$buscar%')
                   	  OR UPPER(a.telefono) like UPPER('$buscar%')
                      OR UPPER(a.auditoria_usu) like UPPER('$buscar%'))
		ORDER BY a.estadoid, a.nombre, a.proveedorid ASC ";
                //echo $sql;
                $result = sql_do_query($sql,'1',$usu);
                $n=0;
                while($row=sql_do_array($result)) {
                        $n++;
                        
                        if($row['ESTADOID']=='1'){
                            $img_estado = 'si.gif';
                        }else{
                            $img_estado = 'no.gif'; 
                        }
                ?>
                        <tr>
                            <td width="30" align="center"><?php echo $n;?>&nbsp;</td>
                            <td width="30" align="center"><?php echo $row['PROVEEDORID'];?>&nbsp;</td>
                            <td width="100" align="center"><?php echo $row['NOMBRE'];?>&nbsp;</td>
                            <td width="100" align="center"><?php echo $row['DIRECCION'];?>&nbsp;</td>
                            <td width="100" align="center"><?php echo $row['TELEFONO'];?>&nbsp;</td>
                            <td align="center" width="30"><img src="img/ico/<?php echo $img_estado; ?>" width="30" height="30" /></td>
                            <td width="200" align="center">
                            <?php
                                        $variables = array("proveedorid" => $row['PROVEEDORID']);
                                        $estados = array("38" => "Resumen");
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

