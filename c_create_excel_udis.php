<?php
include_once ('c_create_excel_udis_numero.php');

function get_headers_table($curriculum, $standard) {
    $arr_subject_hader = array(); //array para encabezados
    $arr_general = array(); //array para guardar todos los datos
    $query_subject_header = "select ss.id,ss.name,ss.code  from pl_curriculum c 
                                    join pl_subject s on c.id=s.pl_curriculum_id
                                    join pl_etapas e on e.id=s.pl_etapas_id
                                    join op_subject ss on ss.id=s.op_subject_id
                                  where c.id=$curriculum and ss.op_standard_id=$standard
                                  order by  e.code,ss.id;
        ";

    $ex_subject_header = pg_query($query_subject_header) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
    //ITERAMOS ENCABEZADO
    while ($subject = pg_fetch_array($ex_subject_header, null, PGSQL_ASSOC)) {
        //guardamos el encabezado en un array
        $arr_subject = array();
        $arr_subject["name"] = $subject["name"];
        $arr_subject["code"] = $subject["code"];
        $arr_subject["id"] = $subject["id"];
        $arr_subject_hader[] = $arr_subject;
    } //fin while materias
    return $arr_subject_hader;
}

function get_subjects_no_average($curriculum) {
    //OBTENER ID DE MATERIAS QUE NO DEBEN PROMEDIARSE Y LAS GUARDAMOS EN UN ARRAY
    $arr_noaverage = array();
    $query_noaverage = "SELECT op_su.id FROM
                                pl_subject pl_su
                            JOIN op_subject op_su ON op_su.id=pl_su.op_subject_id
                            where pl_curriculum_id=$curriculum and vertical_average=false;
                                ";
    $ex_noaverage = pg_query($query_noaverage) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
    while ($subject_noaverage = pg_fetch_array($ex_noaverage, null, PGSQL_ASSOC)) {
        $arr_noaverage[] = $subject_noaverage["id"];
    }

    return $arr_noaverage;
}


function get_sudent_prom($arr_subject_hader, $batch, $curriculum, $division, $periodo, $standard, $arr_noaverage) {
    // tomar una materia y sacar los alumnos. Ordernar por roll_number (numero de lista)
    $subject_aux = $arr_subject_hader[0]["id"];
    $arr_general = array();
    if ($subject_aux != "") {

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
            $arr_student = array();
            $arr_student_score = array();
            $arr_prom = array();
            //$total_alumnos++;

            $arr_student[] = $student["id_number"];
            $arr_student[] = $student["alumno"];

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
                                            and ss.op_student_id=" . $student["id"] . "
                                            and s.pl_curriculum_id=$curriculum
                                            and ss.active=true 
                                         order by  e.code,op.code;
                ";
            $ex_student_score = pg_query($query_student_score) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());

            while ($student_score = pg_fetch_array($ex_student_score, null, PGSQL_ASSOC)) {

                $arr_student[] = ($student_score["score"] == "" ? $student_score["aux1_text"] : $student_score["score"]);

                //varificar si se tiene que promediar  
                if (!in_array($student_score["id_materia"], $arr_noaverage)) {
                    //no promediar si la calificaci�n es nula 
                    if ($student_score["score"] != "") {
                        $arr_prom[] = $student_score["score"];
                    }
                }
            }//fin while score
            //AGREGAR AL FINAL EL PROMEDIO DE LAS CALIFICACIONES
            if (count($arr_prom) > 0) {
                $size = count($arr_prom);
                $sum_prom = 0;
                foreach ($arr_prom as $element) {
                    $sum_prom+=$element;
                }
                $prom = $sum_prom / $size;
                $final_prom = truncateFloat($prom, 2);
                $arr_student[] = $final_prom;
            }//fin if promediar
            //AGREGAR EL ALUMNO AL ARRAY GENERAL                  
            $arr_general[] = $arr_student;
        }//fin while de estudiantes
    }//if materia vacia

    return $arr_general;
}

