<?php

require_once('../config/conection.php');
/*
 * En este archivo creamos la vista para mostrar una lista con alumnos y sus calificaciones 7
 */
session_start();
$name = $_SESSION['name'];
$id_faculty = $_SESSION['faculty_id'];
if (!empty($name)) {
    $grupo = $_POST["grupo"];
    $ciclo = $_POST["ciclo"];
    $materia = $_POST["materia"];
    $seccion = $_POST["seccion"];
    $evaluacion = $_POST["evaluacion"];
   
    if($grupo!="null"){
        $query = "  select 
                                 s.id as id_sec_est,
                                 id_number as matricula, 
                                 coalesce(st.middle_name,' ')||' '||coalesce(st.last_name,' ')||' '||coalesce(res.name,' ') as alumno,
                                 score as calificacion 
                                from pl_scores s 
                                 join pl_subject sub on s.pl_subject_id =sub.id
                                 join op_student st on st.id = s.op_student_id
                                 join res_partner res on res.id = st.partner_id
                                where 
                                  op_division_id=$grupo and 
                                  op_subject_id=$materia and 
                                  pl_evaluation_criteria_id=$evaluacion and
                                  s.active=true
                                order by roll_number;";
    }else{
      $query  ="select 
                 scor.id as id_sec_est,
                 id_number as matricula, 
                 coalesce(st.middle_name,' ')||' '||coalesce(st.last_name,' ')||' '||coalesce(res.name,' ') as alumno,
                 score as calificacion 
                from 
                    pl_scores scor join 
                    pl_subject subject on scor.pl_subject_id=subject.id
                    join op_student st on scor.op_student_id= st.id
                    join res_partner res on res.id=st.partner_id
                    join pl_evaluation_criteria c on c.id=scor.pl_evaluation_criteria_id
                    join pl_section sec on sec.id = c.pl_section_id
                where 
                    pl_evaluation_criteria_id=$evaluacion
                    and  subject.op_subject_id=$materia
                    and sec.op_batch_id=$ciclo and scor.active=true"
                  . " order by roll_number;";
                    
    }
    $tabla = pg_query($query) or die("Tenemos un error con la base de datos, intenta mas tarde: $query" . pg_last_error());
    $html.="<div id='error_score'></div> ";
    $html.="<div class='table_grey'> <form id='form_save_evaluations_$seccion'>";   //comienza la tabla 
    $html.=" <table >";
    $html.="    <tr>
                    <td> Matricula</td>
                    <td > Alumno</td>
                    <td > Calificacion</td>
                <tr>";

    while ($col = pg_fetch_array($tabla, null, PGSQL_ASSOC)) {

        $html.="<tr>";
        $html.="<td > " . $col["matricula"] . "</td>";
        $html.="<td > " . $col["alumno"] . "</td>";
        $html.="<td><input type='text' value='" . $col["calificacion"] . "' name='score-" . $col["id_sec_est"] . "'  id='score-" . $col["id_sec_est"] . "' placeholder='Califica' onkeypress='return valida_numero(event, this);' >  </td>";
        $html.="</tr>";
    }
    $html.=" </table > </form>";
    $html.="</div>";
    echo("$html");
} else {

    echo("usuario no loggeado");
}

