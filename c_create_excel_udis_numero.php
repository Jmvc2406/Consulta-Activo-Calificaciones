<?php

function udis_numericas($arr_subject_hader,$arr_general,$sub_array_header,$estiloTituloColumnas,$estiloInformacion,$objPHPExcel) {
    $columna = 0;
    $fila = 1;
    $arr_letras_col=['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ'];
    //CREO LA NUEVA PESTAÑA 
    // Create a new worksheet called "My Data"
    $myWorkSheet = new PHPExcel_Worksheet($objPHPExcel, 'NUMERICA');
    // Attach the "My Data" worksheet as the first worksheet in the PHPExcel object
    $objPHPExcel->addSheet($myWorkSheet, 1);
    $objPHPExcel->setActiveSheetIndex(1);
    //OBTEBNER LAS LITERALES DE LA TABLA 
    
    $query_literal = "select name,value_y as calif from pl_literales";
    $ex_literal = pg_query($query_literal) or die('Tenemos un error con la base de datos, intenta mas tarde: ' . pg_last_error());
    $arr_literal=[];
    while ($literal = pg_fetch_array($ex_literal, null, PGSQL_ASSOC)) {
        $key=$literal["name"];
        $calif=$literal["calif"];
        $arr_literal[$key] = $calif;
    }

    //CONSTRUYENDO ENCABEZADOS
    if (count($arr_general) > 0) {
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, "MATRICULA");
        $columna ++;
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, "ALUMNO");
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
                        $final_name.= " " . $name2_3l;
                        break;
                    }
                    if ($size_arr_name > $i + 1)
                        $name2 = strtoupper($arr_name[$i]);
                }
            }//fin count($arr_name)>1 

            $columna++;
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, strtoupper($final_name));
        }//fin foreach header
        $objPHPExcel->getActiveSheet()->getStyle("A1:" . $arr_letras_col[$columna] . '1')->applyFromArray($estiloTituloColumnas);

//++++++++++++++++++++++++++++++++++++
//IMPRIMIR CUADRO DE CALIFICACIONES
//++++++++++++++++++++++++++++++++++++

        $cont_aux = 1;
        $fila ++;
        foreach ($arr_general as $key => $alumnos) {
            // $html.="<td>$cont_aux</td>"; 
            $columna = 0;
            foreach ($alumnos as $key_alumnos => $calif) {
                
                if (array_key_exists($calif, $arr_literal)) {
                    $calif=$arr_literal[$calif];
                }
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna, $fila, $calif);
                
                $columna++;
            }
            $cont_aux++;

            $fila ++;
            //$html.="</tr>";
        }//fin foreach2 alumnos
        $objPHPExcel->getActiveSheet()->setSharedStyle($estiloInformacion, "A2:" . $arr_letras_col[$columna - 1] . $fila);
    }//fin array general
//
        //CICLO PARA PONER COLUMNAS AUTOMATICAS
    for ($i = 'A'; $i <= $arr_letras_col[$columna]; $i++) {
        $objPHPExcel->getActiveSheet()->getColumnDimension($i)->setAutoSize(TRUE);
    }
}
