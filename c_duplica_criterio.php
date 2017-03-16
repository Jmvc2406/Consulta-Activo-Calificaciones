<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once('../config/conection.php');
//  AQUI VALIDAREMOS USUARIO. SINO RETORNAR A LOGIN
session_start();
$name = $_SESSION['name'];
if (!empty($name)) {
// con la opcion show se muestran los datos 
    $type = $_POST["type"];
    $batch = $_POST["batch"];
    $grupo = $_POST["grupo"];
    $materia = $_POST["materia"];
    $pl_section_from = $_POST["pl_section_from"];
    $pl_section_to = $_POST["pl_section_to"];

    //echo(" type=$type  - batch= $batch -  grupo= $grupo - materia= $materia  - pl_section_from=$pl_section_from - pl_section_to=$pl_section_to");

    if ($pl_section_from == $pl_section_to) {
        echo("2");
        exit();
    }

    if ($type == "show") {
        $pl_section = $pl_section_from;
        $batch = $_POST["batch"];
        $arr = show_criteria($pl_section, $batch, $grupo);
        if (!empty($arr)) {
            $html = "";
            $html.="  
         <div class='table_grey'>   
           <table>
             <tr>
                 <td>Criterio</td>
                 <td>Descripcion</td>
                 <td>Materia</td>
                 <td>Grupo</td>
             </tr>

           ";
            foreach ($arr as $criterios_) {
                $html.="<tr>";
                $html.="<td>";
                
                $html.=$criterios_["criterio"];
                $html.="</td>";
                $html.="<td>";
                $html.=$criterios_["description"];
                $html.="</td>";
                $html.="<td>";
                $html.=$criterios_["materia"];
                $html.="</td>";
                $html.="<td>";
                $html.=$criterios_["grupo"];
                $html.="</td>";
                $html.="</tr>";
            }

            $html.="</table>"
                    . " </div>";

            echo($html);
        } else {
            echo("NO HAY EVALUACIONES PARA COPIAR");
        }
    } else if ($type == "duplicate") {
        // obtener todos los alumnos de el grupo
        $arr_student = getStudent($grupo);
        //hacer la consulta para traer todos los criterios (from) // de la funcion
        $arr_criterios = show_criteria($pl_section_from, $batch, $grupo);
        //hacer consulta para obtener el periodo al que copiaremos (to)
        $seq_to = getPlSection($pl_section_to);
        $seq_from = getPlSection($pl_section_from);
        // crear el registro pl_evaluation_criteria para cada criterio
        foreach ($arr_criterios as $key => $criterio_nuevo) {
            $user = getUser();
            $date = date("Y-m-d H:i:s");
            $formula = $criterio_nuevo["formula"];
            $criterio = $criterio_nuevo["criterio"];
            $description = $criterio_nuevo["description"];
            $materia_id = $criterio_nuevo["materia_id"];
            $seq_criterio = $criterio_nuevo["seq"];
            $curriculum = $criterio_nuevo["curriculum"];
            $pl_subject_id = $criterio_nuevo["pl_subject_id"];


            //revisar si  el criterio existe
            $exist_criteria = false;
            $criterio_existente='';
            $query_exist_criteria = "SELECT
                                       C.id, c.code, c.description
                                    FROM
                                        pl_evaluation_criteria C
                                    JOIN pl_section P
                                    ON C.pl_section_id = P . ID
                                    where
                                        p.seq = $seq_to AND
                                        op_batch_id = $batch and 
                                        op_subject_id=$materia_id and
                                        op_division_id=$grupo and"
                                        . " c.code= '$criterio' and c.description= '$description' and c.seq= '$seq_criterio';";
            echo($query_exist_criteria." \n ");
            $ex_exist_criteria = pg_query($query_exist_criteria) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
            while ($exist_criteria_obj = pg_fetch_array($ex_exist_criteria, null, PGSQL_ASSOC)) {
                $exist_criteria = true;
                $criterio_existente=$exist_criteria_obj["code"];
                $id_criterio_existente=$exist_criteria_obj["id"];
            }

            if (!$exist_criteria) {
                //sacar el pl_section correspondiente a la seq del to 
                $pl_section_nuevo = 0;
                $query_getplsection = "SELECT 
                                    id
                                 FROM pl_section 
                                 WHERE
                                    seq = $seq_to AND
                                    op_batch_id = $batch and 
                                    op_subject_id= $materia_id and
                                    op_division_id=$grupo;";
                $ex_plsection = pg_query($query_getplsection) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
                while ($plsection = pg_fetch_array($ex_plsection, null, PGSQL_ASSOC)) {
                    $pl_section_nuevo = $plsection["id"];
                }

                if ($pl_section_nuevo != 0) {
                    $query_insert_pl_criteria = "INSERT INTO pl_evaluation_criteria (
                                            create_uid,create_date,
                                            write_date,write_uid,formula,
                                            code,pl_section_id,seq,
                                            description
                                        )
                                        VALUES
                                        (
                                            '$user','$date',
                                            '$date','$user','$formula',
                                            '$criterio','$pl_section_nuevo','$seq_criterio',
                                            '$description'
                                        )RETURNING ID;";

                    $ex_insert_pl_criteria = pg_query($query_insert_pl_criteria) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
                    $arr_nuevo_criterio = pg_fetch_array($ex_insert_pl_criteria, null, PGSQL_ASSOC);
                    $id_nuevo_criterio = $arr_nuevo_criterio["id"];
                    // con el id crear los registros de los alumnos
                    echo("NUEVO CRITERIO: $id_nuevo_criterio");
                    if ($id_nuevo_criterio || $id_nuevo_criterio != "") {
                        foreach ($arr_student as $student) {
                            $id_student = $student["id"];
                            $query_insert_plscore = "INSERT INTO public.pl_scores (
                                            create_uid, create_date, write_date, write_uid,
                                             score, pl_evaluation_criteria_id, pl_subject_id,
                                            op_student_id, pl_curriculum_id, op_division_id, active
                                        )
                                        VALUES
                                        (
                                             '$user','$date','$date','$user',
                                             NULL,'$id_nuevo_criterio','$pl_subject_id',
                                             '$id_student','$curriculum','$grupo','t'
                                        );";
                            $ex_insert_plscore = pg_query($query_insert_plscore) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
                        }
                    }
                } else {
                    echo("0");
                }
            }else{
                echo("\n Ya existe criterio $id_criterio_existente  $criterio_existente NO LO CREO \n");
            }

        //exit();
            //validar que si en to ya hay cosas, no crear nada 
        }
    }
} else {
    echo("1");
}

