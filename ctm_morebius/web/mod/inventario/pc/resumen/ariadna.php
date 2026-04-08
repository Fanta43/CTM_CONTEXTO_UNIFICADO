<nav aria-label="breadcrumb">
    <ol class="breadcrumb m-0 fw-light">
<?php
                
                //var_dump($_SESSION['HABILITA_EDICION']);

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
                    if($_SESSION['HABILITA_EDICION'] == 'NO' or !$_SESSION['HABILITA_EDICION']){
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


                $sql_mysql = "
                        WITH RECURSIVE hierarchy AS (
                            SELECT ID, OPCION, ESTADO, DIRECCION, SUBMENU, DESCRIPCION, MENU, NIVACC, TIPO
                            FROM more_menus
                            WHERE ID = '$estado'
                            UNION ALL
                            SELECT m.ID, m.OPCION, m.ESTADO, m.DIRECCION, m.SUBMENU, m.DESCRIPCION, m.MENU, m.NIVACC, m.TIPO
                            FROM more_menus m
                            JOIN hierarchy h ON m.ID = h.SUBMENU
                        )
                        SELECT * FROM hierarchy
                        ORDER BY SUBMENU;

                ";
                
                $sql = "
                             WITH hierarchy AS (
                           SELECT id, opcion, estado, direccion, submenu, descripcion, menu, nivacc, tipo
                             FROM more_menus
                            WHERE  id = '$estado'
                        UNION ALL
                           SELECT  m.id, m.opcion, m.estado, m.direccion, m.submenu, m.descripcion, m.menu, m.nivacc, m.tipo
                            FROM more_menus m
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
