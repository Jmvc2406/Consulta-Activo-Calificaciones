<?php  require_once('../config/conection.php'); 
session_start();
$name= $_SESSION['name'];
if(empty($name)){
    echo("no esta logueado");
}
echo("vamos a guardar la descripcion; en php");
//GUARDAR LOS DATOS 
$description    = isset($_POST['description']) ? $_POST['description'] : "";
$periodo        = isset($_POST['periodo']) ? $_POST['periodo'] : "";
$type           = isset($_POST['type']) ? $_POST['type'] : "";
$id             = isset($_POST['id']) ? $_POST['id'] : "";


if($periodo != "" && $id != "" ){
    //ACTUALIZAR DE ACUERDO AL TIPO
    if($type=="deportes"){
        $arr_columnas=array();
        $arr_columnas[1]="a";   $arr_columnas[4]="d";
        $arr_columnas[2]="b";   $arr_columnas[5]="e";
        $arr_columnas[3]="c";
        
        $columna="description_". $arr_columnas[$periodo];
        $query = "update pl_sports set $columna = '$description' where id = $id; ";
        $action = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
        echo($action);
                
    }
    if($type=="materias"){
        
        $query  = "update pl_section set description='$description' where id=$id;";
        $action = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
        echo($action);
        
        }
 }else{
   echo("datos incompletos");
    }

  ?> 