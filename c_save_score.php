<?php  require_once('../config/conection.php'); 
session_start();
$name= $_SESSION['name'];
$id_faculty=$_SESSION['faculty_id'];
echo("<div class='usuario'> ".$name . " ".$id_faculty."</div>") ;
if(empty($name)){
    echo("redirige a login");
   //no est� logeado, retorna al login 
   header('Location: ../index.php');
}
ini_set("max_input_vars","3000");

$data = $_POST;

//hacer un for para insertar los datos
foreach($data  as $key => $score) {
    //hacer split en key para obtener el id de pl_section_student
    $arr_key=explode("-", $key);
    $id= $arr_key[1];
    if($id>0 && !empty($id)){
        
        //si el id no esta vacio, valida que sea un numero
        if($arr_key[0]=='score'){
            
             if(is_numeric($score)) {
                $query = "UPDATE pl_section_student SET score=$score WHERE id=$id;";
                $execute = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
                //echo "/n <br> ".$query;
             }
            else if ($score==""){
                $query = "UPDATE pl_section_student SET score=null WHERE id=$id";
                $execute = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
                //echo "/n <br> " .$query;
            }else
            {echo("NO ACTUALIZA NADA ");   }  
          }else{
             
                $aux_key="";
                $arr_aux_key=explode("_", $arr_key[0]);
                $aux_key= $arr_aux_key[1];
                if($aux_key=='text'){
                    $query = "UPDATE pl_section_student SET ".$arr_key[0]."='$score' WHERE id=$id";
                    $execute = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
                     //echo "/n <br> " .$query;
                }else{
                  if(is_numeric($score)) {
                      $query = "UPDATE pl_section_student SET ".$arr_key[0]."=$score WHERE id=$id";
                       $execute = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
                       // echo "/n <br> " .$query;
                    }
                    else if ($score==""){
                        $query = "UPDATE pl_section_student SET ".$arr_key[0]."=null WHERE id=$id";
                        $execute = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
                        // echo "/n <br> " .$query;
                    }  
                    else
                    {echo("NO ACTUALIZA NADA ");   }  
                }
              
            }  

        
        /*
        
        if(is_numeric($score)) {
            //revisar qe entre en el rango de calificacion asignada al plan?
            // si es score
            if($arr_key[0]=='score'){
                
                $query = "UPDATE pl_section_student SET score=$score WHERE id=$id;";
            }else{
                     $query = "UPDATE pl_section_student SET ".$arr_key[0]."=null WHERE id=$id";
                     $execute = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
                }
            
            if($arr_key[0]!='score'){
                        $query = "UPDATE pl_section_student SET ".$arr_key[0]."=$score WHERE id=$id";
                }else{
                         $query = "UPDATE pl_section_student SET ".$arr_key[0]."=null WHERE id=$id";
                         $execute = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
                    }
            
            $execute = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
            //echo($execute);
        }//fin numeric 
       else{
            if($arr_key[0]!='score'){
                $aux_key="";
                $arr_aux_key=explode("_", $arr_key[0]);
                $aux_key= $arr_aux_key[1];
		if($aux_key=='text'){
			echo(' score text: '.$score .'\n');
                    $query = "UPDATE pl_section_student SET ".$arr_key[0]."='$score' WHERE id=$id";
                    $execute = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
		}else{
                    if($score!=""){
                         echo(' score int: '.$score .'\n');
                         $query = "UPDATE pl_section_student SET ".$arr_key[0]."=$score WHERE id=$id";
                          $execute = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
                      }else{
                            $query = "UPDATE pl_section_student SET ".$arr_key[0]."=null WHERE id=$id";
                            $execute = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
                          }    
		}
            }
            
            */       
            
        }//id no vacio
        else{
            echo("id vacio");
            }
}//fin foreach



  ?> 