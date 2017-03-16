<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function secciones($faculty_id, $batch) {
    $html="";
      //----------------------------------------------
        //CONSULTA Y CONSTRUCCI�N DE TABLA PARA CURSOS 
        //----------------------------------------------
    if($faculty_id=="admin"){
          $query = "select c.id as curso_id,c.code as code ,c.order as seq , c.name as curso, cs.op_batch_id as ciclo_id, s.name as materia
                    from pl_curso c join 
                        pl_curso_student cs on c.id=cs.pl_curso_id join
                        op_subject s on c.op_subject_id= s.id
                    where 
                        cs.op_batch_id=$batch  
                       
                        order by materia,seq";
          }
       else{ 
         $query = "select * from pl_secciones where faculty_id=$faculty_id and batch_id=$batch ";
        }    
                                                   
       //validaicon
       $ex_secciones = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
       $hay_seccion=pg_fetch_array($ex_secciones, null, PGSQL_ASSOC);       
       if($hay_seccion){
               $secciones = pg_query($query) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
               $html.=" <div class='title3'><br> SECCIONES </div>
                        <table><tr><td style='width: 12%;'>SECCION </td><td>MATERIA</td></tr>";
               $arr_imprime_materia=array();
                while ($seccion = pg_fetch_array($secciones, null, PGSQL_ASSOC)) {
                
                    $html.="<tr>";
                    $html.="<td>".$seccion["seccion"]."</td>";
                    $html.="<td> <a href='periodos_secciones.php?&ciclo=".$seccion["batch_id"]."&seccion=".$seccion["id"]."'> ".$seccion["materia"]."</a></td>";
                    $html.="</tr>";                
                    
                }//fin while
                $html.="</table>";//fin tabla cursos
        }// fin if hay curso      
        return $html;
        
        
        
}