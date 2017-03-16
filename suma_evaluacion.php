<?php

  require_once('../config/conection.php'); 
$periodo =  $_POST['periodo'] ;
$plsection =  $_POST['plsection'] ;
$materia =  $_POST['materia'] ;
$grupo =  $_POST['grupo'] ;
$ciclo =  $_POST['ciclo'] ;



if($periodo!= "null"){
    
    //tomar el porcentaje total para no pasar de 100
 $sum_evaluaciones=0;
$plsection_text="";    
if($plsection != ""){
    $plsection_text=" and pls.id=$plsection";
}
if($grupo!="null"){     
$query_total_per = " select 
                            DISTINCT e.code,
                            e.id,
                            e.formula    
                        from
                            pl_scores s join pl_evaluation_criteria e on s.pl_evaluation_criteria_id=e.id 
                            join pl_section pls on pls.id=e.pl_section_id
                            join pl_subject sub on sub.id=s.pl_subject_id
                        where sub.op_subject_id=$materia and s.op_division_id=$grupo and s.active=true $plsection_text ";
}else{
  $query_total_per="SELECT 
                        c.id,
                        c.code,
                        c.formula 
                      from 
                        pl_section pls join 
                        pl_evaluation_criteria c on pls.id=c.pl_section_id
                     where op_subject_id=$materia
                        and op_batch_id = $ciclo $plsection_text";
}
 $ex_total_per = pg_query($query_total_per) or die("Tenemos un error con la base de datos, intenta mas tarde: $query_total_per" . pg_last_error());
  while ($total_per = pg_fetch_array($ex_total_per, null, PGSQL_ASSOC)) {
              
                $sum_evaluaciones+=$total_per["formula"];
  }
  
 echo($sum_evaluaciones);
}