<?php 
require_once('../config/conection.php'); 
require_once('../config/utils.php');
require_once('../config/vars.php'); 
require_once('../lib/Classes/PHPExcel.php');
require_once('../lib/Classes/PHPExcel/Reader/Excel2007.php');
require_once('../lib/Classes/PHPExcel/IOFactory.php');
include_once ('c_create_excel_subject.php');
include_once ('c_create_excel_udis.php');
//  AQUI VALIDAREMOS USUARIO. SINO RETORNAR A LOGIN

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="book.xlsx"');
header('Cache-Control: max-age=0');


$batch = isset($_GET['batch']) ? $_GET['batch'] :"";
$curriculum= isset($_GET['curriculum']) ? $_GET['curriculum'] : "";
$division= isset($_GET['division']) ? $_GET['division'] : "";
$periodo= isset($_GET['periodo']) ? $_GET['periodo'] : "";

//echo("batch:".$batch." | curriculum: ".$curriculum." | division: ".$division." | periodo: ".$periodo);
//revisar que todos los datos tengan valores
if(!empty($batch) && !empty($curriculum) && !empty($division) && !empty($periodo)){

        //obtenemos el grado del grupo
    
        $query_standard = "select standard_id from op_allocat_division where division_id=$division;";
        $ex_standard = pg_query($query_standard) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
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
        $response= print_udis_excel($batch,$curriculum,$division,$periodo,$standard);
      }else{ 
          //echo("no es udi");
        $response= print_subject_excel($batch,$curriculum,$division,$periodo,$standard);
         
        }   
        
}else{
    echo('Los datos no estan completos');
    }
    
    
    