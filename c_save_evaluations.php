<?php
require_once('../config/conection.php'); 
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

session_start();
$name= $_SESSION['name'];
$id_faculty=$_SESSION['faculty_id'];
if(!empty($name)){
    $grupo      =$_POST["grupo"];
    $ciclo      =$_POST["ciclo"];
    $materia    =$_POST["materia"];
    $periodo    =$_POST["periodo"];
    $curriculum =$_POST["curriculum"];
    $code       = strtoupper($_POST["code"]);
    $seq        =$_POST["seq"];
    $perc       =$_POST["perc"];
    $desc       = strtoupper($_POST["description"]);
    $type       =$_POST["type"];
    $date       =date("Y-m-d H:i:s");
    $username   =$_SESSION['username'];
    
    
    $query_user = " select id from res_users where login ='$username'; ";
    $user="";
    $ex_user = pg_query($query_user) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
    echo("$query_user\n");
    while ($u = pg_fetch_array($ex_user, null, PGSQL_ASSOC)) {
        $user=$u["id"];
    }
    echo("user = $user\n");
    if ($user==""){
        $user=1;
    }
    
    if($type=="nuevo"){
        if($grupo=="null"){
            nuevo_registro_area($user);
        }else{
            nuevo_registro_materia($user);
        }

    }//fin if nuevo
    else{
        //solo actualizar el registro 
         $id       =$_POST["id_for_update"];
         $query_update_student_="UPDATE  public.pl_evaluation_criteria SET "
                 . " write_date='$date', "
                 . " write_uid='$user', "
                 . " formula='$perc', "
                 . " code='$code', "
                 . " pl_section_id='$periodo', "
                 . " seq ='$seq', "
                 . " description='$desc'"
                 . "where id=$id ";

        $ex_update_student_scores = pg_query($query_update_student_) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());

        echo("actualizados"); 
    }

    
}else{
    
    echo("usuario no loggeado");
    
}


function nuevo_registro_materia($user){
    
    $grupo      =$_POST["grupo"];
    $ciclo      =$_POST["ciclo"];
    $materia    =$_POST["materia"];
    $periodo    =$_POST["periodo"];
    $curriculum =$_POST["curriculum"];
    $code       =strtoupper($_POST["code"]);
    $seq        =$_POST["seq"];
    $perc       =$_POST["perc"];
    $desc       =strtoupper($_POST["description"]);
    $date       =date("Y-m-d H:i:s");
    
            //insertar a la tabla pl_evaluation_criteria
        $id_criteria=0;
        $query_insert_evaluation="INSERT INTO public.pl_evaluation_criteria (create_uid, create_date, write_date, write_uid, formula, code, pl_section_id, seq, description) VALUES ('$user', '$date', '$date', '$user', '$perc', '$code', '$periodo', '$seq', '$desc') returning id;";
        $ex_insert_evaluation = pg_query($query_insert_evaluation) or die("Tenemos un error con la base de datos, intenta mas tarde: $query_insert_evaluation" . pg_last_error());
       while ($u = pg_fetch_array($ex_insert_evaluation, null, PGSQL_ASSOC)) {
          $id_criteria=$u["id"];
        }

        if($id_criteria != 0) {
            //Obtener el pl_subject de la materia
            //;
            $subject_id=0;
            $query_plsubject="select id from pl_subject where op_subject_id=$materia and pl_curriculum_id=$curriculum;";
            $ex_plsubject = pg_query($query_plsubject) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
           while ($subject = pg_fetch_array($ex_plsubject, null, PGSQL_ASSOC)) {
              $subject_id=$subject["id"];           
            }
           if($subject_id != 0) {
                //Obtener el id de todos los alumnos del grupo y materia.
                $query_student="select
                                            st.id
                                        from pl_section sec
                                            join pl_subject s on sec.pl_subject_id=s.id
                                            join pl_section_student se on se.pl_section_id=sec.id
                                            JOIN op_student st on se.op_student_id=st.id
                                        where sec.op_division_id = $grupo
                                            and s.op_subject_id= $materia
                                            and sec.op_batch_id= $ciclo
                                            and se.pl_section_id= $periodo
                                            and se.active=true 
                                        order by roll_number;";
                $ex_students = pg_query($query_student) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
                while ($student = pg_fetch_array($ex_students, null, PGSQL_ASSOC)) {
                   $student_id=$student["id"];

                   // Guardar el id del alumno 
                   $query_insert_student_scores="INSERT INTO public.pl_scores 
                       (create_uid, create_date, write_date, 
                       write_uid, pl_evaluation_criteria_id, pl_subject_id, 
                       op_student_id, pl_curriculum_id, op_division_id,active) 
                       VALUES (
                       '$user', '$date', '$date', 
                       '$user', '$id_criteria', '$subject_id', 
                       '$student_id', '$curriculum', '$grupo','true')";

                   $ex_insert_student_scores = pg_query($query_insert_student_scores) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
                }
           }//fin pl_subject
         }//fin id criteria
    
    
    
}//fin nuevo registro materia

