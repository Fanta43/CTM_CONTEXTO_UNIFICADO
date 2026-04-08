<?php
//ini_set('display_errors', 'on');
// Variables iniciales
$nro_art    = Enmascarar_post('nro_art') ?: Enmascarar_get('nro_art');
$sector     = Enmascarar_post('sector') ?: Enmascarar_get('sector');
$legajo_art = Enmascarar_post('legajo_art') ?: Enmascarar_get('legajo_art');
$hoy        = date('Y-m-d');

// Obtener datos del artículo
$row = [];
if ($nro_art) {
    $row = obtenerArticulo($nro_art, $usu);
    if (!$row) {
        echo '<p>Error: No se encontró información del artículo.</p>';
        exit;
    }
}

//var_dump($row);

$registrar_baja    = Enmascarar_post('registrar_baja');

if ($registrar_baja) {
    $row_c = insertArticuloHis($_POST, $usu);
    if ($row_c) {
                  echo '<script>swal({
                    title: "Buen Trabajo!",
                    text: "Muchas gracias por registrar la baja!",
                    icon: "success",
                    dangerMode: false,
                    buttons: {
                        confirm: {text: "Continuar", value: true},
                    },
                    })
                    .then((value) => {
                        window.close();
                    })    
                    </script>';
        exit;
    }
}



// Asignar valores predeterminados
$nota_c = str_replace('  ', '', $row['NOTA']) ?? 'Notas de baja del equipo, conserva la línea, solicita cambio de titularidad, etc.';
$nota_d = $row['NOTA_DEVOLUCION'] ?? 'Sin devolución o registrar novedades del equipo devuelto.';
?>

<style>
/* Estilos generales */
:root {
    --color-fondo: #f5f5f5;
    --color-contenedor: #fff;
    --color-texto: #333;
    --color-borde: #ccc;
    --color-primario: #3498db;
    --color-primario-hover: #2980b9;
    --color-subtexto: #555;
    --color-descripcion: #888;
    --color-input-focus: #66afe9;
}
body {
    margin: 0;
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    background: var(--color-fondo);
    color: var(--color-texto);
    line-height: 1.6;
}
.container {
    max-width: 800px;
    margin: 40px auto;
    background: var(--color-contenedor);
    border-radius: 8px;
    padding: 30px 40px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}
h1.page-title {
    text-align: center;
    margin-bottom: 30px;
    font-size: 1.8em;
}
fieldset {
    border: 1px solid var(--color-borde);
    border-radius: 6px;
    margin-bottom: 25px;
    padding: 20px;
}
legend {
    font-weight: bold;
    padding: 0 10px;
    font-size: 1.1em;
    color: var(--color-subtexto);
}
.form-group {
    margin-bottom: 20px;
}
label {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
    color: var(--color-subtexto);
}
small.field-description {
    display: block;
    font-size: 0.85em;
    color: var(--color-descripcion);
    margin-bottom: 5px;
}
input[type="text"],
input[type="date"],
textarea,
select {
    width: 100%;
    padding: 10px;
    border: 1px solid var(--color-borde);
    border-radius: 4px;
    font-size: 1em;
    box-sizing: border-box;
    transition: border 0.3s ease;
}
input[type="text"]:focus,
input[type="date"]:focus,
textarea:focus,
select:focus {
    border-color: var(--color-input-focus);
    outline: none;
}
.readonly {
    background-color: #e9ecef;
}
.btn {
    display: block;
    width: 100%;
    background: var(--color-primario);
    color: #fff;
    padding: 12px;
    border: none;
    border-radius: 4px;
    font-size: 1.1em;
    cursor: pointer;
    text-align: center;
    transition: background 0.3s ease;
    margin-top: 20px;
}
.btn:hover {
    background: var(--color-primario-hover);
}
@media (max-width: 600px) {
    .container {
        padding: 20px;
    }
}
</style>

<form method="post" action="" name="form" id="form">
    <div class="container">
        <h1 class="page-title">Historial del Artículo</h1>
        <fieldset>
            <legend>Datos del Historial del Artículo</legend>
            <div class="form-group">
                <label for="nro_art">Número de Artículo:</label>
                <input type="text" name="nro_art" id="nro_art" value="<?= htmlspecialchars($row['NRO_ART'] ?? $nro_art) ?>" readonly class="readonly" />
            </div>
            <div class="form-group">
                <label for="fecha_des">Fecha Desde:</label>
                <input type="date" name="fecha_des" id="fecha_des" value="<?= htmlspecialchars(substr($row['FECHA'] ?? $hoy, 0, 10)) ?>" />
            </div>
            <div class="form-group">
                <label for="fecha_has">Fecha Hasta:</label>
                <input type="date" name="fecha_has" id="fecha_has" value="<?= htmlspecialchars(substr($hoy, 0, 10)) ?>" />
            </div>
            <div class="form-group">
                <label for="sector">Sector:</label>
                <?= imp_combo_herr_sc('sector', 'ctm_centroscosto', '2', $usu, 'CENTROCOSTO', 'CENTROCOSTONOM', htmlspecialchars($row['SECTOR'] ?? '')) ?>
            </div>
            <div class="form-group">
                <label for="legajo_art">Legajo del Usuario:</label>
                <?php 
                    $query_mysql = "SELECT EmpLegajo AS id, CONCAT(EmpNombre, ' ', EmpLegajo) AS nombre FROM ctm_plantel ORDER BY EmpNombre;";
                    $query = "SELECT EmpLegajo AS id, (ISNULL(EmpNombre, '') + ' ' + CAST(EmpLegajo AS varchar)) AS nombre FROM ctm_plantel ORDER BY EmpNombre;";
                    //echo $query;
                    imp_combo_w_oc('legajo_art', $query, '2', $usu, htmlspecialchars($row['LEGAJO_ART'] ?? $legajo_art)) 
                ?>
            </div>
            <div class="form-group">
                <label for="nota">Nota:</label>
                <textarea name="nota" id="nota" rows="4"><?php echo $nota_c; ?></textarea>
            </div>
            <div class="form-group">
                <label for="nota_devolucion">Nota de devolución:</label>
                <textarea name="nota_devolucion" id="nota_devolucion" rows="4"><?php echo $nota_d; ?></textarea>
            </div>
            <div class="form-group">
                <label for="pais_linea">País de Línea:</label>
                <?= imp_combo_herr_sc('pais_linea', 'more_paises', '1', $usu, 'PAISID', 'DESCRIPCION', $row['PAIS_LINEA']) ?>
            </div>
        </fieldset>
        <input type="hidden" name="usuario" id="usuario"  value="<?php echo $usu; ?>" />
        <input type="submit" name="registrar_baja" id="registrar_baja" class="btn" value="Registrar Baja" />
    </div>
</form>