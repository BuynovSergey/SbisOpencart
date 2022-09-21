<?php
header('Content-Type: text/html; charset=utf-8');
require_once('admin/config.php');
$mysqli = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE); echo(DB_DATABASE);
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}
//$mysqli->exec('SET CHARACTER SET utf8');
mysqli_set_charset( $mysqli, 'utf8');
$sql = $mysqli->query("SELECT category_id, name FROM oc_category_description"); 
while ($row = $sql->fetch_object()) {
    $arr_cat[$row->category_id] = $row->name;
}
$sql = $mysqli->query("SELECT category_id, parent_id, sbis_id FROM oc_category"); 
while ($row = $sql->fetch_object()) {
    $arr_cat2[$row->category_id] = array($row->sbis_id,$row->parent_id);
}
$sql = $mysqli->query("SELECT query, keyword FROM oc_url_alias where `query` rlike concat( '^' , 'category_id=' )"); 
while ($row = $sql->fetch_object()) {
	$el = explode("=",$row->query);
    $arr_cat_url[$el[1]] = $row->keyword;
}
$sql = $mysqli->query("SELECT product_id, sbis_id, image FROM oc_product"); 
while ($row = $sql->fetch_object()) {
    $arr_prod[$row->product_id] = $row->sbis_id;
	$arr_prod_img[$row->product_id] = $row->image;
}
$sql = $mysqli->query("SELECT product_id, category_id FROM oc_product_to_category"); 
while ($row = $sql->fetch_object()) {
    $arr_prod_cat[$row->product_id] = $row->category_id;
}
$sql = $mysqli->query("SELECT level, check_sbis FROM log_sbis"); 
while ($row = $sql->fetch_object()) {
    $check_sbis = $row->check_sbis;
	$level = $row->level;
}
if($check_sbis==1){
	$sql_log = "update log_sbis set level='0', check_sbis='0'";
	$mysqli->query($sql_log);
	$level=0;
}
function rus2translit($string) {
    $converter = array(
        'а' => 'a',   'б' => 'b',   'в' => 'v',
        'г' => 'g',   'д' => 'd',   'е' => 'e',
        'ё' => 'e',   'ж' => 'zh',  'з' => 'z',
        'и' => 'i',   'й' => 'y',   'к' => 'k',
        'л' => 'l',   'м' => 'm',   'н' => 'n',
        'о' => 'o',   'п' => 'p',   'р' => 'r',
        'с' => 's',   'т' => 't',   'у' => 'u',
        'ф' => 'f',   'х' => 'h',   'ц' => 'c',
        'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',
        'ь' => '',  'ы' => 'y',   'ъ' => '',
        'э' => 'e',   'ю' => 'yu',  'я' => 'ya',
        
        'А' => 'A',   'Б' => 'B',   'В' => 'V',
        'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
        'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',
        'И' => 'I',   'Й' => 'Y',   'К' => 'K',
        'Л' => 'L',   'М' => 'M',   'Н' => 'N',
        'О' => 'O',   'П' => 'P',   'Р' => 'R',
        'С' => 'S',   'Т' => 'T',   'У' => 'U',
        'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
        'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',
        'Ь' => '',  'Ы' => 'Y',   'Ъ' => '',
        'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',
    );
    return strtr($string, $converter);
}
function str2url($str) {
    // переводим в транслит
    $str = rus2translit($str);
    // в нижний регистр
    $str = strtolower($str);
    // заменям все ненужное нам на "_"
    $str = preg_replace('~[^_a-z0-9_]+~u', '-', $str);
    $str = trim($str, "-");
    return $str;
}
function countArray($array,$search){
    $co=0;
	foreach($array as $key => $val){
        if($val[1] == $search){
        	$co++;
        }
    }
	$co++;
     return $co;
}
function searchArray($array,$search){
    foreach($array as $key => $val){
        if($val[0] == $search){
        	return $key;
        }
    }
     return false;
} 
function searchTov($search){
    global $arr_prod;
	foreach($arr_prod as $key => $val){
        if($val == $search){
        	return $key;
        }
    }
    return false;
} 
function searchArrayCat($array,$search){
    foreach($array as $key => $val){
        foreach($val as $val2){
            if($val2 == $search){
            	return $key;
            }
        }
    }
     return false;
}
function searchUrl($search){
    global $arr_cat_url;
    foreach($arr_cat_url as $key => $val){
        if(trim($val) == trim($search)){
        	return $key;
        }
    }
    return false;
}
function searchLevel($search, $i, $arr){
    global $arr_cat2;
	$arr[$i] = $search;
	$parent = $arr_cat2[$search][1]; //echo("!".$parent."-".$i."<br>!");
	if($parent == 0){
		return $arr;
	} else {
		$i++;
		$sel = searchLevel($parent, $i, $arr);
	}
	if($sel){ return $sel; }
}