function show_criteria($pl_section, $batch, $grupo, $pl_section_from) {
    //obtener la secuencia
    $array_all_criteria = array();
    $seq = getPlSection($pl_section);
    $query_criteria = "SELECT
               C.id, C.code as criterio,c.description,c.seq,
               c.formula,S.name as materia,D.name as grupo,S.id as materia_id,
               P.pl_curriculum_id as curriculum, pl_subject_id
            FROM
                pl_evaluation_criteria C
                JOIN pl_section P ON C.pl_section_id=P.id
                JOIN op_subject S ON S.id=P.op_subject_id
                join op_division D on D.id=p.op_division_id
            WHERE
            P.seq = $seq
            AND op_batch_id = $batch
                and D.id=$grupo
            order by D.name, S.code";
    $ex_criteria = pg_query($query_criteria) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
    $cont = 0;
    while ($criteria = pg_fetch_array($ex_criteria, null, PGSQL_ASSOC)) {

        $array_all_criteria[$cont]["id"] = $criteria["id"];
        $array_all_criteria[$cont]["criterio"] = $criteria["criterio"];
        $array_all_criteria[$cont]["description"] = $criteria["description"];
        $array_all_criteria[$cont]["materia"] = $criteria["materia"];
        $array_all_criteria[$cont]["materia_id"] = $criteria["materia_id"];
        $array_all_criteria[$cont]["grupo"] = $criteria["grupo"];
        $array_all_criteria[$cont]["seq"] = $criteria["seq"];
        $array_all_criteria[$cont]["formula"] = $criteria["formula"];
        $array_all_criteria[$cont]["curriculum"] = $criteria["curriculum"];
        $array_all_criteria[$cont]["pl_subject_id"] = $criteria["pl_subject_id"];
        $cont++;
    }
    return $array_all_criteria;
}

function getPlSection($id) {
    $seq = 0;
    $query_seq = "SELECT seq from pl_section where id = $id";
    $ex_seq = pg_query($query_seq) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
    while ($sequence = pg_fetch_array($ex_seq, null, PGSQL_ASSOC)) {
        $seq = $sequence["seq"];
    }
    return $seq;
}

function getStudent($division) {
    $arr_student = array();
    $query_student = "select * from op_student where division_id=$division";
    $ex_student = pg_query($query_student) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
    while ($student = pg_fetch_array($ex_student, null, PGSQL_ASSOC)) {
        $arr_student[]["id"] = $student["id"];
    }
    return $arr_student;
}

function getUser() {
    $user = $_SESSION['username'];
    $id_user = 1;
    $query_user = "select * from res_users where login='$user'";
    $ex_user = pg_query($query_user) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
    while ($user = pg_fetch_array($ex_user, null, PGSQL_ASSOC)) {
        $id_user = $user["id"];
    }

    return $id_user;
}
