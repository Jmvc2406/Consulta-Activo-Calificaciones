<?php

function print_subject($batch, $curriculum, $division, $periodo, $standard) {
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
        $arr_sumas=array();// array para guardar las "materias" que se deben sumar 
        $cont_pos=0;
        while ($subject = pg_fetch_array($ex_subject_header, null, PGSQL_ASSOC)) {
            //guardamos el encabezado en un array
            $arr_subject = array();
            $arr_subject["name"] = $subject["name"];
            $arr_subject["code"] = $subject["code"];
            $arr_subject["id"] = $subject["id"];
            $arr_subject_hader[$cont_pos] = $arr_subject;
            //ESTOS NO SE VAN A PROMEDIAR, SE VAN A SUMAR
            $faltas = strpos($subject["name"], "FALTAS");
            $retardos = strpos($subject["name"], "RETARDOS");
            $ina = strpos($subject["name"], "INASISTENCIAS");
            $tareas = strpos($subject["name"], "TAREAS");
            $homework = strpos($subject["name"], "HOMEWORK");
            $absences = strpos($subject["name"], "ABSENCES");
            $marks = strpos($subject["name"], "MARKS");
            if($faltas !== false || $retardos !== false || $tareas !== false || $absences !== false || $homework !== false || $marks !== false || $ina !== false){
               $arr_sumas[]=$cont_pos;
            }            
            
            if ($include_absence==1 || $include_absence==true ) {
                $cont_pos++;
                $arr_subject = array();
                $arr_subject["name"] = "F";
                $arr_subject["code"] = $subject["code"];
                $arr_subject["id"] = $subject["id"];
                $arr_subject_hader[$cont_pos] = $arr_subject;
               
            }
           $cont_pos++;
        } //fin while materias
       
        //AGREGAR COLUMNA DE PROMEDIO
        $arr_subject = array();
        $arr_subject["name"] = "PROM W";
        $arr_subject["code"] = "PROM W";
        $arr_subject["id"] = 0;
        $arr_subject_hader[] = $arr_subject;

        //AGREGAR COLUMNA DE PROMEDIO
        $arr_subject_sep = array();
        $arr_subject_sep["name"] = "PROM SEP";
        $arr_subject_sep["code"] = "PROM SEP";
        $arr_subject_sep["id"] = 0;
        $arr_subject_hader[] = $arr_subject_sep;

        
        
        
        //OBTENER ID DE MATERIAS QUE DEBEN PROMEDIARSE PARA WILIAMS Y PARA SEP Y LAS GUARDAMOS EN UN ARRAY
        $arr_average_w = array();
        $arr_average_sep = array();
        $query_average_w = "SELECT op_su.id FROM
                                pl_subject pl_su
                            JOIN op_subject op_su ON op_su.id=pl_su.op_subject_id
                            where pl_curriculum_id=$curriculum and op_su.op_standard_id=$standard and vertical_average=true;
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
                //AGREGAR AL FINAL EL PROMEDIO DE LAS CALIFICACIONES williams
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
    $suma_faltas_otros=0;
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
    $cont_pos_prom_mat=0;
    //Construir array con promedios
    foreach ($arr_prom_mat_aux as $materia_aux) {
      
        foreach ($materia_aux as $calif) {
            $suma_materia+=$calif;
            //clasificar los promedios
            if ($calif < 6) {
                $suma_reprobados++;
                if(in_array($cont_pos_prom_mat, $arr_sumas)){
                $suma_reprobados='-';
                }
            }
            if(in_array($cont_pos_prom_mat, $arr_sumas)){
                $suma_faltas_otros+=$calif;
            }
        }
        $prom = $suma_materia / count($materia_aux);
        if(in_array($cont_pos_prom_mat, $arr_sumas)){
            $arr_prom_mat[] = $suma_faltas_otros;
        }else{
            $arr_prom_mat[] = truncateFloat($prom, 1);
        }
        $arr_reprobados[] = $suma_reprobados;
        $suma_materia = 0;
        $suma_reprobados = 0;
        $suma_faltas_otros=0;
        $cont_pos_prom_mat++;
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

        //CONSTRUYENDO ENCABEZADOS
        $html.="<div class='table_grey' >
              <table>
                <tr>";
        $html.=" <td>Matricula</td>";
        $html.=" <td>Alumno</td>";
        
        foreach ($arr_subject_hader as $key => $sub_array_header) {
            // COLOCAR LAS PRIMERAS 3 LETRAS DE LA MATERIA, SI TIENE M�S DE 2 PALABRAS, COLOCAR LAS 3 LETRAS DE LA SIGUIENTE
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
                        $final_name.="<br> " . $name2_3l;
                        break;
                    }
                    if ($size_arr_name > $i + 1)
                        $name2 = strtoupper($arr_name[$i]);
                }
            }//fin count($arr_name)>1 

            $html.="<td title='" . strtoupper($sub_array_header["name"]) . "'>" . strtoupper($final_name) . "</td>";
        }//fin foreach header

        $html.="</tr>";

        //++++++++++++++++++++++++++++++++++++
        //IMPRIMIR CUADRO DE CALIFICACIONES
        //++++++++++++++++++++++++++++++++++++

        $cont_aux = 1;
        foreach ($arr_general as $key => $alumnos) {
            $html.="<tr>";
            // $html.="<td>$cont_aux</td>"; 
             foreach ($alumnos as $key_alumnos => $calificacion) {
               if($calificacion!=0 && $calificacion<10 && is_numeric($calificacion)){
                $calif=truncateFloat($calificacion, 1);
                }
                else{
                 $calif=$calificacion;
                }

                 $html.="<td>" . $calif . "</td>";
            }

          
            //$cont_aux++;
            $html.="</tr>";
        }//fin foreach2 alumnos
        //Imprimir promedio por materia
        $html.="<tr>";
        $html.="<td></td>";
        $html.="<td><b>PROMEDIOS</b></td>";
        $cont_abs = 0;
        foreach ($arr_prom_mat as $prom_mat) {
            //si tiene columna de faltas imprimir uno s� y uno no 
            if ($include_absence) {
                if ($cont_abs % 2 == 0) {
                    $html.="<td><b>" . $prom_mat . "<b></td>";
                } else {
                    $html.="<td>-</td>";
                }
            } else {
                $html.="<td>" . $prom_mat . "</td>";
            }
            $cont_abs++;
        }
        $html.="</tr>";

        //Imprimir total de faltas
        if ($include_absence) {
            $html.="<tr>";
            $html.="<td>.</td>";
            $html.="<td><b>TOTAL DE FALTAS</b></td>";
            $cont_abs = 0;
            foreach ($arr_abs as $falta) {
                //si tiene columna de faltas imprimir uno s� y uno no 
                if ($include_absence) {
                    if ($cont_abs % 2 == 0) {
                        $html.="<td>.</td>";
                    } else {
                        $html.="<td><b>$falta</b></td>";
                    }
                }
                $cont_abs++;
            }
            $html.="</tr>";
        }


        //Imprimir total de reprobados
        $html.="<tr>";
        $html.="<td>.</td>";
        $html.="<td><b>REPROBADOS</b></td>";
        $cont_abs = 0;
        foreach ($arr_reprobados as $reprobado) {
            //si tiene columna de faltas imprimir uno s� y uno no 
            if ($include_absence) {
                if ($cont_abs % 2 == 0) {
                    $html.="<td>" . $reprobado . "</td>";
                } else {
                    $html.="<td>-</td>";
                }
            } else {
                $html.="<td>" . $reprobado . "</td>";
            }
            $cont_abs++;
        }
        $html.="</tr>";

        //Imprimir porcentaje de reprobados
        $html.="<tr>";
        $html.="<td>.</td>";
        $html.="<td><b>% DE REPROBADOS</b></td>";
        $cont_abs = 0;
        foreach ($arr_reprobados as $reprobado) {
            //si tiene columna de faltas imprimir uno s� y uno no 
            if ($include_absence) {
                if ($cont_abs % 2 == 0) {
                    $html.="<td><b>" . round(($reprobado / $total_alumnos) * 100) . "%</b></td>";
                } else {
                    $html.="<td>-</td>";
                }
            } else {
                $html.="<td><b>" . round(($reprobado / $total_alumnos) * 100) . "%<b></td>";
            }
            $cont_abs++;
        }
        $html.="</tr>";




        //Imprimir Alumnos con 6
        $html.="<tr>";
        $html.="<td>.</td>";
        $html.="<td><b>ALUMNOS CON 6</b></td>";
        $cont_abs = 0;
        foreach ($arr_calif_cat[1] as $calif) {
            //si tiene columna de faltas imprimir uno s� y uno no 
            if ($include_absence) {
                if ($cont_abs % 2 == 0) {
                    $html.="<td><b>$calif</b></td>";
                } else {
                    $html.="<td>-</td>";
                }
            } else {
                $html.="<td><b>$calif<b></td>";
            }
            $cont_abs++;
        }


        $html.="</tr>";
        //Imprimir Alumnos con 7
        $html.="<tr>";
        $html.="<td>.</td>";
        $html.="<td><b>ALUMNOS CON 7</b></td>";
        $cont_abs = 0;
        foreach ($arr_calif_cat[2] as $calif) {
            //si tiene columna de faltas imprimir uno s� y uno no 
            if ($include_absence) {
                if ($cont_abs % 2 == 0) {
                    $html.="<td><b>$calif</b></td>";
                } else {
                    $html.="<td>-</td>";
                }
            } else {
                $html.="<td><b>$calif<b></td>";
            }
            $cont_abs++;
        }
        $html.="</tr>";


        //Imprimir Alumnos con 8
        $html.="<tr>";
        $html.="<td>.</td>";
        $html.="<td><b>ALUMNOS CON 8</b></td>";
        $cont_abs = 0;
        foreach ($arr_calif_cat[3] as $calif) {
            //si tiene columna de faltas imprimir uno s� y uno no 
            if ($include_absence) {
                if ($cont_abs % 2 == 0) {
                    $html.="<td><b>$calif</b></td>";
                } else {
                    $html.="<td>-</td>";
                }
            } else {
                $html.="<td><b>$calif<b></td>";
            }
            $cont_abs++;
        }
        $html.="</tr>";




        //Imprimir Alumnos con 9
        $html.="<tr>";
        $html.="<td>.</td>";
        $html.="<td><b>ALUMNOS CON 9</b></td>";
        $cont_abs = 0;
        foreach ($arr_calif_cat[4] as $calif) {
            //si tiene columna de faltas imprimir uno s� y uno no 
            if ($include_absence) {
                if ($cont_abs % 2 == 0) {
                    $html.="<td><b>$calif</b></td>";
                } else {
                    $html.="<td>-</td>";
                }
            } else {
                $html.="<td><b>$calif<b></td>";
            }
            $cont_abs++;
        }
        $html.="</tr>";


        //Imprimir Alumnos con 10
        $html.="<tr>";
        $html.="<td>.</td>";
        $html.="<td><b>ALUMNOS CON 10</b></td>";
        $cont_abs = 0;
        foreach ($arr_calif_cat[5] as $calif) {
            //si tiene columna de faltas imprimir uno s� y uno no 
            if ($include_absence) {
                if ($cont_abs % 2 == 0) {
                    $html.="<td><b>$calif</b></td>";
                } else {
                    $html.="<td>-</td>";
                }
            } else {
                $html.="<td><b>$calif<b></td>";
            }
            $cont_abs++;
        }
        $html.="</tr>";

        $html.="<tr><td colspan=2><b>TOTAL DE ALUMNOS: $total_alumnos</b></td></tr>";

        $html.="</table></div>";


        return $html;
    } else {
        return "No hay datos para mostrar ";
    }
}