$auth = array(
    'app_client_id'=>'***',
    'app_secret'=>'***',
    'secret_key'=>'***'
);
$auth = json_encode($auth);
$ch = curl_init();
curl_setopt_array($ch, array(
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_URL => 'https://online.sbis.ru/oauth/service/',
    CURLOPT_POST => true,
    CURLOPT_HEADER => 0,
    CURLOPT_POSTFIELDS => $auth,
    CURLOPT_HTTPHEADER =>  array(
        'Content-type: charset=utf-8'
        )
));
$response = curl_exec($ch);
$obj=json_decode($response);
//print_r($obj->access_token);
//curl_close($ch);
curl_close($ch);
$ch2 = curl_init();
curl_setopt_array($ch2, array(
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_URL => 'https://api.sbis.ru/retail/point/list?withPhones=true&withPrices=true&withSchedule=true&page=0&pageSize=10',
    CURLOPT_HEADER => 0,
    CURLOPT_HTTPHEADER =>  array(
        'Content-type: charset=utf-8',
        'X-SBISAccessToken: '.$obj->access_token
        )
));
$response2 = curl_exec($ch2);
$obj2=json_decode($response2);
$pointId = $obj2->salesPoints[0]->id;
curl_close($ch2);

$ch3 = curl_init();
curl_setopt_array($ch3, array(
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_URL => 'https://api.sbis.ru/retail/nomenclature/price-list?pointId='.$pointId.'&actualDate='.date("d.m.y").'&page=0&pageSize=5000',
    CURLOPT_HEADER => 0,
    CURLOPT_HTTPHEADER =>  array(
        'Content-type: charset=utf-8',
        'X-SBISAccessToken: '.$obj->access_token
        )
));
$response3 = curl_exec($ch3);
$obj3=json_decode($response3);
$priceListId = $obj3->priceLists[0]->id; 
curl_close($ch3);

$ch4 = curl_init();
curl_setopt_array($ch4, array(
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_URL => 'https://api.sbis.ru/retail/nomenclature/list?&pointId='.$pointId.'&priceListId='.$priceListId.'&withBalance=true&page='.$level.'&pageSize=2000',
    CURLOPT_HEADER => 0,
    CURLOPT_HTTPHEADER =>  array(
        'Content-type: charset=utf-8',
        'X-SBISAccessToken: '.$obj->access_token
        )
));
$response4 = curl_exec($ch4);
$obj4=json_decode($response4); print_r($obj4);

foreach($obj4->nomenclatures as $val){
    if(!$val->id){
		$arr_cat_sbis[$val->hierarchicalId] = trim($val->name);
	}
} 
$permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyz';

if($check_sbis==1){
	$mysqli->query("update oc_category set check_sbis='0'");
	$mysqli->query("update oc_product set check_sbis='0'");
}
$k=0;

