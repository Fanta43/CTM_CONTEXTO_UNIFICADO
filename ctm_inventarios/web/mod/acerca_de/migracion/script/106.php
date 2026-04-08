<?php

    //ini_set( 'display_errors', 'on' );

    $sql="TRUNCATE TABLE inve_discos;";
    echo $sql." <br />";
    $result_0 = sql_do_query($sql,'1',$usu);

    $sql="SELECT discocod, 
                 cpudisco,
                 cpunrodisc, 
                 cpudissetu, 
                 cpusoftwar, 
                 discfcompr, 
                 discpcc, 
                 discoc, 
                 discvtogar, 
                 discbaja, 
                 discprest, 
                 provcod, 
                 discfbaja, 
                 discozona 
            FROM discos;";
        echo $sql." <br />";
        $result = sql_do_query($sql,'4',$usu);
        $n=0;
        while($row=sql_do_array($result,'4',$usu)) {
            
            $discoid = get_id('INVE_DISCOS');
            
            $sql = "INSERT INTO inve_discos (
                                discoid,
                                discocod, 
                                cpudisco, 
                                cpunrodisc, 
                                cpudissetu, 
                                cpusoftwar, 
                                discfcompr, 
                                discpcc, 
                                discoc, 
                                discvtogar, 
                                discbaja, 
                                discprest, 
                                provcod, 
                                discfbaja, 
                                discozona, 
                                estadoid, 
                                auditoria_usu, 
                                auditoria_fecha
                    ) VALUES (
                                '".$discoid."', 
                                '".$row['DISCOCOD']."', 
                                '".$row['CPUDISCO']."', 
                                '".$row['CPUNRODISC']."',  
                                '".$row['CPUDISSETU']."',  
                                '".$row['CPUSOFTWAR']."',
                                CONVERT(datetime, '".substr($row['DISCFCOMPR'],0,10)."', 120), 
                                '".$row['DISCPCC']."',  
                                '".$row['DISCOC']."',
                                CONVERT(datetime, '".substr($row['DISCVTOGAR'],0,10)."', 120), 
                                '".$row['DISCBAJA']."',  
                                '".$row['DISCPREST']."',  
                                '".$row['PROVCOD']."', 
                                CONVERT(datetime, '".substr($row['DISCFBAJA'],0,10)."', 120),   
                                '".$row['DISCOZONA']."',   
                                '1',
                                '".$usu."',
                                CONVERT(datetime, '".substr($hoy,0,10)."', 120)
                    );";
            echo $sql." <br />";
            $result_1 = sql_do_query($sql, '1', $usu);
        }
        