//MAIN
function print_udis_excel($batch, $curriculum, $division, $periodo, $standard) {
    $html = "";
    if (!empty($standard)) {
        $arr_subject_hader = get_headers_table($curriculum, $standard);
        $arr_noaverage = get_subjects_no_average($curriculum);
        $arr_general = get_sudent_prom($arr_subject_hader, $batch, $curriculum, $division, $periodo, $standard, $arr_noaverage);
        if (count($arr_general) > 0) {//si el array general esta lleno contruir la tabla
//CREAMOS OBJETOS PARA EXCEL
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

     //ESTILOS
     $estiloTituloReporte = array(
    'font' => array(
        'name'      => 'Verdana',
        'bold'      => true,
        'italic'    => false,
        'strike'    => false,
        'size' =>16,
        'color'     => array(
            'rgb' => 'FFFFFF'
        )
    ),
    'fill' => array(
        'type'  => PHPExcel_Style_Fill::FILL_SOLID,
        'color' => array(
            'argb' => 'FF220835')
    ),
    'borders' => array(
        'allborders' => array(
            'style' => PHPExcel_Style_Border::BORDER_NONE
        )
    ),
    'alignment' => array(
        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
        'rotation' => 0,
        'wrap' => TRUE
    )
);
 
$estiloTituloColumnas = array(
    'font' => array(
        'name'  => 'Arial',
        'bold'  => true,
        'size'  =>   8,
        'color' => array(
            'rgb' => '000000'
        )
    ),
    'fill' => array(
        'type'       => PHPExcel_Style_Fill::FILL_SOLID,
        'startcolor' => array(
            'rgb' => 'C4C2C2'
        ),
    ),
    'borders' => array(
        'top' => array(
            'style' => PHPExcel_Style_Border::BORDER_MEDIUM ,
            'color' => array(
                'rgb' => '143860'
            )
        ),
        'bottom' => array(
            'style' => PHPExcel_Style_Border::BORDER_MEDIUM ,
            'color' => array(
                'rgb' => '143860'
            )
        )
    ),
    'alignment' =>  array(
        'horizontal'=> PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        'vertical'  => PHPExcel_Style_Alignment::VERTICAL_CENTER,
        'wrap'      => TRUE
    )
);
 
$estiloInformacion = new PHPExcel_Style();
$estiloInformacion->applyFromArray( array(
    'font' => array(
        'name'  => 'Arial',
        'size'  =>   8,
        'color' => array(
            'rgb' => '000000'
        )
    ),  
    'borders' => array(
        'left' => array(
            'style' => PHPExcel_Style_Border::BORDER_THIN ,
        'color' => array(
                'rgb' => '000000'
            )
        ),
        'bottom' => array(
            'style' => PHPExcel_Style_Border::BORDER_THIN ,
        'color' => array(
                'rgb' => '000000'
            )
        )
    )
));

$estiloResumen=  array(
    'font' => array(
        'name'      => 'Arial',
        'bold'      => true,
        'size'       =>8,
        'color'     => array(
            'rgb' => '000000'
        )
    ));

            //CONSTRUYENDO ENCABEZADOS
            
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna,$fila,"MATRICULA");
            $columna ++;
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna,$fila,"ALUMNO");
            foreach ($arr_subject_hader as $key => $sub_array_header) {
                // COLOCAR LAS PRIMERAS 3 LETRAS DE LA MATERIA, SI TIENE MaS DE 2 PALABRAS, COLOCAR LAS 3 LETRAS DE LA SIGUIENTE
                $arr_name = explode(" ", $sub_array_header["name"]);
                $size_arr_name = count($arr_name);
                $name_3l = ($arr_name[0] == "PROM") ? "PROM" : mb_substr($arr_name[0], 0, 3, 'UTF-8');
                $final_name = $name_3l;
                //TOMAR LAS 3 LETRAS DE LA SIGUIENTE PALABRA
                if ($size_arr_name > 1) {
                    $aux2_name = "";
                    $name2 = strtoupper($arr_name[1]);
                    //haz un ciclo de 4 para saltar preposiciones
                    for ($i = 1; $i < 5; $i++) {
                        //si es preposicion no hagas nada
                        if ($name2 == "DE" || $name2 == "LA" || $name2 == "EL" || $name2 == "AL") {
                            $var = 0;
                        }//si no es prepocision, toma las 3 letras 
                        else {
                            $name2_3l = mb_substr($name2, 0, 3, 'UTF-8');
                            $final_name.= " ".$name2_3l;
                            break;
                        }
                        if ($size_arr_name > $i + 1)
                            $name2 = strtoupper($arr_name[$i]);
                    }
                }//fin count($arr_name)>1 

                $columna++;
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna,$fila,strtoupper($final_name));

            }//fin foreach header
             $objPHPExcel->getActiveSheet()->getStyle("A1:".$arr_letras_col[$columna].'1')->applyFromArray($estiloTituloColumnas);

            //++++++++++++++++++++++++++++++++++++
            //IMPRIMIR CUADRO DE CALIFICACIONES
            //++++++++++++++++++++++++++++++++++++

            $cont_aux = 1;
             $fila ++;
            foreach ($arr_general as $key => $alumnos) {
                // $html.="<td>$cont_aux</td>"; 
                $columna=0;
                foreach ($alumnos as $key_alumnos => $calif) {
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna,$fila,$calif);
                    $columna++;
                }
                $cont_aux++;
                
                $fila ++;
                //$html.="</tr>";
            }//fin foreach2 alumnos
             $objPHPExcel->getActiveSheet()->setSharedStyle($estiloInformacion,"A2:".$arr_letras_col[$columna-1].$fila);

        }//fin array general
    }// fin empty array
    //
        //CICLO PARA PONER COLUMNAS AUTOMATICAS
        for($i = 'A'; $i <= $arr_letras_col[$columna]; $i++){
                $objPHPExcel->getActiveSheet()->getColumnDimension($i)->setAutoSize(TRUE);
         }
    
     //IMPRIMIMOS LAS CALIFICACIONES NUMERICAS
        udis_numericas($arr_subject_hader,$arr_general,$sub_array_header,$estiloTituloColumnas,$estiloInformacion,$objPHPExcel);
    
    //return $html;
     //Guardamos el archivo en formato Excel 2007
        //Si queremos trabajar con Excel 2003, basta cambiar el 'Excel2007' por 'Excel5' y el nombre del archivo de salida cambiar su formato por '.xls'
        ob_clean();//Esta función desecha el contenido del búfer de salida. Con esto evitamos la basura dentro del archivo
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
         exit;
      
}
