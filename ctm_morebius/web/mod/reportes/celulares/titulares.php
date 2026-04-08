<?php
////ini_set( 'display_errors', 'on' );
?>
<style>
.imagen-circular {
  width: 40px;
  height: 60px;
  border-radius: 50%;
  overflow: hidden;
  display: flex;
  justify-content: center;
  align-items: center;
}
.table th, .table td {
  text-align: center;
  vertical-align: middle;
}
</style>

<div class="h-100 w-100">
<?php
$baja = Enmascarar_post('baja');
$buscar = Enmascarar_post('buscar');
?>
<div class="row flex-column align-items-start m-2">
    <strong class="fs-3">Titulares de equipos celulares</strong>
</div>
<div class="row flex-column m-2">
    <form id="form1" name="form1" method="post" action="">
        <input type="text" class="form-control" name="buscar" id="buscar" placeholder="Filtro de Búsqueda" onchange="submit();" />
    </form>
</div>
<div class="row flex-column m-2 overflow-auto" style="height: 100vh;">
    <table class="table table-bordered table-striped" style="background-color: white;">
        <thead>
            <tr>
                <th colspan="7">Usuario</th>
                <th colspan="4">Celular</th>
                <th colspan="1">Opciones</th>
            </tr>
            <tr>
                <th>Nro</th>
                <th>Foto</th>
                <th>Legajo</th>
                <th>Nombre y Apellido</th>
                <th>Categoria</th>
                <th>EMail</th>
                <th>Cargo y Categoria</th>
                <th>Linea Nro</th>
                <th>Fecha Fecha</th>
                <th>Equipo Marca y Modelo</th>
                <th>Equipo Fecha</th>

                <th>Opciones</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $legajos = obtenerLegajosAnios('2', $usu, '1');
            //var_dump($legajos);
            $lista_legajos = implode(',', $legajos);
            $sql = "SELECT a.EmpLegajo, a.EmpNombre, a.EmpEmail, a.EmpCargoNom, a.SITUACION_DE_REVISTA, a.EmpCategoria
                    FROM ctm_plantel a
                    WHERE (UPPER(a.EmpLegajo) LIKE UPPER('%$buscar%')                     
                           OR UPPER(a.EmpCategoria) LIKE UPPER('%$buscar%')
                           OR UPPER(a.EmpNombre) LIKE UPPER('%$buscar%')
                           OR UPPER(a.EmpCentroCostoNom) LIKE UPPER('%$buscar%')
                           OR UPPER(a.EmpLugarFisicoNom) LIKE UPPER('%$buscar%')
                           OR UPPER(a.EmpEmail) LIKE UPPER('%$buscar%')
                           OR UPPER(a.EmpCargoNom) LIKE UPPER('%$buscar%'))
                      AND  a.EmpLegajo IN ($lista_legajos)
                    ORDER BY a.EmpCategoria ASC;";
            //echo $sql;
            $result = sql_do_query($sql, '2', $usu);
            $n = 0;
            while ($row = sql_do_array($result, '2', $usu)) {
                $n++;
                $img_estado = ($row['SITUACION_DE_REVISTA'] == 'ACTIVO') ? 'si.gif' : 'no.gif';
                $archivo = determinarImagen($row['EMPLEGAJO'], $row['EMPNOMBRE'], $servidor_img);
                //$img_estado_laptop = legajo_laptop($row['EMPLEGAJO'], $usu, '2');
                //$img_estado_laptop_cuadrilla = legajo_laptop_cuadrilla($row['EMPLEGAJO'], $usu, '2');
                //$img_estado_celular = legajo_celular($row['EMPLEGAJO'], $usu, '2');
                //$img_estado_linea = legajo_linea($row['EMPLEGAJO'], $usu, '2');
                //$img_estado_cuadrilla = legajo_celular_cuadrilla($row['EMPLEGAJO'], $usu, '2');
            ?>
                <tr>
                <td width="50"><?php echo $n; ?></td>
                    <td width="50">
                        <div class="imagen-circular">
                            <img src="<?php echo htmlspecialchars($archivo); ?>" alt="Imagen de <?php echo htmlspecialchars($row['EMPNOMBRE']); ?>" width="50" height="50">
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($row['EMPLEGAJO']); ?>&nbsp;</td>
                    <td><?php echo htmlspecialchars($row['EMPNOMBRE']); ?>&nbsp;</td>
                    <td width="100"><?php echo $row['EMPCATEGORIA']; ?>&nbsp;</td>
                    <td width="400"><?php echo htmlspecialchars($row['EMPCARGONOM']); ?>&nbsp;</td>
                    <td width="400"><?php echo htmlspecialchars($row['EMPEMAIL']); ?>&nbsp;</td>
                    <td width="84">
                        <?php 
                            $dat = obtenerLegajosLinea('2',$usu,$row['EMPLEGAJO']);
                            echo $dat['NRO_LINEA']; 
                        ?>
                    </td>
                    <td width="84">
                        <?php 
                            $dat = obtenerLegajosLinea('2',$usu,$row['EMPLEGAJO']);
                            echo enmascararFecha($dat['FECHA']); 
                        ?>
                    </td>
                    <td width="84">
                        <?php 
                            $equi = obtenerLegajosEquipo('2',$usu,$row['EMPLEGAJO']);
                            echo $equi['MARCA'].' - '.$equi['MODELO']; 
                        ?>
                    </td>
                    <td width="84">
                        <?php 
                            echo enmascararFecha($equi['FECHA']); 
                        ?>
                    </td><td>
                        <?php
                        $variables = array("legajo" => $row['EMPLEGAJO']);
                        $estados = array("26" => "Resumen", "261" => "Editar");
                        //echo impSelectHerramienta($variables, $estados, $n);
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