function nuevo_registro_area($user){
    
    $grupo      =$_POST["grupo"];
    $ciclo      =$_POST["ciclo"];
    $materia    =$_POST["materia"];
    $periodo    =$_POST["periodo"];
    $curriculum =$_POST["curriculum"];
    $code       =$_POST["code"];
    $seq        =$_POST["seq"];
    $perc       =$_POST["perc"];
    $desc       =$_POST["description"];
    $date       =date("Y-m-d H:i:s");
    
            //insertar a la tabla pl_evaluation_criteria
        $id_criteria=0;
        $query_insert_evaluation="INSERT INTO public.pl_evaluation_criteria (create_uid, create_date, write_date, write_uid, formula, code, pl_section_id, seq, description) VALUES ('$user', '$date', '$date', '$user', '$perc', '$code', '$periodo', '$seq', '$desc') returning id;";
        echo("$query_insert_evaluation");
        $ex_insert_evaluation = pg_query($query_insert_evaluation) or die("Tenemos un error con la base de datos, intenta mas tarde: $query_insert_evaluation" . pg_last_error());
       while ($u = pg_fetch_array($ex_insert_evaluation, null, PGSQL_ASSOC)) {
          $id_criteria=$u["id"];
        }

        if($id_criteria != 0) {
            //Obtener el pl_subject de la materia
            
            $subject_id=0;
            $query_plsubject="select id from pl_subject where op_subject_id=$materia and pl_curriculum_id=$curriculum;";
            $ex_plsubject = pg_query($query_plsubject) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
           while ($subject = pg_fetch_array($ex_plsubject, null, PGSQL_ASSOC)) {
              $subject_id=$subject["id"];           
            }
           if($subject_id != 0) {
                //Obtener el id de todos los alumnos del area
                $query_student= " select
                                st.id  
                            from pl_section sec
                                join pl_subject s on sec.pl_subject_id=s.id
                                join pl_section_student se on se.pl_section_id=sec.id
                                JOIN op_student st on se.op_student_id=st.id
                                JOIN res_partner res on res.id=st.partner_id
                            where 
                                 s.op_subject_id= $materia
                                and sec.op_batch_id= $ciclo
                                and se.pl_section_id= $periodo
                                and se.active=true         
                            order by roll_number;";
              
                $ex_students = pg_query($query_student) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
                while ($student = pg_fetch_array($ex_students, null, PGSQL_ASSOC)) {
                   $student_id=$student["id"];

                   // Guardar el id del alumno 
                   $query_insert_student_scores="INSERT INTO public.pl_scores 
                       (create_uid, create_date, write_date, 
                       write_uid, pl_evaluation_criteria_id, pl_subject_id, 
                       op_student_id, pl_curriculum_id, op_division_id,active) 
                       VALUES (
                       '$user', '$date', '$date', 
                       '$user', '$id_criteria', '$subject_id', 
                       '$student_id', '$curriculum', null,'true')";
                       
                   $ex_insert_student_scores = pg_query($query_insert_student_scores) or die("Tenemos un error con la base de datos, intenta mas tarde: $query_insert_student_scores " . pg_last_error());
                }
           }//fin pl_subject
         }//fin id criteria
    
}