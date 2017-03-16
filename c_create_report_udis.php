 <?php
 
function get_headers_table($curriculum,$standard){
        $arr_subject_hader=array();//array para encabezados       
        $arr_general=array();//array para guardar todos los datos
        $query_subject_header = "select ss.id,ss.name,ss.code  
                                    from pl_curriculum c 
                                    join pl_subject s on c.id=s.pl_curriculum_id
                                    join pl_etapas e on e.id=s.pl_etapas_id
                                    join op_subject ss on ss.id=s.op_subject_id
                                  where c.id=$curriculum and ss.op_standard_id=$standard
                                  order by e.code,ss.code;
        ";
        
        
        $ex_subject_header = pg_query($query_subject_header) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
        //ITERAMOS ENCABEZADO
         while ($subject = pg_fetch_array($ex_subject_header, null, PGSQL_ASSOC)) {
             //guardamos el encabezado en un array
                $arr_subject=array();
                $arr_subject["name"]=$subject["name"];
                $arr_subject["code"]=$subject["code"];
                $arr_subject["id"]  =$subject["id"];
                $arr_subject_hader[]=$arr_subject;
                
         } //fin while materias
         //AGREGAR COLUMNA DE PROMEDIO
        $arr_subject=array();
        $arr_subject["name"]="PROM";
        $arr_subject["code"]="PROM";
        $arr_subject["id"]  =0;
        $arr_subject_hader[]=$arr_subject;

        return $arr_subject_hader;
    }      

 function get_subjects_no_average($curriculum){
      //OBTENER ID DE MATERIAS QUE NO DEBEN PROMEDIARSE Y LAS GUARDAMOS EN UN ARRAY
         $arr_noaverage=array();
         $query_noaverage = "SELECT op_su.id FROM
                                pl_subject pl_su
                            JOIN op_subject op_su ON op_su.id=pl_su.op_subject_id
                            where pl_curriculum_id=$curriculum and vertical_average=false;
                                ";
            $ex_noaverage = pg_query($query_noaverage) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
             while ($subject_noaverage = pg_fetch_array($ex_noaverage, null, PGSQL_ASSOC)) {  
                $arr_noaverage[]=$subject_noaverage["id"];
            }
          return   $arr_noaverage;
     } 
     
     
     //MAIN
  function get_sudent_prom($arr_subject_hader,$batch,$curriculum,$division,$periodo,$standard,$arr_noaverage){
        $arr_prom_lit=array();
        $arr_prom_lit["LI"]=10;$arr_prom_lit["IA"]=10;
        $arr_prom_lit["LA"]=9;$arr_prom_lit["AH"]=9;
        $arr_prom_lit["LP"]=7;$arr_prom_lit["PA"]=7;
        $arr_prom_lit["PD"]=5;$arr_prom_lit["SD"]=5;
               // tomar una materia y sacar los alumnos. Ordernar por roll_number (numero de lista)
        $subject_aux=$arr_subject_hader[0]["id"];
        $arr_general=array();
        if($subject_aux != ""){
            
            $query_students = "select 
                                stu.id,
                                stu.id_number,
                                COALESCE(stu.middle_name,' ')|| ' ' ||COALESCE(stu.last_name,' ') ||' '||res.name as alumno
                               from pl_section_student ss
                                join pl_section s on s.id=ss.pl_section_id
                                join pl_subject a on a.id=s.pl_subject_id
                                join op_student stu on stu.id=ss.op_student_id
                                join res_partner res on res.id=stu.partner_id
                               where ss.op_division_id=$division
                                and ss.op_batch_id=$batch
                                and s.seq=$periodo
                                and a.op_subject_id=$subject_aux
                                and ss.active=true 
                               ORDER by stu.roll_number;
                                ";
            $ex_students = pg_query($query_students) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
             while ($student = pg_fetch_array($ex_students, null, PGSQL_ASSOC)) {                
                //hacer consulta para obtener todas las calificaciones del alumno y guardarlas en array general ordenar por materia
                $arr_student=array();
                $arr_student_score=array();
                $arr_prom=array();
                
                $arr_student[]=$student["id_number"];
                $arr_student[]=$student["alumno"];
                
                $query_student_score = "select ss.score,aux1_text, a.op_subject_id as id_materia
                                        from pl_section_student ss
                                            join pl_section s on s.id=ss.pl_section_id
                                            join pl_subject a on a.id=s.pl_subject_id
                                            join pl_etapas e on e.id=a.pl_etapas_id
                                            join op_subject op on op.id=a.op_subject_id
                                            join op_student stu on stu.id=ss.op_student_id
                                            join res_partner res on res.id=stu.partner_id
                                        where ss.op_division_id=$division
                                            and ss.op_batch_id=$batch
                                            and s.seq=$periodo
                                            and ss.op_student_id=".$student["id"]."
                                            and s.pl_curriculum_id=$curriculum 
                                            and  ss.active=true     
                                        order by  e.code,op.code;
                ";
              
                $ex_student_score = pg_query($query_student_score) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());                    
               
                 while ($student_score = pg_fetch_array($ex_student_score, null, PGSQL_ASSOC)) {  
                      //CONVERTIR LETRAS A NUMEROS PARA PROMEDIAR
                     $score_aux=0;
                     $literal=trim(strtoupper($student_score["aux1_text"]));
                     if($student_score["score"]==""){
                         if(array_key_exists($literal,$arr_prom_lit)==1){
                                $score_aux =$arr_prom_lit[$literal];  
                         }
                         $arr_student[]=$literal;
                        //$arr_student[]=$score_aux;
                     }else{
                         $score_aux=$student_score["score"];
                         $arr_student[]=$score_aux;
                     }
                                           
                      //varificar si se tiene que promediar  
                      if(!in_array($student_score["id_materia"],$arr_noaverage)){
                          //no promediar si la calificaci�n es nula 
                          if($score_aux != ""){
                                $arr_prom[]=$score_aux;
                            }
                        }

                }//fin while score
             
                //AGREGAR AL FINAL EL PROMEDIO DE LAS CALIFICACIONES
                  if(count($arr_prom)> 0){
                       $size=count($arr_prom); 
                       $sum_prom=0;
                       foreach ($arr_prom as $element){
                           $sum_prom+=$element;
                        }  
                        $prom =  $sum_prom/$size;
                        $final_prom=truncateFloat($prom,2);
                        $arr_student[]= $final_prom;
                         
                     }//fin if promediar
                     
                //AGREGAR EL ALUMNO AL ARRAY GENERAL                  
                    $arr_general[]=$arr_student;
               
            }//fin while de estudiantes
         }//if materia vacia
  
    return $arr_general;
  }   
  
  function print_udis($batch,$curriculum,$division,$periodo,$standard){
      $html="";
    if(!empty($standard)){
         
          $arr_subject_hader=get_headers_table($curriculum,$standard);
          $arr_noaverage=get_subjects_no_average($curriculum);
          $arr_general=get_sudent_prom($arr_subject_hader,$batch,$curriculum,$division,$periodo,$standard,$arr_noaverage);
    if(count($arr_general)> 0){//si el array general est� lleno contruir la tabla
    

    //CONSTRUYENDO ENCABEZADOS
    $html.="<div class='table_grey' >
              <table>
                <tr>";
        $html.=" <td>Matricula</td>";
        $html.=" <td>Alumno</td>";
            foreach ($arr_subject_hader as $key=>$sub_array_header){
                // COLOCAR LAS PRIMERAS 3 LETRAS DE LA MATERIA, SI TIENE M�S DE 2 PALABRAS, COLOCAR LAS 3 LETRAS DE LA SIGUIENTE
                $arr_name=explode(" ",$sub_array_header["name"]);
                $size_arr_name=count($arr_name);
                $name_3l=($arr_name[0]=="PROM")?"PROM":mb_substr($arr_name[0],0,3,'UTF-8');
                $final_name=$name_3l;
                //TOMAR LAS 3 LETRAS DE LA SIGUIENTE PALABRA
                if($size_arr_name>1){
                   $aux2_name="";
                   $name2=strtoupper($arr_name[1]);
                   //haz un ciclo de 4 para saltar preposiciones
                   for($i=1;$i<5;$i++){
                       //si es preposicion no hagas nada
                       if($name2=="DE" || $name2=="LA" || $name2=="EL" || $name2=="AL"){
                           $var=0;
                        }//si no es prepocision, toma las 3 letras 
                        else{
                            $name2_3l=mb_substr($name2,0,3,'UTF-8');
                            $final_name.="<br> ".$name2_3l;
                            break;
                            }
                        if($size_arr_name>$i+1)
                            $name2=strtoupper($arr_name[$i]);
                    }
                }//fin count($arr_name)>1 
                                
                 $html.="<td title='".strtoupper($sub_array_header["name"])."'>".strtoupper($final_name)."</td>";
                 
            }//fin foreach header
           
        $html.="</tr>";  
        
         //++++++++++++++++++++++++++++++++++++
         //IMPRIMIR CUADRO DE CALIFICACIONES
         //++++++++++++++++++++++++++++++++++++
         
         $cont_aux=1;
        foreach ($arr_general as $key=>$alumnos){
            $html.="<tr>"; 
           // $html.="<td>$cont_aux</td>"; 
            foreach ($alumnos as $key_alumnos=>$calif){
                $html.="<td>".$calif."</td>";
            } 
            //$cont_aux++;
            $html.="</tr>"; 
        }//fin foreach2 alumnos
    }//fin array general
  }// fin empty array
  
  return $html;
}
