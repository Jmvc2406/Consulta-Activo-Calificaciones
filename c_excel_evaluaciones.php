<?php

//echo("HORA INICIO: ".  date("H:i:s:u") . "<br>");
require_once('../config/conection.php');
require_once('../config/utils.php');
require_once('../config/vars.php');
require_once('../lib/Classes/PHPExcel.php');
require_once('../lib/Classes/PHPExcel/Reader/Excel2007.php');
require_once('../lib/Classes/PHPExcel/IOFactory.php');

//  AQUI VALIDAREMOS USUARIO. SINO RETORNAR A LOGIN
$test = "f";
if ($test != "debug") {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="book.xlsx"');
    header('Cache-Control: max-age=0');
}


$batch = isset($_GET['batch']) ? $_GET['batch'] : "";
$curriculum = isset($_GET['curriculum']) ? $_GET['curriculum'] : "";
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : "";

//revisar que todos los datos tengan valores
if (!empty($batch) && !empty($curriculum) && !empty($periodo)) {
    //CREAR REPORTE POR CICLO
    //
   //CREAMOS OBJETOS PARA EXCEL
    $inputFileName = '../lib/book.xlsx';
    // Creamos un objeto PHPExcel
    $objPHPExcel = new PHPExcel();
    $no_sheet = 0;
    // Leemos un archivo Excel 2007
    $objReader = PHPExcel_IOFactory::createReader('Excel2007');
    $objPHPExcel = $objReader->load($inputFileName);
    // Variables para columnas
    $columna_grupo = 0;
    $fila_grupo = 1;
    $columna_materia = 2;
    $fila_materia = 1;
    $columna_evaluaciones = 0;
    $fila_evaluaciones = 1;
    $columna_alumnos = 2;
    $fila_alumnos = 4;
    $test_fila = 1;
    $columna_evals = 1;
    $fila_evals = 2;
    $arr_students = array();
    $fila_calif = 3;


    //FOR DE LETRAS
    for ($i = "A"; $i <= "Z"; $i++) {
        $arr_letras_col[] = $i;
    }
    $limite = 150;
    $cont = 0;
    for ($i = "A"; $i <= "Z"; $i++) {
        for ($j = "A"; $j <= "Z"; $j++) {
            $arr_letras_col[] = $i . $j;
            $cont++;
            if ($cont == $limite)
                break;
        }
        if ($cont == $limite)
            break;
    }

    #OBTENEMOS EL GRADO --STANDARD
    $query_standard = "SELECT 
                    DISTINCT d.name,
                    a.standard_id as id
                     FROM op_allocat_division a
                             JOIN op_course c ON c.id = a.course_id
                             JOIN op_batch b ON b.course_id = c.id
                             JOIN op_standard d ON a.standard_id=d.id
                     WHERE  b.id= $batch 
                    GROUP BY d.name,a.standard_id;";

    $ex_standard = pg_query($query_standard) or die("Tenemos un error con la base de datos, intenta mas tarde: #1" . pg_last_error());
    while ($standard = pg_fetch_array($ex_standard, null, PGSQL_ASSOC)) {
        $objPHPExcel->setActiveSheetIndex($no_sheet);
        // Le ponemos nombre a la pestaña
        $objPHPExcel->getActiveSheet()->setTitle($standard["name"]);
        //1 OBTENEMOS LOS GRUPOS
        $query_division = "SELECT
                     a.division_id AS id,
                    a.name
                   FROM op_allocat_division a
                     JOIN op_course c ON c.id = a.course_id
                     JOIN op_batch b ON b.course_id = c.id
                   WHERE  a.standard_id= " . $standard["id"] . "
                   order by a.name";

        $ex_division = pg_query($query_division) or die("Tenemos un error con la base de datos, intenta mas tarde: #2 $query_division" . pg_last_error());
        while ($division_obj = pg_fetch_array($ex_division, null, PGSQL_ASSOC)) {
            //PONEMOS EL NOMBRE DEL GRUPO
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna_grupo, $fila_grupo, $division_obj["name"]);
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna_grupo+2, $fila_grupo, "inicio: ". date("H:i:s:u"));
            $id_division = $division_obj["id"];
            
            //contar los alumnos del grupo
            $query_alumnos_grupo="select count(*) as total_alumnos from op_student where division_id=$id_division";
            $ex_alumnos_grupo = pg_query($query_alumnos_grupo) or die("Tenemos un error con la base de datos, intenta mas tarde: #2 $query_division" . pg_last_error());
            $arr_alumnos_grupo= pg_fetch_array($ex_alumnos_grupo, null, PGSQL_ASSOC);
            $alumnos_por_grupo=$arr_alumnos_grupo["total_alumnos"];
         
            //obtenemos las materias del grupo
            $query_subject = "select 
                DISTINCT sub.id as materia_id, 
                sub.name as materia, 
                div.id as grupo_id,
                div.name as grupo, 
                sec.op_batch_id as ciclo_id,
                 sub.code as code
                from 
                    pl_section sec 
                    join pl_subject pls on sec.pl_subject_id=pls.id
                    join op_subject sub on pls.op_subject_id=sub.id
                    join op_division  div on sec.op_division_id = div.id
                where sec.op_division_id =$id_division
                    and (   sub.name not like '%READING%' 
                        AND sub.name not like '%WRITING%' 
                        AND sub.name not like '%LISTENING%'
                        AND sub.name not like '%SPEAKING%'
                        AND sub.name not like '%SKILLS%'
                        AND sub.name not like '%HOMEWORK%'
                        AND sub.name not like '%BEHAVIOR%'
                        AND sub.name not like '%ABSENCES%'
                        AND sub.name not like '%MARKS%'
                        AND sub.name not like '%COMPRENSIÓN%'
                        AND sub.name not like '%HABILIDADES%'
                        AND sub.name not like '%ACTITUDES%'
                        AND sub.name not like '%SOBRESALI%'
                        AND sub.name not like '%TRABAJAR%'
                        )
                order by grupo,code;";
            $ex_subject = pg_query($query_subject) or die("Tenemos un error con la base de datos, intenta mas tarde: #2 $query_division" . pg_last_error());
            while ($subject_obj = pg_fetch_array($ex_subject, null, PGSQL_ASSOC)) {
                $id_subject=$subject_obj["materia_id"];
                $subject_evaluation = false;
                $materia_nombre=$subject_obj["materia"];
                //obtenemos evaluaciones
                $query_evaluations = " SELECT
                                            DISTINCT e.code,
                                            e.id,
                                            e.seq
                                        from
                                            pl_scores s join pl_evaluation_criteria e on s.pl_evaluation_criteria_id=e.id 
                                             join pl_section pl on e.pl_section_id=pl.id
                                            join pl_subject sub on sub.id=s.pl_subject_id
                                        where 
                                            sub.op_subject_id=$id_subject
                                            and s.op_division_id=$id_division
                                             and pl.seq=$periodo
                                            and s.active=true order by e.seq";

                $ex_evaluations = pg_query($query_evaluations) or die("Tenemos un error con la base de datos, intenta mas tarde: #2 $query_division" . pg_last_error());
                while ($evaluations_obj = pg_fetch_array($ex_evaluations, null, PGSQL_ASSOC)) {
                    $subject_evaluation = true;                  
                    $id_evaluacion=$evaluations_obj["id"];
                    $nombre_evaluacion=$evaluations_obj["code"];
                    
                    //pongo el nombre de la materia y de la evaluacion
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna_materia, ($fila_materia+1), $materia_nombre);
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna_materia, ($fila_materia+2), $nombre_evaluacion);
                    $columna_materia++;
                    
                    $query_scores=" select 
                                 s.id as id_sec_est,
                                 id_number as matricula, 
                                 coalesce(st.middle_name,' ')||' '||coalesce(st.last_name,' ')||' '||coalesce(res.name,' ') as alumno,
                                 score as calificacion 
                                from pl_scores s 
                                 join pl_subject sub on s.pl_subject_id =sub.id
                                 join op_student st on st.id = s.op_student_id
                                 join res_partner res on res.id = st.partner_id
                                where 
                                  op_division_id=$id_division and 
                                  op_subject_id=$id_subject and 
                                  pl_evaluation_criteria_id=$id_evaluacion and
                                  s.active=true
                                order by roll_number;";
                  
                    $ex_scores = pg_query($query_scores) or die("Tenemos un error con la base de datos, intenta mas tarde: #2 $query_division" . pg_last_error());
                    $cont_alum=0;
                    while ($scores_obj = pg_fetch_array($ex_scores, null, PGSQL_ASSOC)) {
                    $calificacion=$scores_obj["calificacion"];
                    $alumno=$scores_obj["alumno"];
                    $col_alumno_name=1;
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col_alumno_name, $fila_alumnos, $alumno);
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna_alumnos, $fila_alumnos, $calificacion);
                    $fila_alumnos++;
                    $cont_alum++;
                } 
                $columna_alumnos++;
                $fila_alumnos-=$cont_alum;
            }
            if(!$subject_evaluation){
                //poner la calificacion de la materia 
                //pongo el nombre de la materia 
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna_materia, ($fila_materia+1), $materia_nombre);
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna_materia, ($fila_materia+2), "-");
                    $columna_materia++;
                    
                $query_scores_subject="SELECT
                                            ss.score as calificacion,
                                            A .op_subject_id AS id_materia,
                                            COALESCE (stu.middle_name, ' ') || ' ' || COALESCE (stu.last_name, ' ') || ' ' || COALESCE (res. NAME, ' ') AS alumno
                                        FROM
                                            pl_section_student ss
                                        JOIN pl_section s ON s. ID = ss.pl_section_id
                                        JOIN pl_subject A ON A . ID = s.pl_subject_id
                                        JOIN op_student stu ON stu. ID = ss.op_student_id
                                        JOIN res_partner res ON res. ID = stu.partner_id
                                        WHERE
                                            ss.op_division_id = $id_division
                                            AND ss.op_batch_id = $batch
                                            AND s.seq = $periodo
                                            AND A .op_subject_id = $id_subject
                                            AND s.pl_curriculum_id = $curriculum
                                            AND ss.active = TRUE
                                        ORDER BY
                                            stu.roll_number";
                
                $ex_scores = pg_query($query_scores_subject) or die("Tenemos un error con la base de datos, intenta mas tarde: #2 $query_division" . pg_last_error());
                $cont_alum=0;
                while ($scores_subject_obj = pg_fetch_array($ex_scores, null, PGSQL_ASSOC)) {
                    $alumno=$scores_subject_obj["alumno"];
                    $calificacion=$scores_subject_obj["calificacion"];
                   
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col_alumno_name, $fila_alumnos, $alumno);
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna_alumnos, $fila_alumnos, $calificacion);
                    $fila_alumnos++;
                    $cont_alum++;
                }
                $columna_alumnos++;
                $fila_alumnos-=$cont_alum;
                }               
            }
            //poner los promedios SEP
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna_alumnos, $fila_alumnos-2, "PROMEDIO SEP");
            $query_prom_sep="SELECT DISTINCT
                            ss.op_student_id,
                            stu.roll_number,
                            COALESCE (stu.middle_name, ' ') || ' ' || COALESCE (stu.last_name, ' ') || ' ' || COALESCE (res. NAME, ' ') AS alumno,
                            (
                                SELECT
                                    SUM (ss2.score) / COUNT (*) AS prom_sep
                                FROM
                                    pl_section_student ss2
                                    JOIN pl_section s2 ON s2. ID = ss2.pl_section_id
                                    JOIN pl_subject A2 ON A2 . ID = s2.pl_subject_id
                                    JOIN op_subject sub2 ON sub2. ID = A2 .op_subject_id
                                    JOIN op_student stu2 ON stu2. ID = ss2.op_student_id
                                WHERE
                                    ss2.op_division_id = $id_division
                                    AND s2.op_batch_id = $batch
                                    AND s2.pl_curriculum_id = $curriculum
                                    AND s2.seq = $periodo
                                    AND ss2.op_student_id = ss.op_student_id
                                    AND oficial = TRUE
                            )AS promedio
                            FROM
                                pl_section_student ss
                                JOIN pl_section s ON s. ID = ss.pl_section_id
                                JOIN pl_subject A ON A . ID = s.pl_subject_id
                                JOIN op_subject sub ON sub. ID = A .op_subject_id
                                JOIN op_student stu ON stu. ID = ss.op_student_id
                                JOIN res_partner res ON res. ID = stu.partner_id
                            WHERE
                                ss.op_division_id = $id_division
                                AND ss.op_batch_id = $batch
                                AND s.seq = $periodo
                                AND s.pl_curriculum_id = $curriculum
                                AND ss.active = TRUE
                                AND oficial = TRUE
                            ORDER BY
                            stu.roll_number";
            $ex_prom_sep = pg_query($query_prom_sep) or die("Tenemos un error con la base de datos, intenta mas tarde: #promedio $query_division" . pg_last_error());
            $cont_alum=0; 
            while ($prom_sep = pg_fetch_array($ex_prom_sep, null, PGSQL_ASSOC)) {
                $calificacion_sep=$prom_sep["promedio"];
                //TRUNCAR DECIMALES
                $calificacion_sep=truncateFloat($calificacion_sep, 1);
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna_alumnos, $fila_alumnos, $calificacion_sep);
                    $fila_alumnos++;
                    $cont_alum++; 
             }
            $fila_alumnos-=$cont_alum;
           $columna_alumnos ++;
          
        //poner los promedios WILLIAMS // TRUNCAR A 1 DECIMAL 
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna_alumnos, $fila_alumnos-2, "PROMEDIO WILLIAMS");
            $query_prom_will="SELECT DISTINCT
                                ss.op_student_id,
                                stu.roll_number,
                                COALESCE (stu.middle_name, ' ') || ' ' || COALESCE (stu.last_name, ' ') || ' ' || COALESCE (res. NAME, ' ') AS alumno,
                                (
                                        SELECT
                                                SUM (ss2.score) / COUNT (*) AS prom_sep
                                        FROM
                                                pl_section_student ss2
                                                JOIN pl_section s2 ON s2. ID = ss2.pl_section_id
                                                JOIN pl_subject A2 ON A2 . ID = s2.pl_subject_id
                                                JOIN op_subject sub2 ON sub2. ID = A2 .op_subject_id
                                                JOIN op_student stu2 ON stu2. ID = ss2.op_student_id
                                        WHERE
                                            ss2.op_division_id = $id_division
                                        AND s2.op_batch_id = $batch
                                        AND s2.pl_curriculum_id = $curriculum
                                        AND s2.seq = $periodo
                                        AND ss2.op_student_id = ss.op_student_id
                                        AND vertical_average=true
                                ) as promedio
                        FROM
                                pl_section_student ss
                            JOIN pl_section s ON s. ID = ss.pl_section_id
                            JOIN pl_subject A ON A . ID = s.pl_subject_id
                            JOIN op_subject sub ON sub. ID = A .op_subject_id
                            JOIN op_student stu ON stu. ID = ss.op_student_id
                            JOIN res_partner res ON res. ID = stu.partner_id
                        WHERE
                                ss.op_division_id = $id_division
                            AND ss.op_batch_id = $batch
                            AND s.seq = $periodo
                            AND s.pl_curriculum_id = $curriculum
                            AND ss.active = TRUE
                            and vertical_average=true
                        ORDER BY
                                stu.roll_number";
            $ex_prom_will = pg_query($query_prom_will) or die("Tenemos un error con la base de datos, intenta mas tarde: #promedio $query_division" . pg_last_error());
            $cont_alum=0; 
            while ($prom_will = pg_fetch_array($ex_prom_will, null, PGSQL_ASSOC)) {
                $calificacion_will=$prom_will["promedio"];
                $calificacion_will=truncateFloat($calificacion_will, 1);
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($columna_alumnos, $fila_alumnos, $calificacion_will);
                    $fila_alumnos++;
                    $cont_alum++; 
             }
            $fila_alumnos-=$cont_alum;
            
          
            
            $columna_materia=2;
            $columna_grupo=0;
            $columna_alumnos=2;
            $fila_grupo=$alumnos_por_grupo+5;
            $fila_materia=$fila_grupo;
            $fila_alumnos+=$alumnos_por_grupo+4;
            
        }//fin while division 
        $no_sheet++;
        $objPHPExcel->getActiveSheet()->getColumnDimension("B")->setAutoSize(TRUE);
        if ($no_sheet != 0) {
            $objPHPExcel->createSheet($no_sheet);
            $objPHPExcel->setActiveSheetIndex($no_sheet);
            //reiniciar variables
            $columna_grupo = 0;
            $fila_grupo = 1;
            $columna_materia = 1;
            $fila_materia = 2;
            $columna_evaluaciones = 0;
            $fila_evaluaciones = 2;
            $columna_alumnos = 1;
            $fila_alumnos = 4;
            $test_fila = 1;
            $columna_evals = 1;
            $fila_evals = 2;
            $fila_calif = 3;
        }
        
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(30, 2, "fin : ". date("H:i:s:u"));
    }//fin while standard

    if ($test == "debug") {
        exit();
    }
    ob_clean(); //Esta función desecha el contenido del búfer de salida. Con esto evitamos la basura dentro del archivo
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save('php://output');
    exit();
} else {
    echo("faltan datos");
    
}