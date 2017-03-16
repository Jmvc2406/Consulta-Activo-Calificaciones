<?php

/**
 * EN LA LLAMAD A ESTE ARCHIVO SE RETORNAN LAS MATERIAS Y CURSOS DEL PROFESOR   
 *  /
 */
require_once('../config/conection.php');
//  AQUI VALIDAREMOS USUARIO. SINO RETORNAR A LOGIN
session_start();
$name = $_SESSION['name'];
if (!empty($name)) {
    $faculty_id = isset($_POST['faculty_id']) ? $_POST['faculty_id'] : NULL;
    $batch = isset($_POST['batch_id']) ? $_POST['batch_id'] : NULL;
  
    $html = "";
    if (!empty($faculty_id)) {
        
        //----------------------------------------------
        //CONSULTA Y CONSTRUCCIoN DE TABLA PARA MATERIAS 
        //----------------------------------------------
        if ($faculty_id == "admin") {
            $query = "select DISTINCT sub.id as materia_id, sub.name as materia, div.id as grupo_id,div.name as grupo, sec.op_batch_id as ciclo_id, sub.code as code
                    from 
                        pl_section sec 
                        join pl_subject pls on sec.pl_subject_id=pls.id
                        join op_subject sub on pls.op_subject_id=sub.id
                        join op_division  div on sec.op_division_id = div.id
                    where sec.op_batch_id=$batch and sec.op_division_id is not null ". 
                        //and pl_curso_id is NULL
                    "order by grupo,code;";
            
        } else {
            $query = "select s.id as materia_id, s.name as materia, d.id as grupo_id, d.name as grupo, pl.op_batch_id as ciclo_id, s.code as code      
                    from pl_faculty_subject_division pl 
                    join op_subject s on pl.op_subject_id= s.id
                    join op_division d on d.id=pl.op_division_id 
                    where op_faculty_id= $faculty_id and pl.op_batch_id=$batch and pl.op_division_id is NOT null
                    order by grupo,code;";
           
        }

        $validacion_ = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
        $valida_subject = pg_fetch_array($validacion_, null, PGSQL_ASSOC);

        $subjects = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());

        if ($valida_subject != false) {
            $html.="<div class='title3'> MATERIA </div><table><tr><td style='width: 12%;'> CODIGO</td>  <td style='width: 30%;'> MATERIA</td>  <td style='width: 12%;'> GRUPO </td> <td style='width: 20%;'> EVALUACIONES </td></tr>";
            while ($subject = pg_fetch_array($subjects, null, PGSQL_ASSOC)) {
                //revisar que la materia no sea curso 
               // $query_curso = "select name from pl_curso where op_subject_id=" . $subject["grupo_id"];
               // $curso = pg_query($query_curso) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
                $flag = 0;
               // while ($curso = pg_fetch_array($query_curso, null, PGSQL_ASSOC)) {
                //    $flag = 1;
                //}//fin while 2

                if ($flag == 0) {
                    $html.="<tr>";
                    $html.="<td>" . $subject["code"] . "</td>";
                    $html.="<td>" . $subject["materia"] . "</td>";
                    $html.="<td>" . $subject["grupo"] . "</td>";

                    //Imprimir evaluaciones 
                    
                    $query_evaluations = "select 
                                DISTINCT e.code,
                                e.id, 
                                e.formula , 
                                e.seq as orden_criterio,
                                pls.name,
                                pls.seq as pestana,
                                pls.id as pl_section_id
                            from
                                pl_scores s join pl_evaluation_criteria e on s.pl_evaluation_criteria_id=e.id 
                                join pl_section pls on pls.id=e.pl_section_id
                                join pl_subject sub on sub.id=s.pl_subject_id
                            where 
                                sub.op_subject_id=" . $subject["materia_id"] . " 
                                and s.op_division_id=" . $subject["grupo_id"] .' 
                                and s.active=true '
                             .' and op_batch_id='. $batch
                            . ' order by pls.seq,e.seq';
                    $pestania_aux="";
                    $sum_evaluaciones = 0;
                    $cont_eval=0;
                    $ex_evaluations = pg_query($query_evaluations) or die('Tenemos un error con la base de datos, intenta mas tarde: ' .$query_evaluations ."\n --- <br>" .pg_last_error());
                    $html.="<td>";
                    
            
                    while ($evaluacion = pg_fetch_array($ex_evaluations, null, PGSQL_ASSOC)) {
                        $pestania=$evaluacion["pestana"];
                        $name = $evaluacion["code"];
                        $name_periodo = $evaluacion["name"];
                        $pl_section_id=$evaluacion["pl_section_id"];
                        $id = $evaluacion["id"];
                        $materia = $subject["materia_id"];
                        $grupo = $subject["grupo_id"];
                        $formula = $evaluacion["formula"];
                        $ciclo = $subject["ciclo_id"];
                        
                        if($pestania_aux!=$pestania){
                             $pestania_aux=$pestania;
                             $html.="<hr><br><b> * $name_periodo *</b> <br>";
                             $sum_evaluaciones=0;
                             $html.=" <br> - <a style='text-decoration: none;' href='evaluaciones_editar.php?evaluacion=$id&grupo=$grupo&ciclo=$ciclo&materia=$materia&plsection=$pl_section_id'>$name ($formula%) </a>";
                             
                        }else{
                            $sum_evaluaciones+=$evaluacion["formula"];
                            $html.="<br> - <a style='text-decoration: none;' href='evaluaciones_editar.php?evaluacion=$id&grupo=$grupo&ciclo=$ciclo&materia=$materia&plsection=$pl_section_id'>$name($formula%) </a>";
                        }
                        
                    $cont_eval++;
                    }//fin while
                             
                   
                      $html.="<br><hr> <a style='text-decoration: none;' href='evaluaciones.php?grupo=" . $subject["grupo_id"] . "&ciclo=" . $subject["ciclo_id"] . "&materia=" . $subject["materia_id"] . "'> <b>  NUEVA EVALUACION </b> </a>";
                      $html.="<br> <a style='text-decoration: none;' href='duplica_criterio.php?grupo=" . $subject["grupo_id"] . "&ciclo=" . $subject["ciclo_id"] . "&materia=" . $subject["materia_id"] . "'> <b>  DUPLICAR EVALUACIONES  </b> </a>";

                    $html.="</td>";
                   

                    $html.="</tr>";
                }
            }//fin whilesubject

            $html.="</table>"; //fin tabla materias
        }//fin valida subject 
        
       //----------------------------------------------
        //CONSULTA Y CONSTRUCCIoN DE TABLA PARA AREAS 
        //----------------------------------------------

        if ($faculty_id == "admin") {
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
            
        } else {

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
            
        }
        //AREAS
        $validacion_deporte = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
        $hay_deporte = pg_fetch_array($validacion_deporte, null, PGSQL_ASSOC);
        if ($hay_deporte) {
            $subjects = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
            $html.="<div class='title3'> AREAS </div><table><tr><td style='width: 12%;'> CODIGO</td>  <td style='width: 30%;'> AREA</td>  </td> <td style='width: 20%;'> EVALUACIONES </td></tr>";
            while ($subject = pg_fetch_array($subjects, null, PGSQL_ASSOC)) {
                 $sum_evaluaciones = 0;
                $html.="<tr>";
                $html.="<td>" . $subject["code"] . "</td>";
                $html.="<td>". $subject["materia"]."</td>";
                //aqui ponemos las evaluaciones
                
                $query_evaluations = "SELECT 
                                        c.id,
                                        c.code,
                                        c.formula,
                                        s.name,
                                        s.seq as pestana,
                                        s.id as pl_section_id
                                      from 
                                        pl_section s join 
                                        pl_evaluation_criteria c on s.id=c.pl_section_id
                                     where op_subject_id=" . $subject["materia_id"] . "
                                        and op_batch_id = " . $batch;
              
                    $ex_evaluations = pg_query($query_evaluations) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
                    $html.="<td>";
                    $pestania_aux="";
                    $cont_eval=0;
                    while ($evaluacion = pg_fetch_array($ex_evaluations, null, PGSQL_ASSOC)) {
                         $pestania=$evaluacion["pestana"];
                         $name_periodo = $evaluacion["name"];
                         $pl_section_id=$evaluacion["pl_section_id"];
                        $name = $evaluacion["code"];
                        $id = $evaluacion["id"];
                        $materia = $subject["materia_id"];
                        $grupo = "null";
                        $ciclo = $subject["ciclo_id"];
                        $formula=$evaluacion["formula"];
                          if($pestania_aux!=$pestania){
                             $pestania_aux=$pestania;
                             
                             $html.="<hr><br><b> * $name_periodo *</b> <br>";
                             $sum_evaluaciones=0;
                             $sum_evaluaciones+=$evaluacion["formula"];
                             $html.="<br> - <a style='text-decoration: none;' href='evaluaciones_editar.php?evaluacion=$id&grupo=$grupo&ciclo=$ciclo&materia=$materia&plsection=$pl_section_id'>$name ($formula%) </a>";
                        }else{
                            $sum_evaluaciones+=$evaluacion["formula"];
                            $html.="<br> - <a style='text-decoration: none;' href='evaluaciones_editar.php?evaluacion=$id&grupo=$grupo&ciclo=$ciclo&materia=$materia&plsection=$pl_section_id'>$name($formula%) </a>";
                        }
                       
                    $cont_eval++;
                    }//fin while
                             
                      $html.="<br><br><a style='text-decoration: none;' href='evaluaciones.php?grupo=null&ciclo=" . $subject["ciclo_id"] . "&materia=" . $subject["materia_id"] . "'> <b>  NUEVA EVALUACION </b> </a>";
                   
                     
                    $html.="</td>";
                    $html.="</tr>";
            }//fin whilesubject

            $html.="</table>"; //fin tabla areas
        }//fin si no hay areas
        
        echo($html);
    } else {
        echo("No tienes grupos asignados");
    }
}else{
    echo("SU SESIÓN A TERMINADO");
}
?> 
