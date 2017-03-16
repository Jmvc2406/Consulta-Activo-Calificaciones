<?php

require_once('../config/conection.php');
session_start();
$name = $_SESSION['name'];
$id_faculty = $_SESSION['faculty_id'];
if (!empty($name)) {
    $data = $_POST;
//var_dump($_POST);
//hacer un for para insertar los datos
    foreach ($data as $key => $score) {
        //hacer split en key para obtener el id de pl_section_student
        $arr_key = explode("-", $key);
        $id = $arr_key[1];
        if ($id > 0 && !empty($id)) {
            //si el id no est� vacio, valida que sea un numero
            if ($arr_key[0] == 'score') {
                if (is_numeric($score)) {
                    $query = "UPDATE pl_scores SET score=$score WHERE id=$id;";
                    $execute = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
                } else if ($score == "") {
                    $query = "UPDATE pl_section_student SET score=null WHERE id=$id";
                    $execute = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
                } else {
                    echo("NO ACTUALIZA NADA ");
                }
            } 
        }
    }
}