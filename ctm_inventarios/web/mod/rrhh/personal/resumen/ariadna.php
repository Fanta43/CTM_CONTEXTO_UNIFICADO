<nav aria-label="breadcrumb">
    <ol class="breadcrumb m-0 fw-light">
<?php
                


                if($_POST['habilitar_edicion']){
                    if($_POST['habilitar_edicion'] == 'NO'){
                        $hab_ed = 'SI';
                        $hab_ed_tx = ' Habilitar Edición ';
                        $hab_styl = 'btn-pna-oscuro';
                        $_SESSION['HABILITA_EDICION'] = 'NO';
                    }     
                    if($_POST['habilitar_edicion'] == 'SI'){
                        $hab_ed = 'NO';
                        $hab_ed_tx = ' Deshabilitar Edición ';
                        $hab_styl = 'btn-pna-activo';
                        $_SESSION['HABILITA_EDICION'] = 'SI';
                    }
                }else{
                    if($_SESSION['HABILITA_EDICION'] == ''){
                        $_SESSION['HABILITA_EDICION'] = 'NO';
                    }
                    if($_SESSION['HABILITA_EDICION'] == 'NO'){
                        $hab_ed = 'SI';
                        $hab_ed_tx = ' Habilitar Edición ';
                        $hab_styl = 'btn-pna-oscuro';
                    }                    
                    if($_SESSION['HABILITA_EDICION'] == 'SI'){
                        $hab_ed = 'NO';
                        $hab_ed_tx = ' Deshabilitar Edición ';
                        $hab_styl = 'btn-pna-activo';
                    }
                }


                $sql = "
                        WITH RECURSIVE hierarchy AS (
                            -- Caso base: el registro inicial
                            SELECT ID, OPCION, ESTADO, DIRECCION, SUBMENU, DESCRIPCION, MENU, NIVACC, TIPO
                            FROM inve_menus
                            WHERE ID = '$estado'

                            UNION ALL

                            -- Recursividad: obtener el padre del registro actual
                            SELECT m.ID, m.OPCION, m.ESTADO, m.DIRECCION, m.SUBMENU, m.DESCRIPCION, m.MENU, m.NIVACC, m.TIPO
                            FROM inve_menus m
                            JOIN hierarchy h ON m.ID = h.SUBMENU
                        )
                        SELECT * FROM hierarchy
                        ORDER BY SUBMENU;

                ";
                
                $sql = "
                             WITH hierarchy AS (
                           SELECT id, opcion, estado, direccion, submenu, descripcion, menu, nivacc, tipo
                             FROM inve_menus
                            WHERE  id = '$estado'
                        UNION ALL
                           SELECT  m.id, m.opcion, m.estado, m.direccion, m.submenu, m.descripcion, m.menu, m.nivacc, m.tipo
                            FROM inve_menus m
                      INNER JOIN hierarchy h ON m.id = h.submenu )
                          SELECT * FROM hierarchy ORDER BY submenu;
                ";
                
                $result = sql_do_query($sql, '1', $usu);
                
                while ($row = sql_do_array($result)) {
                    $var1 = $row['ID'];
                    $var2 = $row['OPCION'];
                    $isActive = ($var1 == $currentTab) ? 'active' : '';
                    echo '<li class="breadcrumb-item fs-9"><a href="index.php?estado='.$row['ID'].'"><strong> '.$row['OPCION'].' </strong></a></li>';
                }

        
?>
</ol>
</nav>
<div class="row flex-column align-items-start" style="height: 40px; margin: 2px auto">
    <strong style="font-size: 25px;">
        <?php echo $nombre_pag; ?>
    </strong>
</div>
