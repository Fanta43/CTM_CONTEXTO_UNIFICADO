    <style>
        /* Clase personalizada para establecer la altura y el borde verde */
        .success-container {
            height: 20mm; /* Altura en milímetros */
            border: 1px solid green; /* Borde en verde */
        }
        
        .error-container {
            height: 20mm; /* Altura en milímetros */
            border: 1px solid red; /* Borde en rojo */
        }
    </style>


<div class="col-12 separar1 p-0 float-start bg-white">
    <div class="bg-white">
        <div class="p-3">
            <div class="col-12 pb-2 d-flex align-items-center">
                <img src="img/icon/person.png" class="me-2" style="height: 30px; width: 30px;">
                <h6 class="mb-0 align-self-center">Registros en OCS Inventory</h6>
            </div>
            <div class="col-12">
<br />               
             
<div class="table-responsive">
    
    
    
    
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th align="center">HARDWARE</th>
                <th align="center">SOFTWARE</th>
                <th align="center">USUARIOS</th>
                <th align="center">RED</th>
                <th align="center">CONTACTOS</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-left">
                    <strong>Hardware id:</strong> <?php echo $rowhardware['ID'];?><br>
                    <strong>Procesador:</strong> <?php echo $rowhardware['PROCESSORT'];?><br>
                    <strong>Memoria:</strong> <?php echo $rowhardware['MEMORY'];?><br>
                    <strong>SWAP:</strong> <?php echo $rowhardware['SWAP'];?><br>
                    <strong>UUID:</strong> <?php echo $rowhardware['UUID'];?><br>                    
                    <strong>Arquitectura:</strong> <?php echo $rowhardware['ARCH'];?><br>                   
                </td>
                <td class="text-left">
                    <strong>Nombre:</strong> <?php echo $rowhardware['NAME'];?><br>
                    <strong>Sistema Operativo:</strong> <?php echo $rowhardware['OSNAME'];?><br>
                    <strong>OS Clave:</strong> <?php echo $rowhardware['WINPRODKEY'];?><br>
                    <strong>OS Prod ID:</strong> <?php echo $rowhardware['WINPRODID'];?><br>
                </td>
                <td class="text-left">
                    <strong>Grupo:</strong> <?php echo $rowhardware['WORKGROUP'];?><br>
                    <strong>Usuario:</strong> <?php echo $rowhardware['USERID'];?><br>
                    <strong>Administrador:</strong> <?php echo $rowhardware['WINOWNER'];?><br>
                </td>
                <td>
                    <strong>IP:</strong> <?php echo $rowhardware['IPADDR'];?><br>
                    <strong>DNS:</strong> <?php echo $rowhardware['DNS'];?><br>
                    <strong>Gateway:</strong> <?php echo $rowhardware['DEFAULTGATEWAY'];?><br>
                    <strong>IPSRC:</strong> <?php echo $rowhardware['IPSRC'];?><br>
                </td>
                <td>
                    <strong>Ultimo dia:</strong> <?php echo $rowhardware['LASTDATE'];?><br>
                    <strong>Ultimo Contacto:</strong> <?php echo $rowhardware['LASTCOME'];?><br>
                    <strong>Calidad:</strong> <?php echo $rowhardware['QUALITY'];?><br>
                    <strong>Fidelidad:</strong> <?php echo $rowhardware['FIDELITY'];?><br>
                </td>
            </tr>
        </tbody>
    </table>
    
    
    
    
    
                </div>  
            </div>
        </div>
    </div>
</div>
</div>  

<div class="col-12 separar1 p-0 float-start mt-3 bg-white">
        <div class="accordion accordion-flush" id="accordion3">
<div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse3" aria-expanded="false" aria-controls="flush-collapse3">
                    <h6 class="m-0"><img src="img/icon/domicilio.png" class="me-2" style="height: 20px; width: 15px;">Monitores</h6>
                </button>
            </h2>
            <div id="flush-collapse3" class="accordion-collapse collapse" data-bs-parent="#accordion3">
                <div class="accordion-body">
                    <div class="col-12">

                        
<div class="table-responsive">
    
    
    
    
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th align="center">ORD</th>
                <th align="center">MARCA</th>
                <th align="center">MODELO</th>
                <th align="center">TIPO</th>
                <th align="center">NRO SERIE</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                        <?php
                        $sql = " SELECT id, 
                                        hardware_id, 
                                        manufacturer, 
                                        caption, 
                                        description, 
                                        type, 
                                        serial, 
                                        sistema_extid, 
                                        estadoid, 
                                        auditoria_usu, 
                                        auditoria_fecha 
                                   FROM ocs_monitors 
                                   WHERE hardware_id = '".$rowhardware['ID']."'";
                        //echo $sql;
                        $result = sql_do_query($sql, '2', $usu);
                        $n = 0;
                        while ($row = sql_do_array($result, '2', $usu)) {
                            $n++;
                        ?>
                <td class="align-middle text-center">
                    <?php echo $n;?>
                </td>
                <td class="text-left">
                    <?php echo $row['MANUFACTURER'];?>
                </td>
                <td class="text-left">
                    <?php echo "(".$row['CAPTION'].") ".$row['DESCRIPTION'];?>
                </td>
                <td class="text-left">
                    <?php echo $row['TYPE'];?>
                </td>
                <td class="text-left">
                    <?php echo $row['SERIAL'];?>
                </td>
            </tr>
                        <?php
                        }
                        ?>
            
        </tbody>
    </table>
    
    
    
    
    
                </div>   
                        
                        
                        
                        
                        
                        
                        
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    
<div class="col-12 separar1 p-0 float-start mt-3 bg-white">
        <div class="accordion accordion-flush" id="accordion4">