foreach($obj4->nomenclatures as $val){
	if(!$val->id){ 
	    if($val->hierarchicalId){
    	    $cat_id = searchArray($arr_cat2,$val->hierarchicalId);
    	    $cat_name = $val->name;
    	    if($val->hierarchicalParent){
	           $cat_id_parent = searchArray($arr_cat2,$val->hierarchicalParent); 
	        } else {
	           $cat_id_parent = 0;
	        }
			$co_url = searchUrl(str2url($cat_name));
	        if($co_url){
	            $en_url = str2url($cat_name).substr(str_shuffle($permitted_chars), 0, 5);
	        } else {
	            $en_url = str2url($cat_name);
	        }
			$arr_cat[$cat_id] = $cat_name;
    	    if(!$cat_id){
    	        echo($val->hierarchicalId." - insert ('".$val->hierarchicalParent."', '".$cat_id_parent."', '".countArray($arr_cat2,$cat_id_parent)."', ".$cat_name."', '".$en_url."')<br>");
    	        $mysqli->query("insert into oc_category (parent_id, `column`, image, sort_order, `status`, `top`, date_added, date_modified, sbis_id, check_sbis) values ('".$cat_id_parent."', 1, '', ".countArray($arr_cat2,$cat_id_parent).", 1, '".($val->hierarchicalParent ? "" : "1")."', NOW(), NOW(),'".$val->hierarchicalId."',1)");
				$cat_id = $mysqli->insert_id;				
				$arr_cat2[$cat_id] = array($val->hierarchicalId,$cat_id_parent);
				if($cat_id){
					$sql_des = "insert into oc_category_description (category_id, language_id, name, meta_description, meta_keyword) values ('".$cat_id."', 1, '".$cat_name."', '".$cat_name."', '".$cat_name."')";
					$mysqli->query($sql_des);
					echo($sql_des."<br>");
					$arr_level_tmp = searchLevel($cat_id,0);
					rsort($arr_level_tmp);
					$j=(count($arr_level_tmp)-1);
					foreach($arr_level_tmp as $val){
						$arr_level[$j] = $val;
						$j--;
					}
					foreach($arr_level as $key_path => $val_path){
						$sql_level = "insert into oc_category_path (category_id, path_id, level) values ('".$cat_id."', '".$val_path."', '".$key_path."')";
						$mysqli->query($sql_level);
						echo($sql_level."<br>");
					}
					$sql_layout = "insert into oc_category_to_layout (category_id, store_id, layout_id) values ('".$cat_id."', 0, 0)";
					$mysqli->query($sql_layout);
					echo($sql_layout."<br>");
					$sql_store = "insert into oc_category_to_store (category_id, store_id) values ('".$cat_id."', 0)";
					$mysqli->query($sql_store);
					echo($sql_store."<br>");
					$sql_alias = "insert into oc_url_alias (query, keyword) values ('category_id=".$cat_id."', '".$en_url."')";
					$mysqli->query($sql_alias);
					echo($sql_alias."<br><br>");
				}
    	        
    	    } else {
				$par_old = $arr_cat2[$cat_id][1];	
    		    $sql_category = "update oc_category set parent_id='".$cat_id_parent."'".($val->published ? ", `status`=1, check_sbis=1" : "")." where sbis_id='".$val->hierarchicalId."'";
				$mysqli->query($sql_category);
				echo($sql_category."<br><br>");
				$sql_des = "update oc_category_description set name='".$cat_name."', meta_description='".$cat_name."', meta_keyword='".$cat_name."' where category_id='".$cat_id."'";
				$mysqli->query($sql_des);
				echo($sql_des."<br><br>");
				if($par_old != $cat_id_parent){
					$arr_cat2[$cat_id] = array($val->hierarchicalId,$cat_id_parent,$par_old);
					$arr_level_tmp = searchLevel($cat_id,0);
					rsort($arr_level_tmp);
					$j=(count($arr_level_tmp)-1);
					foreach($arr_level_tmp as $val){
						$arr_level[$j] = $val;
						$j--;
					}
					$sql_level = "delete from oc_category_path where category_id='".$cat_id."'";
					$mysqli->query($sql_level);
					echo($sql_level."<br>");
					foreach($arr_level as $key_path => $val_path){
						$sql_level = "insert into oc_category_path (category_id, path_id, level) values ('".$cat_id."', '".$val_path."', '".$key_path."')";
						$mysqli->query($sql_level);
						echo($sql_level."<br>");
					}
				}
    	    }
	    }
		$k++;
	}
}


