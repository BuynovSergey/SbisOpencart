<?php
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding("UTF-8");

require_once('admin/config.php');
require_once 'Excel/SimpleXLSX.php'; //подключаем ридер xlsx

$mysqli = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE); //echo(DB_DATABASE);
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}
mysqli_set_charset($mysqli, "utf8");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Добавление описания к товарам</title>
</head>
<body>
<h2>Добавление описания к товарам</h2>
<form name='xls-form' enctype='multipart/form-data' method='post'>
<input type='file' name='userfile' style='margin:0 0 20px 0'>
<button type='submit'>Загрузить</button>
</form>

<?php
if($_FILES["userfile"]["size"] > 0){
    $path='processed/';
    array_map('unlink', glob($path."/*"));
    $tmp_name = $_FILES["userfile"]["tmp_name"];
    $name = str_replace(" ","_",$_FILES["userfile"]["name"]);
    move_uploaded_file($tmp_name, $path."/".$name);

    $sql = $mysqli->query("SELECT product_id, model FROM oc_product");
    while ($row = $sql->fetch_object()) {
        $arr_prod[$row->model] = $row->product_id;
    }

    /*if ($xlsx = SimpleXLSX::parse($path."/".$name)){
        foreach($xlsx->rows() as $val){
            if($val[2] && $co > 1 && $co < 10){
                echo($val[2]." - ".$val[3]."<br><br>");
            }
            $co++;
        }
    }*/

    $co = 1;
    $co_true = 0;
    $eols = array("\n","\r","\r\n");
    if ($xlsx = SimpleXLSX::parse($path."/".$name)){
        foreach($xlsx->rows() as $val){
            if($val[2] && $co > 1){
                if($val[3]){ //если есть описание
                    if($arr_prod[$val[2]]){
                        $last_word = '';
                        $text = str_replace("'","",$val[3]);
                        $text = str_replace("%20", " ", $text);
                        $pos = mb_strpos($text, 'http');
                        if($pos !== false){ // если в тексте есть ссылка, то находим и удаляем ее
                            $pos_end = mb_strpos($text, ' ', $pos);
                            if($pos_end){ //выбираем текст ссылки до пробела или переноса строки
                                $text_link = mb_substr($text, $pos, $pos_end-$pos);
                            } else {
                                $text_link = mb_substr($text, $pos);
                            }
                            if(preg_match('/\n|\r/',$text_link)){  //проверяем есть ли перенос строки
                                $text_link_arr = explode(" ",str_replace($eols,' ',$text_link));
                                $last_word = end($text_link_arr);
                            }
                            $new_text = trim(str_replace($text_link, $last_word, $text));
                            /*if($pos_end && mb_substr($text,$pos_end+1)){
                                $new_text_sm = mb_substr($text,($pos_end+1));
                                if(!$last_word){
                                    $new_text = mb_strtoupper(mb_substr($new_text_sm,0,1)).mb_substr($new_text_sm,1);
                                } else {
                                    $new_text = $last_word." ".$new_text_sm;
                                }
                            }*/
                        } else {
                            $new_text = $val[3];
                        }
                        if($new_text==$val[0]){
                            $new_text = '';
                        }
                        if($new_text){
                            $sql_des = "update oc_product_description set description='".$new_text."' where product_id=".$arr_prod[$val[2]];
                            //if($co == 5){
                                $mysqli->query($sql_des);
                            //}
                            $co_true++;
                        }
                        echo("<span style='color:#17a715;'>Код: ".$val[2]." - OK</span> ".$new_text."<br><br>");
                    } else {
                        echo("<span style='color:#ff0000;'>Не найден товар, Код: ".$val[2]."</span><br><br>");
                    }
                } else {
                    echo("<span style='color:#9b8105;'>Код: ".$val[2]." - Нет описания</span><br><br>");
                }
            }
            $co++;
        }
        if($co_true>0){ echo("<p style='color:#56ad48; font-size:24px'>Файл успешно выгружен!</p><p>".$co_true." - кол-во строк выгружено</p>"); }
        array_map('unlink', glob(__DIR__.'/system/cache/*'));
    } else {
        echo SimpleXLSX::parseError();
    }
}
?>
</body>
</html>