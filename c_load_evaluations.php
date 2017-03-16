<?php

require_once('../config/conection.php');
/*
 * En este archivo creamos la vista para mostrar una con las evaluaciones correspondientes a ese grupo y materia
 */
session_start();
$name = $_SESSION['name'];
$id_faculty = $_SESSION['faculty_id'];
if (!empty($name)) {
    $grupo = $_POST["grupo"];
    $ciclo = $_POST["ciclo"];
    $materia = $_POST["materia"];
    $seccion = $_POST["seccion"];
   
    if ($grupo != "" && $ciclo != "" && $materia != "" && $seccion != "") {
            $html="";
            $cont = 0;
            if($grupo!="null"){
                $query_evaluations = "select 
                            DISTINCT e.code,
                            e.id
                            ,e.seq
                        from
                            pl_scores s join pl_evaluation_criteria e on s.pl_evaluation_criteria_id=e.id 
                            join pl_subject sub on sub.id=s.pl_subject_id
                        where sub.op_subject_id=$materia and s.op_division_id=$grupo and pl_section_id=$seccion  and s.active=true order by e.seq ";
            }else{
                $query_evaluations="SELECT 
                                        c.id,
                                        c.code,
                                        c.formula ,
                                        c.seq
                                      from 
                                        pl_section s join 
                                        pl_evaluation_criteria c on s.id=c.pl_section_id
                                     where op_subject_id= $materia
                                        and op_batch_id = $ciclo "
                                      ." and s.id=$seccion order by c.seq";
            }            
            
            $ex_evaluations = pg_query($query_evaluations) or die("Tenemos un error con la base de datos, intenta mas tarde: $query_evaluations -----" . pg_last_error());
            if($grupo != "null"){
              $html.="<select name='evaluations_$seccion' id='evaluations_$seccion' onchange=load_evaluations_students($materia,$grupo,$ciclo,$seccion)>";
            }else{
              $html.="<select name='evaluations_$seccion' id='evaluations_$seccion' onchange=load_evaluations_students($materia,'null',$ciclo,$seccion)>";
            }
            $html.="<option value='null'>-- SELECCIONE UNA EVALUACION</option>";
            while ($evaluacion = pg_fetch_array($ex_evaluations, null, PGSQL_ASSOC)) {
                $name = $evaluacion["code"];
                $id = $evaluacion["id"];
                $html.="<option value=$id>$name</option>";
                $cont++;
            }//fin while
            $html.="</select>";
            if ($cont > 0) {
               echo("$html");
            } else {
                echo("Aun NO hay evaluaciones para calificar en este periodo. <br> "
                . "Si desea crear una evaluación de click al botón <i>'Criterios de Evaluacion'</i> del menu superior");
            }
        }else{
            echo("los datos no estan completos");
        }
        
} else {

    echo("usuario no loggeado");
}
