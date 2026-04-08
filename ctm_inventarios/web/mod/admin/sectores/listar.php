<div class="h-100 w-100">
    <?php
    $baja = Enmascarar_post('baja');
    $buscar = Enmascarar_post('buscar');
    ?>
    <div class="row flex-column align-items-start m-2">
        <strong class="fs-3">Sectores</strong>
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
                    <th>Id Centro de Costo</th>
                    <th>Nombre Centro de Costo</th> 
                </tr>
            </thead>
            <tbody>
                    <?php
                             
                $sql="SELECT a.hldid, 
                             a.conidcc, 
                             a.connomcc
                        FROM vw_accc a 
                       WHERE (
                                 UPPER(a.hldid) LIKE UPPER('%$buscar%') 
                                OR UPPER(a.empid) LIKE UPPER('%$buscar%')
                                OR UPPER(a.conidac) LIKE UPPER('%$buscar%')
                                OR UPPER(a.connomcc) LIKE UPPER('%$buscar%')
                                OR UPPER(a.itemcostid) LIKE UPPER('%$buscar%')
                                OR UPPER(a.connomabr) LIKE UPPER('%$buscar%')
                              )
                        ORDER BY a.connomcc;";
                    //echo $sql;
                    $result = sql_do_query($sql,'1',$usu);
                    $n=0;
                    while($row=sql_do_array($result)) {
                            $n++;
                            
                            // Suponiendo que se necesite mostrar un estado o imagen similar
                            $img_estado = ($row['ESTADOID']=='1') ? 'si.gif' : 'no.gif';
                    ?>
                            <tr>
                                <td align="center"><?php echo $n;?></td>
                                <td align="center"><?php echo $row['CONIDCC'];?></td>
                                <td align="left"><?php echo $row['CONNOMCC'];?></td>
                            </tr>
                    <?php
                    }
                    ?>
                      </tbody>
                    </table>
                </div>
            </div>
    <br />