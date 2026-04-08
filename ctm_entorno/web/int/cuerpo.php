<style>
.iframe-16-9  iframe {
    position: absolute;
    top:0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index:0;
}
</style>
<?php
ini_set( 'display_errors', 'on' );
    $codigo_sistema = $_GET['estado'];
    
    if(is_numeric($codigo_sistema)){
         
        $sql3="select * from ento_menus where id='".$codigo_sistema."';";
        $result3 = sql_do_query($sql3,'1',$usu);
        $row3=sql_do_array($result3);
        $direcion=$row3['DIRECCION'];
        include_once "$direcion";

    }else{
        
        $sql3="select pagina_inicio from usu_sistemas where codigo='".$codigo_sistema."';";
        $result3 = sql_do_query($sql3,'2',$usu);
        $row3=sql_do_array($result3);
        $direcion=$row3['PAGINA_INICIO'];
        
?>
        <div class="iframe-16-9"><iframe src="../<?php echo $direcion; ?>" frameborder="0" marginwidth="0" marginheight="0"></iframe></div>       
        
<?php   
    }
?>