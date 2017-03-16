<?php
  require_once('../config/conection.php'); 
   include('c_secciones.php'); 
//  AQUI VALIDAREMOS USUARIO. SINO RETORNAR A LOGIN
$faculty_id = isset($_POST['faculty_id']) ? $_POST['faculty_id'] : NULL;
$batch = isset($_POST['batch_id']) ? $_POST['batch_id'] : NULL;
$href="";
$html="";
   if(!empty($faculty_id) ){
        //----------------------------------------------
        //CONSULTA Y CONSTRUCCI�N DE TABLA PARA MATERIAS 
        //----------------------------------------------
      if($faculty_id=="admin"){ 
           $query = "select DISTINCT sub.id as materia_id, sub.name as materia, div.id as grupo_id,div.name as grupo, sec.op_batch_id as ciclo_id, sub.code as code
                    from 
                        pl_section sec 
                        join pl_subject pls on sec.pl_subject_id=pls.id
                        join op_subject sub on pls.op_subject_id=sub.id
                        join op_division  div on sec.op_division_id = div.id
                    where sec.op_batch_id=$batch and sec.op_division_id is not null ".
                        //and pl_curso_id is NULL
                    "order by grupo,code;";
             $href="periodos_admin.php";       
                    
       }
       else{  
         
           $query = "select s.id as materia_id, s.name as materia, d.id as grupo_id, d.name as grupo, pl.op_batch_id as ciclo_id, s.code as code      
                    from pl_faculty_subject_division pl 
                    join op_subject s on pl.op_subject_id= s.id
                    join op_division d on d.id=pl.op_division_id 
                    where op_faculty_id= $faculty_id and pl.op_batch_id=$batch and pl.op_division_id is NOT null
                    order by grupo,code;";
           $href="periodos.php";   
                 
        }
       
       $validacion_     = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
       $valida_subject  = pg_fetch_array($validacion_, null, PGSQL_ASSOC);
       
       $subjects        = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
       
       if($valida_subject != false){
           $html.="<div class='title3'> MATERIAS </div><table><tr><td style='width: 12%;'> CODIGO</td>  <td> MATERIA</td>  <td> GRUPO </td></tr>";
            while ($subject = pg_fetch_array($subjects, null, PGSQL_ASSOC)) {
               //revisar que la materia no sea curso 
                //$query_curso = "select name from pl_curso where op_subject_id=".$subject["grupo_id"];
                //$curso = pg_query($query_curso) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
                $flag=0;
                //while ($curso = pg_fetch_array($query_curso, null, PGSQL_ASSOC)) {
                //    $flag=1;
                //}//fin while 2
              
               if($flag==0){
                    $html.="<tr>";
                    $html.="<td>".$subject["code"]."</td>";
                    $html.="<td><a href='$href?grupo=".$subject["grupo_id"]."&ciclo=".$subject["ciclo_id"]."&materia=".$subject["materia_id"]."'> ".$subject["materia"] ."</a></td>";                $html.="<td>".$subject["grupo"]."</td>";
                    $html.="<td><a href='/controller/c_report_score_subject_excel.php?grupo=".$subject["grupo_id"]."&ciclo=".$subject["ciclo_id"]."&materia=".$subject["materia_id"]."'> CALIFICACIONES </a></td>";
                   $html.="</tr>";
                  
                }
            }//fin whilesubject
            
            $html.="</table>";//fin tabla materias
        
        }//fin valida subject 
        
        
        //----------------------------------------------
        //CONSULTA Y CONSTRUCCI�N DE TABLA PARA CURSOS 
        //----------------------------------------------
    /*if($faculty_id=="admin"){
          $query = "select c.id as curso_id,c.code as code ,c.order as seq , c.name as curso, cs.op_batch_id as ciclo_id, s.name as materia
                    from pl_curso c join 
                        pl_curso_student cs on c.id=cs.pl_curso_id join
                        op_subject s on c.op_subject_id= s.id
                    where 
                        cs.op_batch_id=$batch  
                       
                        order by materia,seq";
          }
       else{ 
         $query = "select c.id as curso_id,c.code as code ,c.order as seq , c.name as curso, cs.op_batch_id as ciclo_id, s.name as materia
                    from pl_curso c join 
                        pl_curso_student cs on c.id=cs.pl_curso_id join
                        op_subject s on c.op_subject_id= s.id
                    where 
                        cs.op_batch_id=$batch  
                        and c.op_faculty_id=$faculty_id
                        order by materia,seq";
        }    
                                                   
       //validaicon
       $cursos_validacion = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
       $hay_curso=pg_fetch_array($cursos_validacion, null, PGSQL_ASSOC); 
       
      
       if($hay_curso){
               $cursos = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
               $html.=" <div class='title3'><br> CURSOS </div>
                        <table><tr><td style='width: 12%;'>CODIGO </td><td>CURSO</td></tr>";
               $arr_imprime_materia=array();
                while ($curso_ = pg_fetch_array($cursos, null, PGSQL_ASSOC)) {
                
                    $html.="<tr>";
                    $html.="<td>".$curso_["code"]."</td>";
                    $html.="<td> <a href='periodos_cursos_admin.php?&ciclo=".$curso_["ciclo_id"]."&curso=".$curso_["curso_id"]."'> ".$curso_["curso"]."</a></td>";
                    $html.="<td><a href='/controller/c_report_score_subject_excel.php?grupo=".$subject["grupo_id"]."&ciclo=".$subject["ciclo_id"]."&materia=".$subject["materia_id"]."'> CALIFICACIONES </a></td>";
                    $html.="</tr>";
                    
                }//fin while
                $html.="</table>";//fin tabla cursos
        }// fin if hay curso      
        */
        
        
        
        //----------------------------------------------
        //CONSULTA Y CONSTRUCCI�N DE TABLA PARA AREAS 
        //----------------------------------------------
        
      if($faculty_id=="admin"){ 
           $query = "select 
                        DISTINCT sub.id as materia_id, 
                        sub.name as materia, 
                        sec.op_batch_id as ciclo_id,
                        sub.code as code
                    from 
                        pl_section sec 
                        join pl_subject pls on sec.pl_subject_id=pls.id
                        join op_subject sub on pls.op_subject_id=sub.id
                    where sec.op_batch_id=$batch and  sec.op_division_id is null
                        order by code;";
             $href="periodos_areas_admin.php";       
                    
       }
       else{   
          
           $query = "select s.id as materia_id, s.name as materia, 
                        pl.op_batch_id as ciclo_id, 
                        s.code as code      
                        from pl_faculty_subject_division pl 
                        join op_subject s on pl.op_subject_id= s.id
                        where
                                op_faculty_id= $faculty_id 
                                and pl.op_batch_id= $batch
                                and pl.op_division_id is null
                        order by code";
           $href="periodos_areas.php";        
            
        }

       $validacion_deporte = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
       $hay_deporte = pg_fetch_array($validacion_deporte, null, PGSQL_ASSOC);
       if($hay_deporte) {
           $subjects = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
           $html.="<div class='title3'><br> AREAS </div><table><tr><td style='width: 12%;'> CODIGO</td>  <td> AREA</td></tr>";
            while ($subject = pg_fetch_array($subjects, null, PGSQL_ASSOC)) {
                    $html.="<tr>";
                    $html.="<td>".$subject["code"]."</td>";
                    $html.="<td><a href='$href?ciclo=".$subject["ciclo_id"]."&materia=".$subject["materia_id"]."'> ".$subject["materia"] ."</a></td>";                
                    $html.="<td><a href='/controller/c_report_score_subject_excel.php?grupo=".$subject["grupo_id"]."&ciclo=".$subject["ciclo_id"]."&materia=".$subject["materia_id"]."'> CALIFICACIONES </a></td>";
                    $html.="</tr>";
                
            }//fin whilesubject
            
            $html.="</table>";//fin tabla areas
        }//fin si no hay areas
        
        
        //----------------------------------------------
        //CONSULTA Y CONSTRUCCI�N DE TABLA PARA DEPORTES 
        //----------------------------------------------
                    
      if($faculty_id=="admin"){ 
           $query = "select 
                        s.id as id_sport,
                        op_batch_id as ciclo_id,
                        s.name as deporte ,
                        r.display_name as profesor 
                    from pl_sports s
                        join op_faculty f on s.op_faculty_id =f.id
                        join res_partner r on f.partner_id=r.id
                    where op_batch_id=$batch;";
             $href="deportes.php";       
                    
       }
       else{   
           $query = "select 
                        id as id_sport,op_batch_id as ciclo_id, name as deporte  
                    from pl_sports 
                    where 
                        op_faculty_id=$faculty_id
                        and op_batch_id=$batch;";
           $href="deportes.php";        
        }
         $ejecuta_query = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
         $valida_query  = pg_fetch_array($ejecuta_query, null, PGSQL_ASSOC);
 //REVISAR SI EL PROFESOR TIENE DEPORTES
       if($valida_query) {
            $subjects = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
           $html.="<div class='title3'><br> DEPORTES </div>";
           $html.=$faculty_id=="admin"?"<table><tr> <td> DEPORTE</td> <td>PROFESOR</td></tr>":"<table><tr> <td> DEPORTE</td></tr>";
            while ($subject = pg_fetch_array($subjects, null, PGSQL_ASSOC)) {
                    $html.="<tr>";
                    $html.="<td><a href='$href?ciclo=".$subject["ciclo_id"]."&deporte=".$subject["id_sport"]."'> ".$subject["deporte"] ."</a></td>";                
                    $html.=$faculty_id=="admin"?"<td>".$subject["profesor"]."</td>":"";
                    $html.="<td><a href='/controller/c_report_score_subject_excel.php?grupo=".$subject["grupo_id"]."&ciclo=".$subject["ciclo_id"]."&materia=".$subject["materia_id"]."'> CALIFICACIONES </a></td>";
                    $html.="</tr>";
                
            }//fin whilesubject
            
            $html.="</table>";//fin tabla areas
        }//fin if valida query
        
        $secciones=secciones($faculty_id, $batch);
        $html.=$secciones;
        echo($html); 
        
       
    }else{
           echo("No tienes grupos asignados");
    }
        
  ?> 
            