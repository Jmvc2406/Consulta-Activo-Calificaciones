<?php 
require_once('../config/conection.php'); 
require_once('../config/utils.php');
//incluir php para crear reporte de UDIS y materias normales
require_once('c_create_report_subject.php');
require_once('c_create_report_udis.php');

//  AQUI VALIDAREMOS USUARIO. SINO RETORNAR A LOGIN
$batch = isset($_POST['batch']) ? $_POST['batch'] :"";
$curriculum= isset($_POST['curriculum']) ? $_POST['curriculum'] : "";
$division= isset($_POST['division']) ? $_POST['division'] : "";
$periodo= isset($_POST['periodo']) ? $_POST['periodo'] : "";

//echo("batch:".$batch." | curriculum: ".$curriculum." | division: ".$division." | periodo: ".$periodo);
//revisar que todos los datos tengan valores
if(!empty($batch) && !empty($curriculum) && !empty($division) && !empty($periodo)){

        //obtenemos el grado del grupo
    
        $query_standard = "select standard_id from op_allocat_division where division_id=$division;";
        $ex_standard = pg_query($query_standard) or die('Tenemos un error con la base de datos, intenta mas tarde: ' .$query_standard. pg_last_error());
        $arr_standard = pg_fetch_array($ex_standard, null, PGSQL_ASSOC);
        $standard= $arr_standard["standard_id"];
        
        
   //Hacemos una consulta al curriculum para ver si es UDI o normal      
        
      $query_udi="SELECT name FROM pl_curriculum 
                where 
                 id=$curriculum and
                  (name like '%TRANSITORIO%' OR
                  name like '%PREPRIMARIA%' OR
                  name like '%PRIMARIA IB%')";
                  
      $ex_udi = pg_query($query_udi) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());          
      $arr_udi = pg_fetch_array($ex_udi, null, PGSQL_ASSOC);
      if($arr_udi) {
         //echo(" es udi"); 
        $response= print_udis($batch,$curriculum,$division,$periodo,$standard);
         
      }else{
          //echo("no es udi");
        $response= print_subject($batch,$curriculum,$division,$periodo,$standard);
         
        }   
        echo("$response");     
                   
}else{
    echo("Debes seleccionar una opcion de cada lista desplegable. ");
    }