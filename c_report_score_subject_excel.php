<?php 
require_once('../config/conection.php'); 
require_once('../config/utils.php');
require_once('../config/vars.php'); 
require_once('../lib/Classes/PHPExcel.php');
require_once('../lib/Classes/PHPExcel/Reader/Excel2007.php');
require_once('../lib/Classes/PHPExcel/IOFactory.php');

session_start();
$name= $_SESSION['name'];
$id_faculty=$_SESSION['faculty_id'];
echo("<div class='usuario'> ".utf8_decode($name) . " ".$id_faculty."</div>") ;
if(empty($name)){
   //no esta logeado, retorna al login 
   header('Location: ../index.php');
}

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="book.xlsx"');
header('Cache-Control: max-age=0');

$inputFileName = '../lib/book.xlsx';
// Creamos un objeto PHPExcel
$objPHPExcel = new PHPExcel();

// Leemos un archivo Excel 2007
$objReader = PHPExcel_IOFactory::createReader('Excel2007');
$objPHPExcel = $objReader->load($inputFileName);
// Indicamos que se pare en la hoja uno del libro
$objPHPExcel->setActiveSheetIndex(0);
$columna = 0;
$fila = 1;
//ESTILOS
 $arr_letras_col=['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ'];

             
//recibo variables para mostrar ddatos
 $materia = $_GET["materia"]; 
 $grupo = $_GET["grupo"] ;
 $ciclo = $_GET["ciclo"] ;

 $arr_calificaciones=[];
 $arr_faltas=[];
//CONSULTAR LOS PERIODOS
 
 if(empty($grupo)){
     $query_periodos="select name,sec.id, sec.pl_curriculum_id as curriculum
                            from pl_section sec
                                join pl_subject s on sec.pl_subject_id=s.id
                            where 
                                 s.op_subject_id=$materia
                                and sec.op_batch_id=$ciclo order by seq";
 }
 else{
 $query_periodos="select name,sec.id, sec.pl_curriculum_id as curriculum,sec.description
                    from pl_section sec
                    join pl_subject s on sec.pl_subject_id=s.id
                    where sec.op_division_id =$grupo
                          and s.op_subject_id=$materia
                          and sec.op_batch_id=$ciclo order by seq";
 }
 
 $ex_periodos = pg_query($query_periodos) or die('Tenemos un error con la base de datos 1 , intenta mas tarde: '.$query_periodos. pg_last_error());
 $columna=2;   
 $col_calif=2;
 $arr_calificaciones=[];
     $arr_faltas=[];
 while ($periodos = pg_fetch_array($ex_periodos, null, PGSQL_ASSOC)) {
     
       //CONSULTAR CALIFICACIONES POR PERIODOS 
        $periodo=$periodos["id"];
        $nombre_periodo=$periodos["name"];
        $curriculum=$periodos["curriculum"];
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna,$fila,$nombre_periodo);
        
        if (empty($grupo)){
            $query_calif="select
                                se.id as id_sec_est,id_number as matricula,
                                coalesce(st.middle_name,' ')||' '||coalesce(st.last_name,' ')||' '||coalesce(res.name,' ') as alumno,
                                score as calificacion,aux1_int
                            from pl_section sec
                                join pl_subject s on sec.pl_subject_id=s.id
                                join pl_section_student se on se.pl_section_id=sec.id
                                JOIN op_student st on se.op_student_id=st.id
                                JOIN res_partner res on res.id=st.partner_id
                            where 
                                 s.op_subject_id= $materia
                                and sec.op_batch_id= $ciclo
                                and se.pl_section_id= $periodo
                                and se.active=true 
                                and res.active=true 
                            order by roll_number;";
        }else{
            $query_calif="select
                        se.id as id_sec_est,id_number as matricula,
                        coalesce(st.middle_name,' ')||' '||coalesce(st.last_name,' ')||' '||coalesce(res.name,' ') as alumno,
                        score as calificacion  ,aux1_int
                    from pl_section sec
                        join pl_subject s on sec.pl_subject_id=s.id
                        join pl_section_student se on se.pl_section_id=sec.id
                        JOIN op_student st on se.op_student_id=st.id
                        JOIN res_partner res on res.id=st.partner_id
                    where sec.op_division_id = $grupo
                        and s.op_subject_id= $materia
                        and sec.op_batch_id= $ciclo
                        and se.pl_section_id= $periodo
                        and  se.active=true 
                        and res.active=true  
                    order by roll_number";
        }
        
 
    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0,2,"MATRICULA");
    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(1,2,"ALUMNO");
   
    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna,1,$nombre_periodo);
    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna,2,"E");
    $columna++;
    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna,1,$nombre_periodo);
    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna,2,"F");
    $columna++;
    $ex_calificaciones = pg_query($query_calif) or die('Tenemos un error con la base de datos 2 , intenta mas tarde: '.$query_calif. pg_last_error());
    $fila=3;
        
    while ($calificaciones = pg_fetch_array($ex_calificaciones, null, PGSQL_ASSOC)) {
        $matricula=$calificaciones["matricula"];
        $alumno=$calificaciones["alumno"];
        $calificacion=$calificaciones["calificacion"];
        $faltas=$calificaciones["aux1_int"];
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0,$fila,$matricula);
       
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(1,$fila,$alumno);
       
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col_calif,$fila,$calificacion);
        if(! in_array ($arr_letras_col[$col_calif], $arr_calificaciones)){
            $arr_calificaciones[]=$arr_letras_col[$col_calif];
        }
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col_calif+1,$fila,$faltas);
        
        if(! in_array ($arr_letras_col[$col_calif+1], $arr_faltas)){
            $arr_faltas[]=$arr_letras_col[$col_calif+1];
        }
       
        
        $fila++;
    }
    
     $col_calif+=2;
 }       
 
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna,1,"FINAL");
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna,2,"PROMEDIO");
        
        for ($i=3; $i<$fila;$i++){
            $form="";
            foreach ($arr_calificaciones as $key => $value) {
                $form.=$value ."$i,";
            }
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna,$i,"=TRUNC(AVERAGE(".substr($form, 0, -1)."),1)");
             //$objPHPExcel->getActiveSheet()->getStyle($arr_letras_col[$columna].$i)->getNumberFormat()->setFormatCode('0.0'); 
            $form="";
            
        }
        
        $columna++;
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna,1,"FINAL");
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna,2,"FALTAS");
        
        
        for ($i=3; $i<$fila;$i++){
            $form="";
            foreach ($arr_faltas as $key => $value) {
                $form.=$value ."$i+";
            }
           
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna,$i,"=".substr($form, 0, -1));
        }
        
        $objPHPExcel->getActiveSheet()->getColumnDimension("A")->setAutoSize(TRUE);
        $objPHPExcel->getActiveSheet()->getColumnDimension("B")->setAutoSize(TRUE);

        
        ob_clean();//Esta función desecha el contenido del búfer de salida. Con esto evitamos la basura dentro del archivo
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
         exit;
//IMPRIMIR EXCEL 


