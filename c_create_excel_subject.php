<?php

require_once('../config/conection.php');
require_once('../config/utils.php');
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function print_subject_excel($batch, $curriculum, $division, $periodo, $standard) {
    //obtenemos todas las materias para crear encabezados
    if (!empty($standard)) {
        $arr_subject_hader = array(); //array para encabezados
        $arr_general = array(); //array para guardar todos los datos
        $query_subject_header = "select ss.id,ss.name,ss.code  from pl_curriculum c 
                                    join pl_subject s on c.id=s.pl_curriculum_id
                                    join op_subject ss on ss.id=s.op_subject_id
                                  where c.id=$curriculum and ss.op_standard_id=$standard
                                  order by  ss.id;
        ";

        $ex_subject_header = pg_query($query_subject_header) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());

        //SI EL BATCH QUE ESCOGIERON ES DE PREPA, BACHILLERATO O SECUNDARIA AGREGAR COLUMNA DE FALTAS POR MATERIA 
        $include_absence = false;
        $query_absence = "select name from op_batch 
                            where
                                id=$batch and
                                (name like '%CCH%' OR
                                name like '%BACHILLERATO%' OR
                                name like '%SECUNDARIA%' ) ";

        $ex_absence = pg_query($query_absence) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
        $arr_absence = pg_fetch_array($ex_absence, null, PGSQL_ASSOC);

        if ($arr_absence) {
            $include_absence = true;
        } else {
            $include_absence = false;
        }

     
        //ITERAMOS ENCABEZADO
        while ($subject = pg_fetch_array($ex_subject_header, null, PGSQL_ASSOC)) {
            //guardamos el encabezado en un array
            $arr_subject = array();
            $arr_subject["name"] = $subject["name"];
            $arr_subject["code"] = $subject["code"];
            $arr_subject["id"] = $subject["id"];
            $arr_subject_hader[] = $arr_subject;
            if ($include_absence) {
                $arr_subject = array();
                $arr_subject["name"] = "F";
                $arr_subject["code"] = $subject["code"];
                $arr_subject["id"] = $subject["id"];
                $arr_subject_hader[] = $arr_subject;
            }
        } //fin while materias
        //AGREGAR COLUMNA DE PROMEDIO
        $arr_subject = array();
        $arr_subject["name"] = "PROM W";
        $arr_subject["code"] = "PROM W";
        $arr_subject["id"] = 0;
        $arr_subject_hader[] = $arr_subject;

        //AGREGAR COLUMNA DE PROMEDIO SEP
        $arr_subject_sep = array();
        $arr_subject_sep["name"] = "PROM SEP";
        $arr_subject_sep["code"] = "PROM SEP";
        $arr_subject_sep["id"] = 0;
        $arr_subject_hader[] = $arr_subject_sep;

        //OBTENER ID DE MATERIAS QUE NO DEBEN PROMEDIARSE Y LAS GUARDAMOS EN UN ARRAY
        $arr_average_w = array();
        $arr_average_sep = array();
        $query_average_w = "SELECT op_su.id FROM
                                pl_subject pl_su
                            JOIN op_subject op_su ON op_su.id=pl_su.op_subject_id
                            where pl_curriculum_id=$curriculum and vertical_average=true;
                                ";
        $ex_average_w = pg_query($query_average_w) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
        while ($subject_noaverage = pg_fetch_array($ex_average_w, null, PGSQL_ASSOC)) {
            $arr_average_w[] = $subject_noaverage["id"];
        }
        //SEP    
        $query_average_sep = "SELECT op_su.id FROM
                                pl_subject pl_su
                            JOIN op_subject op_su ON op_su.id=pl_su.op_subject_id
                            where pl_curriculum_id=$curriculum and oficial=true;
                                ";
        $ex_average_sep = pg_query($query_average_sep) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
        while ($subject_average_sep = pg_fetch_array($ex_average_sep, null, PGSQL_ASSOC)) {
            $arr_average_sep[] = $subject_average_sep["id"];
        }

        //Variables para guardar alumnos por calificacion   
        $prom_5 = 0;
        $prom_6 = 0;
        $prom_7 = 0;
        $prom_8 = 0;
        $prom_9 = 0;
        $prom_10 = 0;
        $total_faltas = 0;
        $total_alumnos = 0;


        // tomar una materia y sacar los alumnos. Ordernar por roll_number (numero de lista)
        $subject_aux = $arr_subject_hader[0]["id"];
        if ($subject_aux != "") {
            $arr_general = array();
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
                $arr_prom_sep = array();
                $total_alumnos++;

                $arr_student[] = $student["id_number"];
                $arr_student[] = $student["alumno"];

                $query_student_score = "select ss.score, a.op_subject_id as id_materia
                                        from pl_section_student ss
                                            join pl_section s on s.id=ss.pl_section_id
                                            join pl_subject a on a.id=s.pl_subject_id
                                            join op_student stu on stu.id=ss.op_student_id
                                            join res_partner res on res.id=stu.partner_id
                                        where ss.op_division_id=$division
                                            and ss.op_batch_id=$batch
                                            and s.seq=$periodo
                                            and ss.op_student_id=" . $student["id"] . "
                                            and s.pl_curriculum_id=$curriculum 
                                            and ss.active=true  
                                        order by a.op_subject_id;
                ";
                $ex_student_score = pg_query($query_student_score) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
                //VERIFICAR SI SE TIENE QUE AGREGAR COLUMNA DE FALTAS Y AGREGARLA
                if ($include_absence) {
                    $query_student_absence = "select ss.aux1_int as absence
                                                from pl_section_student ss
                                                    join pl_section s on s.id=ss.pl_section_id
                                                    join pl_subject a on a.id=s.pl_subject_id
                                                    join op_student stu on stu.id=ss.op_student_id
                                                    join res_partner res on res.id=stu.partner_id
                                                where ss.op_division_id=$division
                                                    and ss.op_batch_id=$batch
                                                    and s.seq=$periodo
                                                    and ss.op_student_id=" . $student["id"] . "
                                                    and s.pl_curriculum_id=$curriculum 
                                                    and ss.active=true 
                                                order by a.op_subject_id;
                        ";
                    $ex_student_absence = pg_query($query_student_absence) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
                }//tiene faltas?

                while ($student_score = pg_fetch_array($ex_student_score, null, PGSQL_ASSOC)) {
                    $arr_student[] = $student_score["score"];
                    //varificar si se tiene que promediar  
                    if ($student_score["score"] != "") {
                        if (in_array($student_score["id_materia"], $arr_average_w)) {
                            //no promediar si la calificacion es nula 
                            $arr_prom[] = $student_score["score"];
                        }
                        if (in_array($student_score["id_materia"], $arr_average_sep)) {
                            //no promediar si la calificacion es nula 
                            $arr_prom_sep[] = $student_score["score"];
                        }
                    }

                    // variable para las faltas
                    if ($include_absence) {
                        $student_absence = pg_fetch_array($ex_student_absence, null, PGSQL_ASSOC);
                        $arr_student[] = $student_absence["absence"];
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
                    $final_prom = truncateFloat($prom, 1);
                    $arr_student[] = $final_prom;
                }//fin if promediar
                //AGREGAR AL FINAL EL PROMEDIO DE LAS CALIFICACIONES   sep 
                if (count($arr_prom_sep) > 0) {
                    $size = count($arr_prom_sep);
                    $sum_prom = 0;
                    foreach ($arr_prom_sep as $element) {
                        $sum_prom+=$element;
                    }
                    $prom = $sum_prom / $size;
                    $final_prom = truncateFloat($prom, 1);
                    $arr_student[] = $final_prom;
                }//fin if promediar
                //
                //AGREGAR EL ALUMNO AL ARRAY GENERAL                  
                $arr_general[] = $arr_student;
            }//fin while de estudiantes
        }//if materia vacia       
    }//fin empty standard
    //+++++++++++++++++++++++++++++++
    // PROMEDIO POR MATERIAS
    //+++++++++++++++++++++++++++++++  
    //Ordenar materias para sumar y promediar en la tabla
    $cont_y = 0;
    $cont_x = 0;
    $suma_materia = 0;
    $suma_reprobados = 0;
    $arr_prom_mat_aux = array();
    $arr_prom_mat = array();
    $arr_general_aux = $arr_general;
    foreach ($arr_general as $key => $alumnos) {
        foreach ($alumnos as $key_alumnos => $calif) {
            $arr_prom_mat_aux[$cont_y][$cont_x] = $calif;
            $cont_y++;
        }
        $cont_y = 0;
        $cont_x++;
    }

    //Borrar las primeras dos posiciones que son el nombre y matricula
    array_shift($arr_prom_mat_aux);
    array_shift($arr_prom_mat_aux);
    $arr_reprobados = array();
    //Construir array con promedios
    foreach ($arr_prom_mat_aux as $materia_aux) {
        foreach ($materia_aux as $calif) {
            $suma_materia+=$calif;
            //clasificar los promedios
            if ($calif < 6) {
                $suma_reprobados++;
            }
        }
        $prom = $suma_materia / count($materia_aux);
        $arr_prom_mat[] = truncateFloat($prom, 1);
        $arr_reprobados[] = $suma_reprobados;
        $suma_materia = 0;
        $suma_reprobados = 0;
    }

    //Contar calificaciones por materia
    $prom_5 = 0;
    $prom_6 = 0;
    $prom_7 = 0;
    $prom_8 = 0;
    $prom_9 = 0;
    $prom_10 = 0;
    $size = count($arr_prom_mat_aux);
    $arr_calif_cat = array();
    $sum_absence = 0;
    $arr_abs = array();

    for ($i = 0; $i < $size; $i++) {
        for ($j = 0; $j < $total_alumnos; $j++) {
            $calif = $arr_prom_mat_aux[$i][$j];
            if ($include_absence) {
                //sumar faltas
                $sum_absence+=$calif;
            }
            if ($calif >= 0 && $calif <= 5.9)
                $prom_5++;
            else if ($calif >= 6.0 && $calif <= 6.9)
                $prom_6++;
            else if ($calif >= 7.0 && $calif <= 7.9)
                $prom_7++;
            else if ($calif >= 8.0 && $calif <= 8.9)
                $prom_8++;
            else if ($calif >= 9.0 && $calif <= 9.9)
                $prom_9++;
            else if ($calif == 10)
                $prom_10++;
        }
        if ($include_absence) {
            $arr_abs[] = $sum_absence;
            $sum_absence = 0;
        }
        $arr_calif_cat[0][$i] = $prom_5;
        $arr_calif_cat[1][$i] = $prom_6;
        $arr_calif_cat[2][$i] = $prom_7;
        $arr_calif_cat[3][$i] = $prom_8;
        $arr_calif_cat[4][$i] = $prom_9;
        $arr_calif_cat[5][$i] = $prom_10;
        $prom_5 = 0;
        $prom_6 = 0;
        $prom_7 = 0;
        $prom_8 = 0;
        $prom_9 = 0;
        $prom_10 = 0;
    }


    //+++++++++++++++++++++++++++++++
    // CONSTRUIR TABLA DE CALIFICACIONES
    //+++++++++++++++++++++++++++++++    
    if (count($arr_general) > 0) {//si el array general est� lleno contruir la tabla
        $html = "";
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
        $arr_letras_col = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ'];

        //ESTILOS
        $estiloTituloReporte = array(
            'font' => array(
                'name' => 'Verdana',
                'bold' => true,
                'italic' => false,
                'strike' => false,
                'size' => 16,
                'color' => array(
                    'rgb' => 'FFFFFF'
                )
            ),
            'fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
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
                'name' => 'Arial',
                'bold' => true,
                'size' => 8,
                'color' => array(
                    'rgb' => '000000'
                )
            ),
            'fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'startcolor' => array(
                    'rgb' => 'C4C2C2'
                ),
            ),
            'borders' => array(
                'top' => array(
                    'style' => PHPExcel_Style_Border::BORDER_MEDIUM,
                    'color' => array(
                        'rgb' => '143860'
                    )
                ),
                'bottom' => array(
                    'style' => PHPExcel_Style_Border::BORDER_MEDIUM,
                    'color' => array(
                        'rgb' => '143860'
                    )
                )
            ),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                'wrap' => TRUE
            )
        );

        $estiloInformacion = new PHPExcel_Style();
        $estiloInformacion->applyFromArray(array(
            'font' => array(
                'name' => 'Arial',
                'size' => 8,
                'color' => array(
                    'rgb' => '000000'
                )
            ),
            'borders' => array(
                'left' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array(
                        'rgb' => '000000'
                    )
                ),
                'bottom' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array(
                        'rgb' => '000000'
                    )
                )
            )
        ));

        $estiloResumen = array(
            'font' => array(
                'name' => 'Arial',
                'bold' => true,
                'size' => 8,
                'color' => array(
                    'rgb' => '000000'
                )
        ));
        /* $objConditional1->getStyle()->getFill()
          ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
          ->getStartColor()->setARGB('C4C2C2'); */




        //CONSTRUYENDO ENCABEZADOS
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, "MATRICULA");
        $columna++;
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, "ALUMNO");

        foreach ($arr_subject_hader as $key => $sub_array_header) {
            // COLOCAR LAS PRIMERAS 3 LETRAS DE LA MATERIA, SI TIENE MaS DE 2 PALABRAS, COLOCAR LAS 3 LETRAS DE LA SIGUIENTE
            $arr_name = explode(" ", $sub_array_header["name"]);
            $size_arr_name = count($arr_name);
            $name_3l = ($arr_name[0] == "PROM W" || $arr_name[0] == "PROM SEP") ? $arr_name[0] : mb_substr($arr_name[0], 0, 3, 'UTF-8');
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
                        $final_name.=" " . $name2_3l;
                        break;
                    }
                    if ($size_arr_name > $i + 1)
                        $name2 = strtoupper($arr_name[$i]);
                }
            }//fin count($arr_name)>1 
            $columna ++;
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, strtoupper($final_name));
        }//fin foreach header
        //ESTILO PARA ENCABEZADOS
        $objPHPExcel->getActiveSheet()->getStyle('A1:' . $arr_letras_col[$columna] . '1')->applyFromArray($estiloTituloColumnas);

        //++++++++++++++++++++++++++++++++++++
        //IMPRIMIR CUADRO DE CALIFICACIONES
        //++++++++++++++++++++++++++++++++++++
        $cont_aux = 1;
        $columna = 0;
        foreach ($arr_general as $key => $alumnos) {
            $fila++;
            $columna = 0;
            foreach ($alumnos as $key_alumnos => $calif) {
		 $calificacion=$calif;
                if($columna != 0 && $columna != 1){
                        $calificacion=truncateFloat($calif, 1);
                    }
                
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, $calificacion);
                $columna++;
            }
            $cont_aux++;
        }//fin foreach2 alumnos
        $objPHPExcel->getActiveSheet()->setSharedStyle($estiloInformacion, "A2:" . $arr_letras_col[$columna - 1] . $fila);

        //Imprimir promedio por materia

        $fila++;
        $columna = 1;
        $objPHPExcel->getActiveSheet()->getStyle('A' . $fila . ':' . $arr_letras_col[$columna] . $fila)->applyFromArray($estiloTituloColumnas);
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, "PROMEDIOS");
        $cont_abs = 0;
        foreach ($arr_prom_mat as $promedio_mat) {
            $prom_mat=  truncateFloat($promedio_mat, 1);
            //si tiene columna de faltas imprimir uno si y uno no 
            if ($include_absence) {
                if ($cont_abs % 2 == 0) {
                    $columna++;

                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, $prom_mat);
                } else {
                    $columna++;
                }
            } else {
                $columna++;
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, $prom_mat);
            }
            $objPHPExcel->getActiveSheet()->getStyle($arr_letras_col[$columna] . $fila)->applyFromArray($estiloResumen);
            $cont_abs++;
        }
        $objPHPExcel->getActiveSheet()->getStyle('A' . $fila . ':' . $arr_letras_col[$columna] . $fila)->applyFromArray($estiloTituloColumnas);

        $fila++;

        //Imprimir total de faltas
        if ($include_absence) {
            $columna = 1;
            $objPHPExcel->getActiveSheet()->getStyle('A' . $fila . ':' . $arr_letras_col[$columna] . $fila)->applyFromArray($estiloTituloColumnas);
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, "TOTAL DE FALTAS");
            $cont_abs = 0;
            foreach ($arr_abs as $falta) {
                //si tiene columna de faltas imprimir uno si y uno no 
                if ($include_absence) {
                    if ($cont_abs % 2 == 0) {
                        $columna++;
                    } else {
                        $columna++;
                        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, $falta);
                        $objPHPExcel->getActiveSheet()->getStyle($arr_letras_col[$columna] . $fila)->applyFromArray($estiloResumen);
                    }
                }
                $cont_abs++;
            }
            $fila++;
        }


        $columna = 1;
        $objPHPExcel->getActiveSheet()->getStyle('A' . $fila . ':' . $arr_letras_col[$columna] . $fila)->applyFromArray($estiloTituloColumnas);
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, "REPROBADOS");
        $cont_abs = 0;
        foreach ($arr_reprobados as $reprobado) {
            //si tiene columna de faltas imprimir uno s� y uno no 
            if ($include_absence) {
                if ($cont_abs % 2 == 0) {
                    $columna++;
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, $reprobado);
                    $objPHPExcel->getActiveSheet()->getStyle($arr_letras_col[$columna] . $fila)->applyFromArray($estiloResumen);
                } else {
                    $columna++;
                }
            } else {
                $columna++;
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, $reprobado);
            }
            $objPHPExcel->getActiveSheet()->getStyle($arr_letras_col[$columna] . $fila)->applyFromArray($estiloResumen);
            $cont_abs++;
        }
        // $html.="</tr>";
        //Imprimir porcentaje de reprobados

        $fila++;
        $columna = 1;
        $objPHPExcel->getActiveSheet()->getStyle('A' . $fila . ':' . $arr_letras_col[$columna] . $fila)->applyFromArray($estiloTituloColumnas);
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, "% DE REPROBADOS");
        $cont_abs = 0;
        foreach ($arr_reprobados as $reprobado) {
            //si tiene columna de faltas imprimir uno si y uno no 
            if ($include_absence) {
                if ($cont_abs % 2 == 0) {
                    $columna ++;
                    $value_op = round(($reprobado / $total_alumnos) * 100);
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, $value_op);
                } else {
                    $columna ++;
                }
            } else {
                $columna ++;
                $value_op = round(($reprobado / $total_alumnos) * 100);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, $value_op);
            }
            $cont_abs++;
            $objPHPExcel->getActiveSheet()->getStyle($arr_letras_col[$columna] . $fila)->applyFromArray($estiloResumen);
        }

        //Imprimir Alumnos con 6

        $fila++;
        $columna = 1;
        $objPHPExcel->getActiveSheet()->getStyle('A' . $fila . ':' . $arr_letras_col[$columna] . $fila)->applyFromArray($estiloTituloColumnas);
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, "ALUMNOS CON 6");
        $cont_abs = 0;
        foreach ($arr_calif_cat[1] as $calif) {
            //si tiene columna de faltas imprimir uno s� y uno no 
            if ($include_absence) {
                if ($cont_abs % 2 == 0) {
                    $columna++;
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, $calif);
                } else {
                    $columna++;
                }
            } else {
                $columna++;
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, $calif);
            }
            $objPHPExcel->getActiveSheet()->getStyle($arr_letras_col[$columna] . $fila)->applyFromArray($estiloResumen);
            $cont_abs++;
        }


        $fila++;
        $columna = 1;
        $objPHPExcel->getActiveSheet()->getStyle('A' . $fila . ':' . $arr_letras_col[$columna] . $fila)->applyFromArray($estiloTituloColumnas);
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, "ALUMNOS CON 7");
        $cont_abs = 0;
        foreach ($arr_calif_cat[2] as $calif) {
            //si tiene columna de faltas imprimir uno s� y uno no 
            if ($include_absence) {
                if ($cont_abs % 2 == 0) {
                    $columna++;
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, $calif);
                } else {
                    $columna++;
                }
            } else {
                $columna++;
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, $calif);
            }
            $objPHPExcel->getActiveSheet()->getStyle($arr_letras_col[$columna] . $fila)->applyFromArray($estiloResumen);
            $cont_abs++;
        }


        //Imprimir Alumnos con 8

        $fila++;
        $columna = 1;
        $objPHPExcel->getActiveSheet()->getStyle('A' . $fila . ':' . $arr_letras_col[$columna] . $fila)->applyFromArray($estiloTituloColumnas);
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, "ALUMNOS CON 8");
        $cont_abs = 0;
        foreach ($arr_calif_cat[3] as $calif) {
            //si tiene columna de faltas imprimir uno s� y uno no 
            if ($include_absence) {
                if ($cont_abs % 2 == 0) {
                    $columna++;
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, $calif);
                } else {
                    $columna++;
                }
            } else {
                $columna++;
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, $calif);
            }
            $objPHPExcel->getActiveSheet()->getStyle($arr_letras_col[$columna] . $fila)->applyFromArray($estiloResumen);
            $cont_abs++;
        }
        $html.="</tr>";




        //Imprimir Alumnos con 9

        $fila++;
        $columna = 1;
        $objPHPExcel->getActiveSheet()->getStyle('A' . $fila . ':' . $arr_letras_col[$columna] . $fila)->applyFromArray($estiloTituloColumnas);
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, "ALUMNOS CON 9");
        $cont_abs = 0;
        foreach ($arr_calif_cat[4] as $calif) {
            //si tiene columna de faltas imprimir uno s� y uno no 
            if ($include_absence) {
                if ($cont_abs % 2 == 0) {
                    $columna++;
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, $calif);
                } else {
                    $columna++;
                }
            } else {
                $columna++;
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, $calif);
            }
            $objPHPExcel->getActiveSheet()->getStyle($arr_letras_col[$columna] . $fila)->applyFromArray($estiloResumen);
            $cont_abs++;
        }


        //Imprimir Alumnos con 10

        $fila++;
        $columna = 1;
        $objPHPExcel->getActiveSheet()->getStyle('A' . $fila . ':' . $arr_letras_col[$columna] . $fila)->applyFromArray($estiloTituloColumnas);
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, "ALUMNOS CON 10");
        $cont_abs = 0;
        foreach ($arr_calif_cat[5] as $calif) {
            //si tiene columna de faltas imprimir uno si y uno no 
            if ($include_absence) {
                if ($cont_abs % 2 == 0) {
                    $columna++;
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, $calif);
                } else {
                    $columna++;
                }
            } else {
                $columna++;
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, $calif);
            }
            $objPHPExcel->getActiveSheet()->getStyle($arr_letras_col[$columna] . $fila)->applyFromArray($estiloResumen);
            $cont_abs++;
        }
        //CICLO PARA PONER COLUMNAS AUTOMATICAS
        for ($i = 0; $i <= $columna; $i++) {
            $objPHPExcel->getActiveSheet()->getColumnDimension($arr_letras_col[$i])->setAutoSize(TRUE);
        }

        $fila++;
        $columna = 1;
        $objPHPExcel->getActiveSheet()->getStyle('A' . $fila . ':' . $arr_letras_col[$columna] . $fila)->applyFromArray($estiloTituloColumnas);
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, "TOTAL de ALUMNOS $total_alumnos");
                
        //Guardamos el archivo en formato Excel 2007
        //Si queremos trabajar con Excel 2003, basta cambiar el 'Excel2007' por 'Excel5' y el nombre del archivo de salida cambiar su formato por '.xls'
        ob_clean(); //Esta función desecha el contenido del búfer de salida. Con esto evitamos la basura dentro del archivo
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    } else {
        return "No hay datos para mostrar ";
    }
}
