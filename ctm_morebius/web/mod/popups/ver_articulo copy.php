<?php
//ini_set( 'display_errors', 'on' );
//var_dump($_POST);

/**
 * 
  INSERT INTO more_menus (id, opcion, estado, direccion, submenu, descripcion, menu, nivacc, tipo) VALUES (111, 'POPUP', '0', 'web/mod/popups/ver_articulo.php', '24', 'POPUP', '3', 9, 9);
 *
 */

$nro_art   = Enmascarar_post('nro_art');
if(!$nro_art) $nro_art = Enmascarar_get('nro_art');
$sector    = Enmascarar_post('sector'); 
if(!$sector) $sector = Enmascarar_get('sector');
$tipo_art  = Enmascarar_post('tipo_art');
if(!$tipo_art) $tipo_art = Enmascarar_get('tipo_art');
$legajo_art= Enmascarar_post('legajo_art');
if(!$legajo_art) $legajo_art = Enmascarar_get('legajo_art'); 
$TipCel    = Enmascarar_post('TipCel');
if(!$TipCel) $TipCel = Enmascarar_get('TipCel');
$CodigoInve    = Enmascarar_post('CodigoInve');
if(!$CodigoInve) $CodigoInve = Enmascarar_get('CodigoInve');

$CelPrincipal    = Enmascarar_post('CelPrincipal');
if(!$CelPrincipal) $CelPrincipal = Enmascarar_get('CelPrincipal');
$devuelto    = Enmascarar_post('devuelto');
if(!$devuelto) $devuelto = Enmascarar_get('devuelto');
$DE_BAJA    = Enmascarar_post('DE_BAJA');
if(!$DE_BAJA) $DE_BAJA = Enmascarar_get('DE_BAJA');

if ($nro_art) {
    $row = obtenerArticulo($nro_art, $usu);
}

