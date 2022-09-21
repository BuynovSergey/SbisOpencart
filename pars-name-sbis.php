<?php
require_once('config.php');
$mysqli = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
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
print_r($obj3);
$priceListId = $obj3->priceLists[0]->id; 
curl_close($ch3);

$ch4 = curl_init();
curl_setopt_array($ch4, array(
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_URL => 'https://api.sbis.ru/retail/nomenclature/list?&pointId='.$pointId.'&priceListId='.$priceListId.'&withBalance=true&page=0&pageSize=5000',
    CURLOPT_HEADER => 0,
    CURLOPT_HTTPHEADER =>  array(
        'Content-type: charset=utf-8',
        'X-SBISAccessToken: '.$obj->access_token
        )
));
$response4 = curl_exec($ch4);
$obj4=json_decode($response4);
curl_close($ch4);
$sql = $mysqli->query("SELECT product_id, name FROM oc_product_description"); 
while ($row = $sql->fetch_object()) {
    $arr_prod[$row->product_id] = $row->name;
}

function searchArray($array,$search){
    foreach($array as $key => $val){
        if(trim($val) == trim($search)){
        	return $key;
        }
    }
     return false;
}
$co1 = 0;
$co2 = 0;
foreach($obj4->nomenclatures as $val){
    if($val->id){
    $id_tov = searchArray($arr_prod,$val->name);
    /*if(!$id_tov){
        $id_tov = searchArray($arr_prod,str_replace("?","×",$val->name));
    }*/
    if($id_tov){
        echo("<p style='color:green'>".$id_tov." - !".$val->name."! update s_products set sbis_id='".$val->id."' where id=".$id_tov."</p>");
        //$mysqli->query("update s_products set sbis_id='".$val->id."' where id=".$id_tov.""); 
        $co2++;
    } else {
        echo("<p style='color:red'>".$val->id." - !".$val->name."!</p>");
        $co1++;
    
    }
    }
}
echo("ДА - ".$co2);
echo("<br>Нет - ".$co1);
?>