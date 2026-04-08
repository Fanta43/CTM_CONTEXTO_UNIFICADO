<div id="area">
<?php 
    //ini_set( 'display_errors', 'on' );
    //refrescoPagina($tiempo_refresco);
    $sql=" SELECT c.crontabid, 
                  c.cronfrecuenciaid,
                  c.frecuencia_seg,
                  c.descripcion, 
                  c.url,
                  c.estadoid
             FROM inve_crontab c 
            WHERE c.estadoid = '1'
              AND c.cronfrecuenciaid > 10
	 ORDER BY c.crontabid;";
     //echo $sql;
     $result=sql_do_query($sql,'1',$usu);
    $n = 0;
    while($row=sql_do_array($result)) {
        
                $sql3 = "SELECT auditoria_fecha  FROM inve_cronlog WHERE auditoria_fecha = '".date('Y-m-d')."'  AND  crontabid = '".$row['CRONTABID']."';";
                $result3 = sql_do_query($sql3,'1',$usu);
                $row3=sql_do_array($result3);
                
                $desde0hs = segundosDesde0Hs();
                
                //echo "<h1>$desde0hs > ".$row['FRECUENCIA_SEG']." and !".$row3['AUDITORIA_FECHA']."</h1>";

                if($desde0hs > $row['FRECUENCIA_SEG'] and !$row3['AUDITORIA_FECHA']){
                    $color = $color_incorrecto;
                    $consulta = json_encode(get_web_page($servidor.$row['URL'])); 
                    //var_dump($consulta);
                    $cronlogid = get_id('CONS_CRONLOG');
                    $sql="     INSERT INTO inve_cronlog (
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