<?php  require_once('../config/conection.php'); 
session_start();
$name= $_SESSION['name'];
$id_faculty=$_SESSION['faculty_id'];
echo("<div class='usuario'> ".$name . " ".$id_faculty."</div>") ;
if(empty($name)){
    echo("redirige a login");
   //no está logeado, retorna al login 
   header('Location: ../index.php');
}
$data = $_POST;
//hacer un for para insertar los datos

foreach($data  as $key => $score) {
    //split para sacar el periodo
    $arr_split_1=explode("_",$key);
    $periodo=$arr_split_1[1];
    //split para sacar la columna (a,b,c,d,e) e id
    $aux1=$arr_split_1[2];
    $arr_columna_id=explode("-",$aux1);
    $columna=$arr_columna_id[0];
    $id=$arr_columna_id[1];
    echo("columna ");
    echo("$columna\n");
    echo("id ");
    echo("$id\n");
    
    
    //revisamos que la columna y el ID tengan datos 
    if(!empty($columna) && !empty($id) ){
    //hacemos actualización
        $query = "UPDATE pl_sports_score set $columna = '$score' where id = $id;";
        $response = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
    }else{
        echo("X");
    }
}

  ?> 