<?php
require_once('../config/conection.php'); 
//  AQUI VALIDAREMOS USUARIO. SINO RETORNAR A LOGIN
//verificar si las variables vienen por POST o por GET 
$get=$_GET;
$post=$_POST;
if(count($post)>0){
    $username = isset($_POST['username']) ? $_POST['username'] : NULL;
    $password = isset($_POST['password']) ? $_POST['password'] : NULL;
}else if(count($get)>0){
    $username =$_GET['login'];
    $password =  base64_decode($_GET['password']);
}

$admin=false;
$login=0;
$faculty_id=0;
$partner_id=0;
$name="";
$isadmin=true;
if(empty($username) || empty($password)){
     echo 0;
}
else{
        //verificar que el usuario sea admin
        $query = "select g.name, us.login, us.password , us.partner_id , r.display_name
                    from 
                        res_groups g join res_groups_users_rel u  on u.gid=g.id
                        join res_users us on u.uid=us.id
                        join res_partner r on us.partner_id= r.id
                  where 
                    us.login='$username' 
                    and us.password='$password'
                    and 
                    (g.name like '%Preescolar%'
                    or g.name like '%Secundaria%' 
                    or g.name like '%PrimariaMix%'
                    or g.name like '%Preparatoria%'
                    or g.name like '%Deporte%');";
          $result_admin = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
          $admin= pg_fetch_array($result_admin, null, PGSQL_ASSOC);    

       if($admin){
           $isadmin=true;
           $login=1; //1 es admin
           //guardar datos en sesion
            session_start();
            $ses_id = session_id();
            $_SESSION['username'] = $username;
            $_SESSION['password'] = $password;
            $_SESSION['partner_id'] = $admin['partner_id'];
            $_SESSION['name'] = $admin['display_name'];
            $_SESSION['isadmin'] = true;
            
            //revisar si el usuario es profesor para agregar a la sesion faculty
            
        $query_faculty = "select g.name, us.login , us.partner_id , f.id as faculty_id
                            from 
                                res_groups g join res_groups_users_rel u  on u.gid=g.id
                                join res_users us on u.uid=us.id
                                join op_faculty f on f.partner_id=us.partner_id
                            where 
                                us.login='$username' and 
                                (g.name like '%Faculty%' or g.name like '%Profesor%');";
                                
          $result_faculty = pg_query($query_faculty)or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
          $faculty= pg_fetch_array($result_faculty, null, PGSQL_ASSOC);  
          if($faculty){
              $_SESSION['faculty_id'] = $faculty["faculty_id"];
              $_SESSION['is_faculty'] = true;
           }else{
                  $_SESSION['is_faculty'] = false;
                  $_SESSION['faculty_id']="";
            }  
        }else{
            //verificar que los datos de usuario sean correctos
            $query = "select f.id as faculty_id, r.partner_id as partner_id
                      from res_users r join op_faculty f on f.partner_id=r.partner_id 
                      where r.login= '$username' 
                        and r.password = '$password';";
            $result = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
             while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
                    $login=2;//e2 es profe
                    $faculty_id=$line["faculty_id"];
                    $partner_id=$line["partner_id"];
        
                }
                if($login==2){
                        $query = "select display_name from res_partner where id =$partner_id;";
                        $result_name = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());

                        //revisar si hay resultado

                        while ($name_p = pg_fetch_array($result_name, null, PGSQL_ASSOC)) {
                            $name=$name_p['display_name'];
                        }
                       
                        session_start();
                        $ses_id = session_id();
                        $_SESSION['username'] = $username;
                        $_SESSION['password'] = $password;
                        $_SESSION['faculty_id'] = $faculty_id;
                        $_SESSION['partner_id'] = $partner_id;
                        $_SESSION['name'] = $name;
                        $_SESSION['isadmin'] = false;
                        
                    }
        }        
           
    //Guardar datos en la sesion
   
   
        echo $login;
}