<div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse4" aria-expanded="false" aria-controls="flush-collapse4">
                    <h6 class="m-0"><img src="img/icon/domicilio.png" class="me-2" style="height: 20px; width: 15px;">Discos</h6>
                </button>
            </h2>
            <div id="flush-collapse4" class="accordion-collapse collapse" data-bs-parent="#accordion4">
                <div class="accordion-body">
                    <div class="col-12">
 
                        
                        
                        
                        
<div class="table-responsive">
    
    
    
    
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th align="center">ORD</th>
                <th align="center">TIPO</th>
                <th align="center">UNIDAD</th>
                <th align="center">ESPACIO</th>
                <th align="center">FECHA</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                        <?php
                        $sql = " SELECT id, 
                                        hardware_id, 
                                        letter, 
                                        type, 
                                        filesystem, 
                                        total, 
                                        free, 
                                        numfiles, 
                                        volumn,  
                                        createdate, 
                                        sistema_extid, 
                                        estadoid, 
                                        auditoria_usu, 
                                        auditoria_fecha 
                                   FROM ocs_drives 
                                  WHERE hardware_id = '".$rowhardware['ID']."' 
                               ORDER BY createdate DESC;";
                        //echo $sql;
                        $result = sql_do_query($sql, '2', $usu);
                        $n = 0;
                        while ($row = sql_do_array($result, '2', $usu)) {
                            //var_dump($row);
                            $n++;
                        ?>
                <td class="align-middle text-center">
                    <?php echo $n;?>
                </td>
                <td class="text-left">
                    <?php echo $row['TYPE'];?>
                </td>
                <td class="text-left">
                    <?php echo $row['LETTER'];?>
                </td>
                <td class="text-left">
                    <?php echo $row['FREE']." MB libres de (".$row['TOTAL']." MB)";?>
                </td>
                <td class="text-left">
                    <?php echo enmascararFecha($row['DATE']);?>
                </td>
            </tr>
                        <?php
                        }
                        ?>
            
        </tbody>
    </table>
    
    
    
    
    
                </div>   
                        
                        
                        
                        
                        
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="col-12 separar1 p-0 float-start mt-3 bg-white">
        <div class="accordion accordion-flush" id="accordion1">
<div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse1" aria-expanded="false" aria-controls="flush-collapse1">
                    <h6 class="m-0"><img src="img/icon/domicilio.png" class="me-2" style="height: 20px; width: 15px;">Red Visible</h6>
                </button>
            </h2>
            <div id="flush-collapse1" class="accordion-collapse collapse" data-bs-parent="#accordion1">
                <div class="accordion-body">
                    <div class="col-12">
                        
                        
                        
                        
                        
<div class="table-responsive">
    
    
    
    
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th align="center">ORD</th>
                <th align="center">IP</th>
                <th align="center">MAC</th>
                <th align="center">NOMBRE | GRUPO</th>
                <th align="center">FECHA</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                        <?php
                        $sql = " SELECT ip, 
                                        mac, 
                                        mask, 
                                        netid, 
                                        date, 
                                        name, 
                                        tag, 
                                        hardware_id, 
                                        sistema_extid, 
                                        estadoid, 
                                        auditoria_usu, 
                                        auditoria_fecha 
                                   FROM ocs_netmap 
                                  WHERE hardware_id = '".$rowhardware['ID']."' 
                               ORDER BY date DESC;";
                        //echo $sql;
                        $result = sql_do_query($sql, '2', $usu);
                        $n = 0;
                        while ($row = sql_do_array($result, '2', $usu)) {
                            $n++;
                        ?>
                <td class="align-middle text-center">
                    <?php echo $n;?>
                </td>
                <td class="text-left">
                    <?php echo $row['IP']." (".$row['MASK'].")";?>
                </td>
                <td class="text-left">
                    <?php echo $row['MAC'];?>
                </td>
                <td class="text-left">
                    <?php echo $row['NAME'];?>
                </td>
                <td class="text-left">
                    <?php echo enmascararFecha($row['DATE']);?>
                </td>
            </tr>
                        <?php
                        }
                        ?>
            
        </tbody>
    </table>
    
    
    
    
    
                </div>   
                        
                        
                        
                        
                        
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>