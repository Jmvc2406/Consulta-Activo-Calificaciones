<?php
require_once('../config/conection.php'); 
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

session_start();
$name= $_SESSION['name'];
if(!empty($name)){
    $id_criteria      =$_POST["id_criteria"];    
    if($id_criteria!= ""){
        $query_pl_scores = "delete from pl_scores where pl_evaluation_criteria_id=$id_criteria";
        $ex_scores= pg_query($query_pl_scores) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
        
        $query_pl_criteria = "delete from pl_evaluation_criteria where id=$id_criteria";
        $ex_criterua = pg_query($query_pl_criteria) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
    }
    
}else{
    
    echo("usuario no loggeado");
    
}

