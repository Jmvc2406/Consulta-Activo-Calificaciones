<?php
require_once('../config/conection.php');    
session_start();
$name = $_SESSION['name'];
$html="";
if (!empty($name)) {
    $grupo = $_POST["division"];
    $ciclo = $_POST["batch"];
    $tipo = $_POST["type"];
    
    
   $arr_students= get_students($grupo,$ciclo,$tipo);
   $table=get_subjects($grupo,$arr_students);
   echo("<br><b>Se han creado los siguientes criterios: </b><br><br><table> <tr><td>Alumno</td> <td>Materia</td> <td>Evaluacion</td> </tr> $table </table>");
}

function get_students($grupo,$ciclo,$tipo){
    //obtener alumnos del grupo y guardar en un array 
    $students_=array();
    $query_students="select 
                    st.id,
                     coalesce(st.middle_name,' ')||' '||coalesce(st.last_name,' ')||' '||coalesce(res.name,' ') as alumno
                    from 
                        op_student st join res_partner res  on res.id=st.partner_id
                    where division_id=$grupo order by st.roll_number ";
    $students = pg_query($query_students) or die("Tenemos un error con la base de datos, intenta mas tarde #1 -: $query_students" . pg_last_error());
    while ($student = pg_fetch_array($students, null, PGSQL_ASSOC)) {
        $student_=array();
        $student_["id"]=$student["id"];
        $student_["name"]=$student["alumno"];
        $students_[]=$student_;
    }
    return $students_;
}
function get_subjects($grupo,$arr_students) {
    
    $arr_student_plscore=array();
     $evaluaciones=array();

    //obtener materias
    $query_subjects="select s.id as materia_id, s.name as materia, d.id as grupo_id, d.name as grupo, pl.op_batch_id as ciclo_id, s.code as code      
                    from pl_faculty_subject_division pl 
                    join op_subject s on pl.op_subject_id= s.id
                    join op_division d on d.id=pl.op_division_id 
                    where pl.op_division_id= $grupo
                    order by grupo,code";
    $ex_subject = pg_query($query_subjects) or die("Tenemos un error con la base de datos, intenta mas tarde #3: $query_subjects" . pg_last_error());
    while ($subject = pg_fetch_array($ex_subject, null, PGSQL_ASSOC)) {
         //obtener evaluaciones de todas las materias   
    $query_evaluaciones="select 
                            DISTINCT e.code,
                            e.id,
                            s.pl_curriculum_id as curriculum ,
			    sub.id as pl_subject_id,
                            ops.name as materia
                        from
                            pl_scores s join pl_evaluation_criteria e on s.pl_evaluation_criteria_id=e.id 
                            join pl_subject sub on sub.id            = s.pl_subject_id
                            join op_subject ops on sub.op_subject_id = ops.id
                        where 
                        op_subject_id=". $subject["materia_id"]."
                        and op_division_id= $grupo
                        and s.active=true 
                        ";
    $ex_evaluaciones = pg_query($query_evaluaciones) or die("Tenemos un error con la base de datos, intenta mas tarde #2: $query_evaluaciones" . pg_last_error());
    while ($evaluacion = pg_fetch_array($ex_evaluaciones, null, PGSQL_ASSOC)) {
        // GUARDAR TODAS LAS EVALUACIONES EN UN ARRAY
        $evaluacion_id=$evaluacion["id"];
        $evaluacion_name=$evaluacion["code"];
        $evaluacion_curriculum=$evaluacion["curriculum"];
        $evaluacion_pl_subject=$evaluacion["pl_subject_id"];
        $evaluacion_subject=$evaluacion["materia"];
    //OBTENER A LOS ALUMNOS DE LA EVALUACION
    $query_student_plscore="select 
			DISTINCT op_student_id
                    from
			pl_scores s join pl_evaluation_criteria e on s.pl_evaluation_criteria_id=e.id 
			join pl_subject sub on sub.id=s.pl_subject_id
                    where 
                        op_subject_id= ". $subject["materia_id"]."
                        and op_division_id= $grupo
                        and e.id=$evaluacion_id
                        and s.active=true 
                    order by op_student_id";
    $ex_student_plscore = pg_query($query_student_plscore) or die("Tenemos un error con la base de datos, intenta mas tarde #4: $query_student_plscore" . pg_last_error());
    while ($student_plscore = pg_fetch_array($ex_student_plscore, null, PGSQL_ASSOC)) {
        $arr_student_plscore[]=$student_plscore["op_student_id"];
    }
     //Revisar si el los alumnos de arr_student están en el array de students_plscore

    foreach ($arr_students as $student_group) {
        if(!in_array($student_group["id"], $arr_student_plscore)){
             $fecha= date("Y-m-d H:i:s");
             $username=$_SESSION['username'];
             $user_id=1;
                $query_user="select id from res_users where login= '$username'";
                $ex_user = pg_query($query_user) or die("Tenemos un error con la base de datos, intenta mas tarde #5: $query_user" . pg_last_error());
                while ($user = pg_fetch_array($ex_user, null, PGSQL_ASSOC)) {
                    $user_id=$user["id"];
                }
           //HACER INSERCION DE LA EVALUACION
                $id_student=$student_group["id"];
               $qery_insert_plscore="
                INSERT INTO pl_scores 
                (create_uid, create_date, write_date, write_uid, literal,
                score, pl_evaluation_criteria_id, pl_subject_id, op_student_id, pl_curriculum_id, 
                op_division_id,active)
                VALUES 
                ($user_id, '$fecha', '$fecha', $user_id, NULL, 
                NULL, $evaluacion_id, $evaluacion_pl_subject, $id_student, $evaluacion_curriculum,
                 $grupo, 't');";
                $ex_evaluaciones = pg_query($qery_insert_plscore) or die("Tenemos un error con la base de datos, intenta mas tarde #6: $qery_insert_plscore" . pg_last_error());
             $html.="<tr>"
                     . "<td>". $student_group["name"] ."</td>"
                     . "<td>". $evaluacion_subject ."</td>"//pendiente
                     . "<td>". $evaluacion_name."</td>"
                     . "</tr>";
        }
    }
    $arr_student_plscore=array();
    }

  }
    
   

 return $html;     
}