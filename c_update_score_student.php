<?php

/*Necesito:
                 * id alumno
                 * id pl_section_student
                 * score de pl_scores
                 * formula (porcentaje) de pl_evaluation_criteria   
 */

require_once('../config/conection.php'); 
/* 
 *En este archivo creamos la vista para mostrar  las evaluaciones correspondientes a ese grupo y materia
 */

session_start();
$name= $_SESSION['name'];
$id_faculty=$_SESSION['faculty_id'];
if(!empty($name)){
    
  $grupo      =$_POST["grupo"];
  $ciclo      =$_POST["ciclo"];
  $materia    =$_POST["materia"]; 
  $seccion    =$_POST["seccion"]; 
  $curriculum =$_POST["curriculum"]; 
  $pestana    =$_POST["pestana"]; 
  
/*1 Buscar los porcentajes de las evaluaciones*/
      $arr_percentage=array();
      $arr_percentage_temp=array();#para las ponderaciones vacias
      $arr_student_scores=array();
    if($grupo != "null" && $grupo != "" ){
      $query_percentage ="select 
                        DISTINCT e.code,
                        e.id,
                        e.formula,
                        e.seq
                    from
                        pl_scores s join pl_evaluation_criteria e on s.pl_evaluation_criteria_id=e.id 
                        join pl_subject sub on sub.id=s.pl_subject_id
                        join pl_section sec on e.pl_section_id=sec.id
                    where sub.op_subject_id=$materia and s.op_division_id=$grupo and pl_section_id=$seccion
                        and sec.name='$pestana'
                    and s.active=true"
              . " order by e.seq";
    }  else{
        $query_percentage="SELECT 
                            c.id,
                            c.code,
                            c.formula,
                            c.seq
                          from 
                            pl_section s join 
                            pl_evaluation_criteria c on s.id=c.pl_section_id
                         where op_subject_id= $materia
                            and op_batch_id = $ciclo"
                        . " and s.name='$pestana'"
                . " order by c.seq";
    } 

    $ex_percentage=pg_query($query_percentage) or die("\n Tenemos un error con la base de datos query 1, $query_percentage --" . pg_last_error());
     while ($percentage = pg_fetch_array($ex_percentage, null, PGSQL_ASSOC)) { 
         $arr_percentaje_aux=array();
         $arr_percentaje_aux["id"]=$percentage["id"];
         $arr_percentaje_aux["percentage"]=$percentage["formula"]/100;
         $arr_percentage[]= $arr_percentaje_aux;
     }

     $arr_percentage_temp=$arr_percentage;
    
  /*2 Obtener los alumnos de pl_section_student como los que estan en la pagina periodo.php*/
  if($grupo != "null" && $grupo != "" ) {      
    $query_student = "  select
              se.id, st.id as alumno,st.id_number as matricula
          from pl_section sec
              join pl_subject s on sec.pl_subject_id=s.id
              join pl_section_student se on se.pl_section_id=sec.id
              JOIN op_student st on se.op_student_id=st.id
              JOIN res_partner res on res.id=st.partner_id
          where sec.op_division_id = $grupo
              and s.op_subject_id= $materia
              and sec.op_batch_id= $ciclo
              and se.pl_section_id= $seccion
              and  se.active=true     
          order by roll_number;";
  }else{
      $query_student="select
                                st.id  as alumno,
                                se.id,
                                st.id_number as matricula
                            from pl_section sec
                                join pl_subject s on sec.pl_subject_id=s.id
                                join pl_section_student se on se.pl_section_id=sec.id
                                JOIN op_student st on se.op_student_id=st.id
                                JOIN res_partner res on res.id=st.partner_id
                            where 
                                 s.op_subject_id= $materia
                                and sec.op_batch_id= $ciclo
                                and se.pl_section_id= $seccion
                                and sec.op_division_id is null
                                and  se.active=true 
                            order by roll_number;";
  }
$ex_student = pg_query($query_student) or die("Tenemos un error con la base de datos, intenta mas tarde: $query_student" . pg_last_error());
 
  $cont_temp=0;
while ($student = pg_fetch_array($ex_student, null, PGSQL_ASSOC)) { 
      $id_pl_section=$student["id"];
      $id_student=$student["alumno"];
      $matricula=$student["matricula"];
      $arr_student_temp=array();
      $arr_student_temp[0]=$id_student;
 

 /*3 Buscar las calificaciones del alumno de pl_scores con el id del alumno, y id de la evaluacion y guardarlas en un array*/
        foreach ($arr_percentage as $criterio) {
           
           $query_score_student="select score from pl_scores
                                where op_student_id = $id_student
                                and pl_evaluation_criteria_id=".$criterio["id"];
           $cont_temp++;
          
            $ex_score_student = pg_query($query_score_student) or die("Tenemos un error con la base de datos, intenta mas tarde: $query_score_student -- " . pg_last_error());
             while ($score_student = pg_fetch_array($ex_score_student, null, PGSQL_ASSOC)) { 
             
                 $arr_student_temp[]=$score_student["score"];
             }
        }
        
         $arr_student_scores[]=$arr_student_temp;
  }//fin while student
  
  //CALCULAR LOS PROMEDIOS 
  $arr_key_score_empty=array();
  
  foreach ($arr_student_scores as $key => $arr_score_student_) {
    $cont_=0; 
    $sum_calif_final=0;
      //hacer prorateo
      $prorat=FALSE;
      $suma_calificaciones=0;
      echo("array de calificaciones : ");
     var_dump($arr_score_student_);
     $arr_key_score_empty=array();
      foreach ($arr_score_student_ as  $key_empty => $student_scores) {
         if($cont_>0) {
            //revisar si la calificacion esta vacia y sumarlas 
            if ($student_scores == ""){
                $prorat=true;
                $arr_key_score_empty[]=$key_empty-1;
            }            
            $nuevo_score_x_evaluacion=$student_scores * ($arr_percentage[$key_empty-1]["percentage"]);
            echo("\nformula calificaciones: $student_scores * ".($arr_percentage[$key_empty-1]["percentage"]));
            $sum_calif_final+=$nuevo_score_x_evaluacion;  
            echo("=$nuevo_score_x_evaluacion\n");
            echo("SUMA: $sum_calif_final");
         }
         $cont_++;
      }

      if($prorat){
          echo("\nprorateo\n");
         //Si esta vacia recalcular los porcentajes y ponerlos en temp
          //1 sumar porcentajes que no esten 
          $cont=0;
          $suma_evaluaciones=0;
          echo("\nArray que no se deben sumar\n");
          var_dump($arr_key_score_empty);
          foreach ($arr_percentage as $key_porcentajes => $evaluaciones) {
              echo("está en array? ".in_array($key_porcentajes, $arr_key_score_empty)."\n");
              if (!in_array($key_porcentajes, $arr_key_score_empty)){
                  echo("suma: ".$evaluaciones["percentage"]."\n");
                  $suma_evaluaciones+=$evaluaciones["percentage"];
              }else{
                  echo("no sumar: ".$evaluaciones["percentage"]."\n Key no sumar: $key_porcentajes \n");
              }
             $cont++; 
           }
           //FALTA ID Y PERSONAJE
           //2 h    exit();hacer operaciones para calcular el nuevo valor de porcentaje y reemplazarlo en temp
            foreach ($arr_percentage as $key_nuevo_porcentaje =>$evaluaciones) {
               echo("Porcentaje Viejo: ".$evaluaciones["percentage"]."\n");
                if (!in_array($key_nuevo_porcentaje, $arr_key_score_empty)){
                    $porcentaje_nuevo= 100*$evaluaciones["percentage"]/$suma_evaluaciones;
                     echo("SUMA: ".$suma_evaluaciones."\n");
                    echo("Porcentaje Nuevo: ".$porcentaje_nuevo."\n");
                    $arr_percentage_temp[$key_nuevo_porcentaje]["percentage"]=$porcentaje_nuevo;
                }else{
                    echo("no nuevo, borrarlo: ".$evaluaciones["percentage"]."\n" );
                    $arr_percentage_temp[$key_nuevo_porcentaje]="";
                    
                }               
            } 
            
            
          //calcular promedio con temp
            //calcular nuevas calificaciones y sumarlas 
            $cont1=0;
            $sum_calif_final=0;
            echo(" array porcentaje:");
            var_dump($arr_percentage_temp );
            
           foreach ($arr_score_student_ as $key_score_prom => $scores) {
               if($cont1>0){   
                   if($scores!=""){
                   echo("KEY NUEVO PORCENTAJE $key_score_prom-1 \n");
                    $nuevo_score_x_evaluacion=($scores*$arr_percentage_temp[$key_score_prom-1]["percentage"])/100;
                    echo("form= ".$scores."*".$arr_percentage_temp[$key_score_prom-1]["percentage"]."\n");
                    $sum_calif_final+=$nuevo_score_x_evaluacion;
                   }
               }
               $cont1++;
           }
       echo(" sum_calif_final $sum_calif_final ");
          
          // actualizar promedio general
            
         if($sum_calif_final!=0){         
        //truncar a 1 decimal
        if(strlen($sum_calif_final) > 1 && $sum_calif_final!=10 ){
            $aux_score=  explode( ".",$sum_calif_final);
           
            if(count($aux_score>1)){
                //cortar el decimal
                $decimales=$aux_score[1];             
                $decimales_trunc=  substr($decimales, 0,1);                
                $sum_calif_final=$aux_score[0].".".$decimales_trunc;
                }
        }
        $query_update_score = "update pl_section_student set score=$sum_calif_final  where op_student_id=".$arr_score_student_["0"]." and pl_section_id=$seccion and op_batch_id=$ciclo;";      
        echo("$query_update_score\n");
        $ex_score_evaluation_student = pg_query($query_update_score) or die("Tenemos un error con la base de datos, intenta mas tarde: $query_update_score" . pg_last_error());      
      }
      }else{
            echo("\nsum_calif_final 1 $sum_calif_final \n");
          //calcular promedio y actualizar $arr_score_student_[0], ahi esta el id del alumno 
            // actualizar promedio general
         if($sum_calif_final!=0){             
        //truncar a 1 decimal
        if(strlen($sum_calif_final) > 1 && $sum_calif_final!=10 ){
            $aux_score=  explode( ".",$sum_calif_final);
            
            if(count($aux_score>1)){
                //cortar el decimal
                echo("decimales");
                var_dump($decimales);
                $decimales=$aux_score[1];
                $decimales_trunc=  substr($decimales, 0,1);
                $sum_calif_final=$aux_score[0].".".$decimales_trunc;
                echo("\n sum_calif_final 2 $sum_calif_final\n");
                }
        }
        $query_update_score = "update pl_section_student set score=$sum_calif_final  where op_student_id=".$arr_score_student_["0"]." and pl_section_id=$seccion and op_batch_id=$ciclo;";
        echo("$query_update_score\n");
        $ex_score_evaluation_student = pg_query($query_update_score) or die("Tenemos un error con la base de datos, intenta mas tarde: $query_update_score" . pg_last_error());      
         }//calif final
      }
  echo("----------------------------------------------------------------------------------------------------------------------------");    
  }//fin for
  
}// fin empty name
