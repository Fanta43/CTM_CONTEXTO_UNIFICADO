<div id="area">
<?php 
    ////ini_set( 'display_errors', 'on' );
    //refrescoPagina($tiempo_refresco);
    $sql=" SELECT c.crontabid, 
                  c.cronfrecuenciaid,
                  c.frecuencia_seg,
                  c.descripcion, 
                  c.url,
                  c.estadoid
             FROM more_crontab c 
            WHERE c.estadoid = '1'
              AND c.cronfrecuenciaid <= 10
	 ORDER BY c.crontabid;";
     //echo $sql;
     $result=sql_do_query($sql,'1',$usu);
    $n = 0;
    while($row=sql_do_array($result)) {
   
                $sql3 = "SELECT MAX(auditoria_fecha) AS auditoria_fecha  FROM more_cronlog WHERE crontabid = '".$row['CRONTABID']."';";
                $result3 = sql_do_query($sql3,'1',$usu);
                $row3=sql_do_array($result3);
                $restante = entreTiempos($row3['AUDITORIA_FECHA'], $hoy_completo);
                
                
                if($restante>$row['FRECUENCIA_SEG'] or !$row3['AUDITORIA_FECHA']){
                    $color = $color_incorrecto;
                    $consulta = json_encode(get_web_page($servidor.$row['URL'])); 
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
                                           '".$row['CRONTABID']."', 
                                           '$hoy', 
                                           '$consulta', 
                                           '$usu'
                                );";
                    //echo $sql;
                    sql_do_query($sql,'1',$usu);
                }else{
                    $color = $color_correcto;
                }
        
        
            
}
?>        
</div>