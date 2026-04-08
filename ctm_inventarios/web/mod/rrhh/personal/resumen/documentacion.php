<div class="col-12 separar1 p-0 float-start bg-white">
    <div class="bg-white">
        <div class="p-3">
            <div class="col-12 pb-2 d-flex align-items-center">
                <img src="img/icon/person.png" class="me-2" style="height: 30px; width: 30px;">
                <h6>Elementos del legajo institucional</h6>
            </div>
            <div class="col-12">
                
<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>ORD</th>
                <th>LETRA</th>
                <th>ELEMENTO</th>
                <th>ESTADO</th>
                <th>ARCHIVOS</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $sql="SELECT elemento_legajoid,
                         letra, 
                         descripcion,
                         archivos, 
                         estadoid, 
                         auditoria_usu, 
                         auditoria_fecha 
                    FROM doce_elementos_legajos 
                   WHERE estadoid = '1';";
            
            //$result = sql_do_query($sql,'1',$usu);
            while($row=sql_do_array($result))  { 
                    $archivo = '../aced_docentes/web/arch/'.$ndoc."/".$row['ELEMento_LEGAJOID'].".pdf";
                    if(is_file($archivo)){
            ?>
            <tr>
                <td class="text-center"><?php echo $row['ELEMento_LEGAJOID'];?></td>
                <td class="text-center"><?php echo $row['LETRA'];?></td>
                <td><?php echo $row['DESCRIPCION'];?></td>
                <td class="text-center">ESTADO</td>
                <td class="text-center">
                        <a href="#" target="_blank" onclick="window.open('<?php echo $archivo;?>', '_blank', 'width=500,status=no,directories=no,menubar=no,toolbar=no,scrollbars=no,location=no,resizable=no,titlebar=no'); return false;">
                            <img src="img/icon/documentos.png" class="me-2" style="height: 20px;">
                        </a>
                </td>
            </tr>
            
            
            
            
            <?php 
                }
            }
            ?>
        </tbody>
    </table>
</div>