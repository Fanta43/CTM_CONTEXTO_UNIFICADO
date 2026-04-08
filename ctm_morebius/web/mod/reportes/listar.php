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
    <strong class="fs-3">Personal</strong>
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
                <th colspan="4">Usuario</th>
                <th colspan="2">Notebook</th>
                <th colspan="3">Celular</th>
                <th colspan="2">Opciones</th>
            </tr>
            <tr>
                <th>Foto</th>
                <th>Legajo</th>
                <th>Nombre y Apellido</th>
                <th>Cargo y Categoria</th>
                <th>Principal</th>
                <th>Cuadrilla</th>
                <th>Principal</th>
                <th>Linea</th>
                <th>Cuadrilla</th>
                <th>Estado</th>
                <th>Opciones</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT a.EmpLegajo, a.EmpNombre, a.EmpCargoNom, a.SITUACION_DE_REVISTA, a.EmpCategoria
                    FROM ctm_plantel a
                    WHERE (UPPER(a.EmpLegajo) LIKE UPPER('%$buscar%')                     
                           OR UPPER(a.EmpCategoria) LIKE UPPER('%$buscar%')
                           OR UPPER(a.EmpNombre) LIKE UPPER('%$buscar%')
                           OR UPPER(a.EmpCentroCostoNom) LIKE UPPER('%$buscar%')
                           OR UPPER(a.EmpLugarFisicoNom) LIKE UPPER('%$buscar%')
                           OR UPPER(a.EmpEmail) LIKE UPPER('%$buscar%')
                           OR UPPER(a.EmpCargoNom) LIKE UPPER('%$buscar%'))
                    ORDER BY a.EmpNombre ASC;";
            $result = sql_do_query($sql, '2', $usu);
            $n = 0;
            while ($row = sql_do_array($result)) {
                $n++;
                $img_estado = ($row['SITUACION_DE_REVISTA'] == 'ACTIVO') ? 'si.gif' : 'no.gif';
                $archivo = determinarImagen($row['EMPLEGAJO'], $row['EMPNOMBRE'], $servidor_img);
                $img_estado_laptop = legajo_laptop($row['EMPLEGAJO'], $usu, '2');
                $img_estado_laptop_cuadrilla = legajo_laptop_cuadrilla($row['EMPLEGAJO'], $usu, '2');
                $img_estado_celular = legajo_celular($row['EMPLEGAJO'], $usu, '2');
                $img_estado_linea = legajo_linea($row['EMPLEGAJO'], $usu, '2');
                $img_estado_cuadrilla = legajo_celular_cuadrilla($row['EMPLEGAJO'], $usu, '2');
            ?>
                <tr>
                    <td width="50">
                        <div class="imagen-circular">
                            <img src="<?php echo htmlspecialchars($archivo); ?>" alt="Imagen de <?php echo htmlspecialchars($row['EMPNOMBRE']); ?>" width="50" height="50">
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($row['EMPLEGAJO']); ?>&nbsp;</td>
                    <td><?php echo htmlspecialchars($row['EMPNOMBRE']); ?>&nbsp;</td>
                    <td width="400"><?php echo htmlspecialchars($row['EMPCARGONOM'])." (".$row['EMPCATEGORIA'].")"; ?>&nbsp;</td>
                    <td width="84"><img src="img/ico/<?php echo htmlspecialchars($img_estado_laptop['img']); ?>" width="30" height="30" alt="<?php echo 'Desde: ' . htmlspecialchars($img_estado_laptop['fecha']); ?>"></td>
                    <td width="84"><img src="img/ico/<?php echo htmlspecialchars($img_estado_laptop_cuadrilla['img']); ?>" width="30" height="30" alt="<?php echo 'Desde: ' . htmlspecialchars($img_estado_laptop_cuadrilla['fecha']); ?>"></td>
                    <td width="84"><img src="img/ico/<?php echo htmlspecialchars($img_estado_celular['img']); ?>" width="30" height="30" alt="<?php echo 'Desde: ' . htmlspecialchars($img_estado_celular['fecha']); ?>"></td>
                    <td width="84"><img src="img/ico/<?php echo htmlspecialchars($img_estado_linea['img']); ?>" width="30" height="30" alt="<?php echo 'Desde: ' . htmlspecialchars($img_estado_linea['fecha']); ?>"></td>
                    <td width="84"><img src="img/ico/<?php echo htmlspecialchars($img_estado_cuadrilla['img']); ?>" width="30" height="30" alt="<?php echo 'Desde: ' . htmlspecialchars($img_estado_cuadrilla['fecha']); ?>"></td>
                    <td width="84"><img src="img/ico/<?php echo htmlspecialchars($img_estado); ?>" width="30" height="30"></td>
                    <td>
                        <?php
                        $variables = array("legajo" => $row['EMPLEGAJO']);
                        $estados = array("26" => "Resumen", "261" => "Editar");
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