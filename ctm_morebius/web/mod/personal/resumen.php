<?php
//ini_set('display_errors', 'on');
?>
<style>
/* Estilos generales */
.container {
    max-width: 1200px;
    margin: 0 auto;
    font-family: Arial, sans-serif;
}
.card {
    margin-bottom: 30px;
    border-radius: 10px;
    border: 1px solid #ccc;
}
.card-header {
    background: #f7f7f7;
    border-bottom: 1px solid #ccc;
    padding: 15px;
    font-weight: bold;
    font-size: 18px;
    text-align: center;
}
.card-body {
    padding: 15px;
    background-color: #fff;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}
.employee-data {
    flex: 1;
    margin-right: 20px;
}
.imagen-circular {
    text-align: right;
}
.imagen-circular img {
    width: 150px;
    height: 200px;
    object-fit: cover;
    border-radius: 50%;
}
.table {
    width: 100%;
    margin-bottom: 1rem;
    border-collapse: collapse;
}
.table th,
.table td {
    padding: 8px 12px;
    border: 1px solid #ccc;
    text-align: center;
}
.table thead.table-dark th {
    background-color: #343a40;
    color: #fff;
}
.resaltado-rojo {
    background-color: #ff9187;
}
.resaltado-verde {
    background-color: #6bffae;
}
.resaltado-amarillo {
    background-color: #f1d9ba;
}
</style>

<?php
$legajo = Enmascarar_post('legajo');

$row_empleado = [];
if ($legajo) {
    $sqlEmp = "
        SELECT EmpLegajo,
               EmpNombre,
               EmpCargoNom,
               EmpSucursalNom,
               EmpDocDNI,
               EmpDocCuil,
               EmpFchAlta,
               EmpEmail,
               EmpObraSocial
        FROM ctm_plantel
        WHERE EmpLegajo = '$legajo'
    ";
    $row_empleado = sql_do_array(sql_do_query($sqlEmp, '2', $usu), '2', $usu);
    $archivo = determinarImagen($row_empleado['EMPLEGAJO'], $row_empleado['EMPNOMBRE'], $servidor_img);
}



?>

<div class="container">
    <div class="card">
        <div class="card-header">DATOS DEL EMPLEADO</div>
        <div class="card-body">
            <div class="employee-data">
                <?php if (!empty($row_empleado)) { ?>
                    <p><strong>Legajo:</strong> <?php echo htmlspecialchars($row_empleado['EMPLEGAJO']); ?></p>
                    <p><strong>Nombre:</strong> <?php echo htmlspecialchars($row_empleado['EMPNOMBRE']); ?></p>
                    <p><strong>Cargo:</strong> <?php echo htmlspecialchars($row_empleado['EMPCARGONOM']); ?></p>
                    <p><strong>Sucursal:</strong> <?php echo htmlspecialchars($row_empleado['EMPSUCURSALNOM']); ?></p>
                    <p><strong>DNI:</strong> <?php echo htmlspecialchars($row_empleado['EMPDOCDNI']); ?></p>
                    <p><strong>CUIL:</strong> <?php echo htmlspecialchars($row_empleado['EMPDOCCUIL']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($row_empleado['EMPEMAIL']); ?></p>
                    <p><strong>Obra Social:</strong> <?php echo htmlspecialchars($row_empleado['EMPOBRASOCIAL']); ?></p>
                    <p><strong>Fecha de Alta:</strong> <?php echo htmlspecialchars($row_empleado['EMPFCHALTA']); ?></p>
                <?php } else { ?>
                    <p>No se encontró información del empleado (verifique Legajo).</p>
                <?php } ?>
            </div>
            <div class="imagen-circular">
                <?php if (!empty($row_empleado)) { ?>
                    <img src="<?php echo $archivo; ?>" alt="Imagen de <?php echo htmlspecialchars($row_empleado['EMPNOMBRE']); ?>">
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<?php

if ($legajo) {
    $sqlNotebook = "
        SELECT nro_art, fecha, tipo_art, detalle, CelPrincipal, de_baja
        FROM articulos_con
        WHERE tipo_art = '3' AND legajo_art = '$legajo'
        ORDER BY de_baja ASC, nro_art DESC;
    ";
    mostrarArticulosSeccion("Notebook del usuario", $sqlNotebook, '2', $usu, $row_empleado);

    $sqlCelulares = "
        SELECT nro_art, fecha, tipo_art, detalle, CelPrincipal, TipCel, de_baja
        FROM articulos_con
        WHERE tipo_art = '1' AND tipcel = '2' AND legajo_art = '$legajo'
        ORDER BY de_baja ASC, nro_art DESC;
    ";
    mostrarArticulosSeccion("Celulares del usuario", $sqlCelulares, '2', $usu, $row_empleado);

    $sqlLineas = "
        SELECT nro_art, fecha, tipo_art, detalle, CelPrincipal, TipCel, de_baja
        FROM articulos_con
        WHERE tipo_art = '1' AND tipcel = '3' AND legajo_art = '$legajo'
        ORDER BY de_baja ASC, nro_art DESC;
    ";
    mostrarArticulosSeccion("Líneas del usuario", $sqlLineas, '2', $usu, $row_empleado);
}
?>
