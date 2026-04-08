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
    <strong class="fs-3">Celulares Entregados</strong>
</div>
<div class="row flex-column m-2">
    <form id="form1" name="form1" method="post" action="">
        <input type="text" class="form-control" name="buscar" id="buscar" placeholder="Filtro de Búsqueda" onchange="submit();" />
    </form>
</div>
<div class="row flex-column m-2 overflow-auto" style="height: 100vh;">
    <table class="table table-bordered table-striped" style="background-color: white; min-width:1200px;">
        <thead>
            <tr>
                <th>Nro Artículo</th>
                <th>Fecha</th>
                <th>Detalle</th>
                <th>Sector</th>
                <th>Legajo Art</th>
                <th>Nota</th>
                <th>Referencia</th>
                <th>Nro Línea</th>
                <th>País Línea</th>
                <th>UbicaId</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>N° Serie</th>
                <th>IMEI</th>
                <th>En Intranet</th>

            </tr>
        </thead>
        <tbody>
            <?php
            $sql = " SELECT c.nro_art, 
                            c.fecha, 
                            c.detalle, 
                            c.sector, 
                            c.legajo_art, 
                            c.nota, 
                            c.referencia, 
                            c.Nro_Linea, 
                            c.Pais_Linea, 
                            c.UbicaId, 
                            u.UbicaDet,
                            c.MARCA, 
                            m.detmarca,
                            c.MODELO, 
                            c.NSERIE, 
                            c.IMEI,  
                            c.CelPrincipal 
                       FROM articulos_con c
                 INNER JOIN artcelubi u ON c.UbicaId = u.UbicaId
                 INNER JOIN artimarcas m ON c.MARCA = m.marcaid
                      WHERE c.tipo_art = '1' 
                        AND c.TipCel = '2' 
                        AND c.devuelto = '0' 
                        AND c.DE_BAJA = '0' 
                   ORDER BY c.nro_art DESC;";
                   // [PAYWEBCTM].[dbo].[ARTICULOS_CON] 
            $result = sql_do_query($sql, '2', $usu);
            while ($row = sql_do_array($result, '2', $usu)) {
            ?>
                <tr>
                    <td><?= htmlspecialchars($row['NRO_ART']) ?></td>
                    <td><?= enmascararFecha($row['FECHA']) ?></td>
                    <td><?= htmlspecialchars($row['DETALLE']) ?></td>
                    <td><?= htmlspecialchars($row['SECTOR']) ?></td>
                    <td>
                    <?php
                    $usuarioPlantel = obtenerUsuarioPlantel($row['LEGAJO_ART'], '2', $usu);
                    echo $usuarioPlantel['EMPNOMBRE'] .' ('.$usuarioPlantel['EMPLEGAJO'].')';
                    ?>
                    </td>
                    <td><?= htmlspecialchars($row['NOTA']) ?></td>
                    <td><?= htmlspecialchars($row['REFERENCIA']) ?></td>
                    <td><?= htmlspecialchars($row['NRO_LINEA']) ?></td>
                    <td><?= htmlspecialchars($row['PAIS_LINEA']) ?></td>
                    <td><?= htmlspecialchars($row['UBICADET']) ?></td>
                    <td><?= htmlspecialchars($row['DETMARCA']) ?></td>
                    <td><?= htmlspecialchars($row['MODELO']) ?></td>
                    <td><?= htmlspecialchars($row['NSERIE']) ?></td>
                    <td><?= htmlspecialchars($row['IMEI']) ?></td>
                    <td>
                        <?php
                            if($row['CELPRINCIPAL']=='1') { 
                                echo '<span style="color: green;">Sí</span>';
                            } else {
                                echo '<span style="color: red;">No</span>';
                            }
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