?>
  <style>
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
  <div class="container">
    <h1 class="page-title">Detalles del Artículo</h1>  
      <!-- Sección Identificador -->
      <fieldset>
        <legend>Identificador del artículo</legend>
        <div class="form-group">
          <label for="nro_art">Número de artículo:</label>
          <small class="field-description">El identificador único asignado por el sistema.</small>
          <?php if($nro_art) { ?>
            <input type="text" name="nro_art" id="nro_art" value="<?php echo $row['NRO_ART']; ?>" readonly class="readonly"/>
          <?php } else { ?>
            <input type="text" name="nro_art" id="nro_art" value="No se puede asignar" disabled />
          <?php } ?>
        </div>
      </fieldset>      
      <!-- Sección Asignación -->
      <fieldset>
        <legend>Asignación del equipo</legend>
        <div class="form-group">
          <label for="legajo_art">Datos del usuario:</label>
          <small class="field-description">Datos del usuario designado acargo del articulo.</small>
          <?php
              $legajo_art_c = (!$legajo_art) ? $row['LEGAJO_ART'] : $legajo_art;
              $query = "SELECT EmpLegajo AS id,  EmpNombre AS nombre
                          FROM ctm_plantel WHERE EmpLegajo IS NOT NULL ORDER BY EmpNombre;";
              echo ver_inf_combo('legajo_art', $query, '2', $usu, $legajo_art_c);
              $ubicacion = getUbicacionCentroCosto($legajo_art_c, $usu);
          ?>      
        </div>
        <div class="form-group">
          <label for="sector">Sector asignado:</label>
          <small class="field-description">Área o departamento al que pertenece el artículo.</small>
          <?php
            echo ver_inf_herr('sector', 'ctm_centroscosto', '2', $usu, 'CENTROCOSTO', 'CENTROCOSTONOM', $ubicacion['CENTROCOSTO']);
          ?>
        </div>
        <div class="form-group">
          <label for="UbicaId">Ubicación del equipo:</label>
          <small class="field-description">Identificador de la ubicación física del artículo.</small>
          <?php echo ver_inf_herr('UbicaId', 'artcelubi', '2', $usu, 'UBICAID', 'UBICADET', $ubicacion['UBICAID']); ?>
        </div>
      </fieldset>      
      <!-- Sección Descripción Estática -->
      <fieldset>
        <legend>Descripción estática del equipo</legend>
        <div class="form-group">
          <label for="tipo_art">Tipo de artículo:</label>
          <small class="field-description">Ejemplo: Celular, Notebook, etc.</small>
          <?php 
            $tipo_art_c = (!$tipo_art) ? $row['TIPO_ART'] : $tipo_art;
            echo ver_inf_herr('tipo_art', 'articulos_tipos', '2', $usu, 'TIPO_ART', 'DETALLE', $tipo_art_c);
          ?>
        </div>
        <?php        
          //echo "<h2>$tipo_art_c</h2>";
          if($tipo_art_c =='3'){
                ?>
                <div class="form-group">
                  <label for="legajo_art">Codigo del dispositivo:</label>
                  <small class="field-description">Codigo del inventario del dispositivo.</small>
                  <?php
                      if(!$CodigoInve){
                          $query0 = "SELECT UPPER(CONCAT(ac.detalle, ' ', ac.referencia, ' ', ac.nota)) AS detalles
                                        FROM articulos_con ac
                                      WHERE ac.legajo_art = '$legajo_art_c'
                                        AND ac.tipo_art = '$tipo_art_c'
                                        AND ac.nro_art = '$nro_art';";
                          $result0 = sql_do_query($query0, '2', $usu);
                          $datos0 = sql_do_array($result0, '2', $usu);
                          //echo "<h1>-->".$datos0['DETALLES']."<--</h1>";
                          $pnnnnnot = buscarCodigoInventario($datos0['DETALLES']);
                          $CodigoInve_c = $pnnnnnot;
                      }else{
                          $CodigoInve_c = $CodigoInve;
                      }
                      //echo "<h1>-->$CodigoInve_c<--</h1>";
                      $query = "SELECT i.codigo AS id, i.codigo AS nombre FROM ctm_inventario i WHERE i.producto = 'PC' ORDER BY i.id DESC;";
                      echo ver_inf_combo('CodigoInve', $query, '2', $usu, $CodigoInve_c);
                      //$producto = getArticulos($CodigoInve_c, $usu);
                      //var_dump($producto);
                      $referencias = ($row['REFERENCIA']);
                      $detalle = ($row['DETALLE']);                      
                      //var_dump($referencias);
                  ?>      
                </div>
                <?php    
          }
        ?>
        <div class="form-group">
          <label for="referencia">Referencia:</label>
          <small class="field-description">Código o referencia alterna del artículo.</small>
          <?php $referencias_c = (!$referencias) ? $row['REFERENCIA'] : $referencias; ?>
          <?php echo verJson($referencias_c); ?>
        </div>      
        <div class="form-group">
          <label for="detalle">Detalle:</label>
          <small class="field-description">Descripción breve de las características del artículo.</small>
          <?php $detalle_c = (!$detalle) ? $row['DETALLE'] :$detalle; ?>
          <?php echo verJson($detalle_c); ?>
        </div>
        <div class="form-group">
          <label for="MARCA">Marca del equipo:</label>
          <small class="field-description">Marca del producto.</small>
          <?php 
              echo ver_inf_herr('marca', 'artimarcas', '2', $usu, 'MARCAID', 'DETMARCA', $row['MARCA']);
          ?>
        </div>      
        <div class="form-group">
          <label for="MODELO">Modelo del equipo:</label>
          <small class="field-description">Modelo del dispositivo.</small>
          <?php $modelo_c = (!$row['MODELO']) ? $modelo : $row['MODELO']; ?>
          <?php echo verValorBonito($modelo_c) ; ?>
        </div>      
        <div class="form-group">
          <label for="NSERIE">Número de serie:</label>
          <small class="field-description">Número de serie único del producto.</small>
          <?php $nserie_c = (!$row['NSERIE']) ? $nserie : $row['NSERIE']; ?>
          <?php echo verValorBonito($nserie_c) ; ?>
        </div>
        <div class="form-group">
          <label for="fecha">Fecha de alta:</label>
          <small class="field-description">Fecha de registro del artículo.</small>
          <?php $fecha_c = ($row['FECHA']) ? substr($row['FECHA'], 0, 10) : substr($hoy, 0, 10); ?>
          <?php echo verValorBonito(enmascararFecha($fecha_c)) ; ?>
        </div>
        <div class="form-group">
          <label for="GARANTIA_VENCE">Vencimiento de la garantía:</label>
          <small class="field-description">Fecha en que vence la garantía del producto.</small>
          <?php 
              $dos_anios =  substr(($row['GARANTIA_VENCE']) ? substr($row['GARANTIA_VENCE'], 0, 10) : substr(obtenerFechaGarantia($hoy), 0, 10), 0, 10); 
          ?>
          <?php echo verValorBonito(enmascararFecha($dos_anios)) ; ?>
        </div>
      </fieldset>      
      <!-- Sección Descripción Dinámica -->
      <fieldset>
        <legend>Descripción dinámica del equipo</legend>
        <div class="form-group">
          <label for="CelPrincipal">Equipo principal del usuario:</label>
          <small class="field-description">En verde para indicar que el equipo esta asignado para uso personal y no de cuadrilla o sector.</small>
          <?php 
            $params = [
                'campo'   => 'CelPrincipal',
                'valor'   => isset($CelPrincipal) ? $CelPrincipal :  $producto['CELPRINCIPAL'],
                'nro_art' => isset($row['nro_art']) ? $row['nro_art'] : '',
                'legajo'  => isset($row['legajo_art']) ? $row['legajo_art'] : ''
            ];
            echo dibujar_switch_field($params);
          ?>
        </div>
        <div class="form-group">
          <label for="devuelto">Equipo devuelto:</label>
          <small class="field-description">Indique si el artículo ha sido devuelto.</small>
          <?php 
            $params = [
                'campo'   => 'devuelto',
                'valor'   => isset($devuelto) ? $devuelto : $producto['DEVUELTO'],
                'nro_art' => isset($row['nro_art']) ? $row['nro_art'] : '',
                'legajo'  => isset($row['legajo_art']) ? $row['legajo_art'] : ''
            ];
            echo dibujar_switch_field($params);
          ?>
        </div>
        <div class="form-group">
          <label for="DE_BAJA">Equipo dado de baja:</label>
          <small class="field-description">Estado del artículo (activo o de baja).</small>
          <?php 
            $params = [
                'campo'   => 'DE_BAJA',
                'valor'   => isset($DE_BAJA) ? $DE_BAJA : $producto['DE_BAJA'],
                'nro_art' => isset($row['nro_art']) ? $row['nro_art'] : '',
                'legajo'  => isset($row['legajo_art']) ? $row['legajo_art'] : ''
            ];
            echo dibujar_switch_field($params);
          ?>
        </div>
        <div class="form-group">
          <label for="nota">Nota:</label>
          <small class="field-description">Observaciones o comentarios adicionales.</small>
          <?php $nota_c = (!$row['NOTA']) ? $producto['NOTA'] : $row['NOTA']; ?>
          <textarea name="nota" id="nota" rows="3"><?php echo $nota_c; ?></textarea>
        </div>
      </fieldset>      
      <!-- Sección adicional para tipo_art = 1 -->
      <?php if($tipo_art=='1'): ?>
      <fieldset>
        <legend>Información adicional para líneas telefónicas</legend>
        <div class="form-group">
          <label for="Nro_Linea">Número de línea telefónica:</label>
          <small class="field-description">Ingrese el número asignado a la línea.</small>
          <input type="text" name="Nro_Linea" id="Nro_Linea" value="<?php echo $row['NRO_LINEA']; ?>" />
        </div>
        <div class="form-group">
          <label for="Pais_Linea">País de la línea:</label>
          <small class="field-description">Indique el país de procedencia o asignación de la línea.</small>
          <?php echo ver_combo_herr('Pais_Linea', 'more_paises', '1', $usu, 'PAISID', 'DESCRIPCION', $row['PAIS_LINEA']); ?>
        </div>
        <div class="form-group">
          <label for="TipCel">Tipo de celular:</label>
          <small class="field-description">Especifique el tipo (ej. Smartphone, Básico, etc.).</small>
          <?php 
            $TipCel_c = (!$TipCel) ? $row['TIPCEL'] : $TipCel;
            echo ver_combo_herr('TipCel', 'artipcel', '2', $usu, 'TIPCEL', 'TIPCELDET', $TipCel_c);
          ?>
        </div>
        <div class="form-group">
          <label for="PIN">PIN:</label>
          <small class="field-description">Código PIN del dispositivo, si aplica.</small>
          <input type="text" name="PIN" id="PIN" value="<?php echo $row['PIN']; ?>" />
        </div>
        <div class="form-group">
          <label for="PUK">PUK:</label>
          <small class="field-description">Código PUK para desbloqueo, si corresponde.</small>
          <input type="text" name="PUK" id="PUK" value="<?php echo $row['PUK']; ?>" />
        </div>
        <div class="form-group">
          <label for="IMEI">IMEI:</label>
          <small class="field-description">Número IMEI del dispositivo.</small>
          <input type="text" name="IMEI" id="IMEI" value="<?php echo $row['IMEI']; ?>" />
        </div>
        <div class="form-group">
          <label for="PLANC">Plan Contratado:</label>
          <small class="field-description">Plan o configuración asignada al artículo.</small>
          <?php echo ver_combo_herr('PLANC', 'artiplanes', '2', $usu, 'IDPLAN', 'PLANDETALLE', $row['PLANC']); ?>
        </div>
        <?php if($TipCel=='4' or $TipCel=='6'): ?>
          <div class="form-group">
            <label for="NSERIE_MODEM">Número de Serie Modem:</label>
            <small class="field-description">Número de serie del módem, si corresponde.</small>
            <input type="text" name="NSERIE_MODEM" id="NSERIE_MODEM" value="<?php echo $row['NSERIE_MODEM']; ?>" />
          </div>
          <div class="form-group">
            <label for="ESTACION_TEL">Estación Telefónica:</label>
            <small class="field-description">Código o nombre de la estación asignada.</small>
            <input type="text" name="ESTACION_TEL" id="ESTACION_TEL" value="<?php echo $row['ESTACION_TEL']; ?>" />
          </div>
          <div class="form-group">
            <label for="ArtPlan">Plan del Artículo:</label>
            <small class="field-description">Plan o configuración especial asociada al artículo.</small>
            <?php echo ver_combo_herr('ArtPlan', 'artiplanes', '2', $usu, 'IDPLAN', 'PLANDETALLE', $row['ARTPLAN']); ?>
          </div>
        <?php endif; ?>
      </fieldset>
      <?php endif; ?>
  </div>