$d=0;
function image($tov_id,$image,$token){
	global $arr_prod_img;
	$file_path=__DIR__.'/image/catalog/tmp_images_sbis/product_'.$tov_id.'_0.jpg'; echo($file_path);
	$filename='catalog/tmp_images_sbis/product_'.$tov_id.'_0.jpg';
	if($arr_prod_img[$tov_id]){ 
		echo("Картинка есть");
		if(file_exists($file_path)){
			unlink($file_path);
		}
	} else {
		echo("Картинки нет - ".$image);
	}
	$ch_img = curl_init();
	curl_setopt_array($ch_img, array(
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_FOLLOWLOCATION => false,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_URL => 'https://api.sbis.ru/retail'.$image,
		CURLOPT_HEADER => 0,
		CURLOPT_HTTPHEADER =>  array(
			'Content-type: charset=utf-8',
			'X-SBISAccessToken: '.$token
			)
	));
	$response_img = curl_exec($ch_img);
	curl_close($ch_img);
	$file = fopen($file_path, 'w');
	fwrite($file, $response_img);
	fclose($file);
	return $filename;
}
foreach($obj4->nomenclatures as $val){
	if($val->id){
		$tov_id = searchTov($val->id);
		$cat_id = searchArray($arr_cat2,$val->hierarchicalParent);
		$filename = '';
		if(!$tov_id){
			$sql_tov = "insert into oc_product (model, quantity, stock_status_id, shipping, price, date_available, weight_class_id, length_class_id, subtract, minimum, sort_order, status, date_added, date_modified, sbis_id, check_sbis) values ('".$val->nomNumber."', '".$val->balance."', '".($val->balance > 0 ? "7" : "5")."', '1', '".$val->cost.".0000', NOW(), '2', '1', '1', '1', '1', '1', NOW(), NOW(), '".$val->id."', '".($val->published ? "1" : "0")."')";
			echo($sql_tov."<br>");
			$mysqli->query($sql_tov);
			$res_id = $mysqli->insert_id;
			
			$sql_attr = "insert into oc_product_attribute (product_id, attribute_id, language_id) values ('".$res_id."', '13', '1')";
			$mysqli->query($sql_attr);
			echo($sql_attr."<br>");
			
			$sql_des = "insert into oc_product_description (product_id, language_id, name) values ('".$res_id."', '1', '".$val->name."')";
			$mysqli->query($sql_des);
			echo($sql_des."<br>");
			
			$sql_lay = "insert into oc_product_to_layout (product_id, store_id, layout_id) values ('".$res_id."', '0', '0')";
			$mysqli->query($sql_lay);
			echo($sql_lay."<br>");
			
			$sql_store = "insert into oc_product_to_store (product_id, store_id) values ('".$res_id."', '0')";
			$mysqli->query($sql_store);
			echo($sql_store."<br>");
			
			$arr_level_tov = searchLevel($cat_id,0); 
			
			foreach($arr_level_tov as $val2){
				$sql_cat = "insert into oc_product_to_category (product_id, category_id) values ('".$res_id."', '".$val2."')";
				$mysqli->query($sql_cat);
				echo($sql_cat."<br>");
			}
			if($val->images[0]){ 
				$filename = image($res_id,$val->images[0],$obj->access_token);
				if($filename){
					$sql_img = "update oc_product set image='".$filename."' where product_id='".$res_id."'";
					$mysqli->query($sql_img);
					echo($sql_img."<br>");
				}
			}			
		} else {
			if($val->images[0]){
				$filename = image($tov_id,$val->images[0],$obj->access_token);
			}
			$sql_tov = "update oc_product set quantity='".$val->balance."', stock_status_id='".($val->balance > 0 ? "7" : "5")."', price='".$val->cost.".0000', date_modified=NOW(), status='".($val->published ? "1" : "0")."', check_sbis='".($val->published ? "1" : "0")."', image='".$filename."' where product_id='".$tov_id."'";
			echo($sql_tov."<br>");
			$mysqli->query($sql_tov);
			
			$sql_des = "update oc_product_description name='".$val->name."' where product_id='".$tov_id."'";
			$mysqli->query($sql_des);
			echo($sql_des."<br>");
			
			if($arr_prod_cat[$tov_id]!=$cat_id || $arr_cat2[$cat_id][2]!=''){ 
				$sql_level = "delete from oc_product_to_category where product_id='".$tov_id."'";
				$mysqli->query($sql_level);
				echo($sql_level."<br>");
				$arr_level_tov = searchLevel($cat_id,0);
				foreach($arr_level_tov as $val){
					$sql_cat = "insert into oc_product_to_category (product_id, category_id) values ('".$tov_id."', '".$val."')";
					$mysqli->query($sql_cat);
					echo($sql_cat."<br>");
				}
			}
		}
		if(!$cat_id) {
	       echo($val->id." - Товар не нашел раздел<br>");
	    }
		$d++;
	}
}

if($obj4->outcome->hasMore){
	$sql_log = "update log_sbis set level='".($level+1)."', check_sbis='0'";
	$mysqli->query($sql_log);
} else {
	$sql_log = "update log_sbis set level='0', check_sbis='1'";
	$mysqli->query($sql_log);
	$mysqli->query("update oc_category set status=0 where check_sbis='0'"); 
	$mysqli->query("update oc_product set status=0 where check_sbis='0'");
	array_map('unlink', glob(__DIR__.'/system/cache/*'));
}
curl_close($ch4);

?>