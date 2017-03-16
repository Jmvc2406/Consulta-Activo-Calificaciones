<?php 
 require_once('../config/conection.php'); 
//  AQUI VALIDAREMOS USUARIO. SINO RETORNAR A LOGIN
$type = isset($_POST['type']) ? $_POST['type'] :"";
$value = isset($_POST['value']) ? $_POST['value'] : "";

if($value!=""){
    //consulta para retornar el 
    if($type=="batch"){
            $html="";
            $html.=' <option value="null"> -- SELECCIONA EL GRUPO --</option>';
            $query_batch = "
                SELECT a.division_id AS id,
                a.name,
                b.id AS batch_id,
                b.name AS ciclo
               FROM op_allocat_division a
                 JOIN op_course c ON c.id = a.course_id
                 JOIN op_batch b ON b.course_id = c.id
               WHERE  b.id= $value order by  a.name
                 ;";
            
            $batch = pg_query($query_batch) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
            while ($batch_ = pg_fetch_array($batch, null, PGSQL_ASSOC)) {
               $html.=('<option value="'.$batch_["id"].'">'.$batch_["name"].'</option>');
            }//fin while
           
            echo($html);
    }//fin if batch
    if($type=="periodo"){
            $html="";
            $html.=' <option value="null"> -- SELECCIONA EL PERIODO --</option>';
            $query_periodo = "select a,b,c,d,e,f from pl_curriculum where id=$value";
            
            $periodo = pg_query($query_periodo) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
           
            while ($periodo_ = pg_fetch_array($periodo, null, PGSQL_ASSOC)) {
               
               $html.=$periodo_["a"]==""?"":('<option value="1">'.$periodo_["a"].'</option>');
               $html.=$periodo_["b"]==""?"":('<option value="2">'.$periodo_["b"].'</option>');
               $html.=$periodo_["c"]==""?"":('<option value="3">'.$periodo_["c"].'</option>');
               $html.=$periodo_["d"]==""?"":('<option value="4">'.$periodo_["d"].'</option>');
               $html.=$periodo_["e"]==""?"":('<option value="5">'.$periodo_["e"].'</option>');
               $html.=$periodo_["f"]==""?"":('<option value="6">'.$periodo_["f"].'</option>');               
               
            }//fin while
           
            echo($html);
        
        
        }
}else{
    echo("no hay nada en value");
    }
?>