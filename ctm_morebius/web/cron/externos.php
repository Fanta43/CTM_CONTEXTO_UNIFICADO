<div id="area">
<?php 
    //ini_set( 'display_errors', 'on' );
    //var_dump($contab);
    //refrescoPagina($tiempo_refresco);
   
                $sql3 = "SELECT MAX(auditoria_fecha) AS auditoria_fecha  FROM more_cronlog WHERE crontabid = '".$row['CRONTABID']."';";
                $result3 = sql_do_query($sql3,'1',$usu);
                $row3=sql_do_array($result3);
                $restante = entreTiempos($row3['AUDITORIA_FECHA'], $hoy_completo);
                
                if($restante>$row['FRECUENCIA_SEG'] or !$row3['AUDITORIA_FECHA']){
                    $consulta = "Servicio Externo del CRON -$contab-"; 
                    //var_dump($consulta);
                    $cronlogid = get_id('CONS_CRONLOG');
                    $sql="     INSERT INTO more_cronlog (
                                           cronlogid, 
                                           crontabid, 
                                           auditoria_fecha, 
                                           resultado, 
                                           auditoria_usu
                                ) VALUES (
                                           '$cronlogid', 
                                           '$contab', 
                                           '$hoy', 
                                           '$consulta', 
                                           '$usu'
                                );";
                    //echo $sql;
                    sql_do_query($sql,'1',$usu);
                }
?>        
</div>