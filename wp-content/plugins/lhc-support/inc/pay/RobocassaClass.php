<?php
/**
 * RobocassaClass.php
 * 18:40
 */
if ( ! defined( 'ABSPATH' ) ) { echo 'Silence gold.'; exit(403); }

class RobocassaClass{
    public $key = '';
    public $pass1 = '';
    public $pass2 = '';
    public $pass1T = '';
    public $pass2T = '';

    public $istest = true;


    public function __construct(){
        $this->istest = false;
        $this->pass1 = PAY_ROBO_PASS1;
        $this->pass2 = PAY_ROBO_PASS2;
        $this->login = PAY_ROBO_SHOP_ID;
        if($this->istest){
            $this->pass1 = PAY_ROBO_PASS1_T;
            $this->pass2 = PAY_ROBO_PASS2_T;
        }
    }


    public function form($uid, $sum, $bill_id, $todo='', $task='', $data=[]){
        // addMess([$uid, $sum, $bill_id],'form');
        $bill = wsd_get_bill($bill_id);
        $uid = $bill['uid'];
        $pid = $bill['pid'];
        $bill_type = $bill['type'];
        $user = get_userdata($uid);
        $mail = $user->user_email;
        $out = '';

        // регистрационная информация (Идентификатор магазина, пароль №1)
        // registration info (Merchant ID, password #1)
        $merchant_login = "demo";
        $password_1 = "password_1";
        $merchant_login = $this->login;
        $password_1 = $this->pass1;
        $IsTest = 1;
        $IsTest = 0;
        // IsTest=$IsTest
        // номер заказа
        // number of order
        $invid = 12345;
        $invid = $bill['id'];
        // описание заказа
        // order description
        $description = "Техническая документация по ROBOKASSA";
        $description = "Оплата тарифа";
        if($bill_type == 'tarif')$description = "Оплата тарифа";
        if($bill_type == 'qr')$description = "Оплата доставки";
        // сумма заказа
        // sum of order
        $out_sum = "8.96";
        $out_sum = "8.96";
        $out_sum = $sum;
        // Товарная номенклатура (Receipt) в url encode
        // Product Nomenclature (Receipt) in url encode
        
        // Before url encode - {"items":[{"name":"product","quantity":1,"sum":1,"tax":"none"}]}

        // addMess($bill);

        // Фискализация для клиентов Robokassa
        // https://docs.robokassa.ru/fiscalization/
        // коды ошибок
        // https://robokassa.com/content/tipichnye-oshibki.html
        $product = [];
        // name Обязательное поле. Наименование товара. Строка, максимальная длина 128 символа
        $product['name'] = $bill['goods']; 
        $product['quantity'] = 1;
        $product['sum'] = $sum;
        $product['tax'] = "none";

        // $product['name'] = "product";
        // $product['quantity'] = 1;
        // $product['sum'] = 1;
        // $product['tax'] = "none";

        $items = [];
        $items[] = $product;
        $pif = ['items'=>$items];
        $productinfo = json_encode($pif);
        $receipt_ = urlencode($productinfo);

        $receipt = "%7B%22items%22%3A%5B%7B%22name%22%3A%22product%22%2C%22quantity%22%3A1%2C%22sum%22%3A1%2C%22tax%22%3A%22none%22%7D%5D%7D";

        // addMess($receipt_);
        // addMess($receipt);

        $receipt = "%7B%22"
        ."items"."%22%3A%5B%7B%22"
        ."name"."%22%3A%22"."product"."%22%2C%22"
        ."quantity"."%22%3A"."1"."%2C%22"
        ."sum"."%22%3A"."1"."%2C%22"
        ."tax"."%22%3A%22"."none"."%22%7D%5D%7D";

        $receipt = "%7B%22"
        ."items"."%22%3A%5B%7B%22"
        ."name"."%22%3A%22"."Страница"."%22%2C%22"
        ."quantity"."%22%3A"."1"."%2C%22"
        ."sum"."%22%3A".$sum."%2C%22"
        ."tax"."%22%3A%22"."none"."%22%7D%5D%7D";

        $receipt = urlencode($productinfo);
        // предлагаемая валюта платежа
        // default payment e-currency
        $incurrlabel = "BANKOCEAN2R";
        // язык
        // language
        $culture = "ru";
        // кодировка
        // encoding
        $encoding = "utf-8";
        // Адрес электронной почты покупателя
        // E-mail
        // $Email = "test@test.ru";
        $Email = $mail;
        // Срок действия счёта
        // Expiration Date
        $ExpirationDate = "2029-01-16T12:00";
        $ExpirationDate = date("Y-m-dTH:i", time() + (86400*30) );
        // Дополнительные пользовательские параметры
        // Shp_item
        $Shp_item = "Shp_oplata=1";
        $shp_item = "Shp_oplata=150";
        $shp_sys = "robo";
        $shp_todo = $todo;
        $shp_type = $bill_type;
        $shp_task = $task;

        $main_args = [];
        $query_args = [];

        $main_args[] = $merchant_login;
        $main_args[] = $out_sum;
        $main_args[] = $invid;
        $main_args[] = $receipt;
        $main_args[] = $password_1;
        $query_args['Shp_sys'] = $shp_sys;
        $query_args['Shp_pid'] = $pid;
        $query_args['Shp_uid'] = $uid;
        $query_args['Shp_todo'] = $shp_todo;
        $query_args['Shp_type'] = $shp_type;
        $query_args['Shp_task'] = $shp_task;
        if($data) foreach($data as $k=>$v){$query_args['Shp_'.$k] = $v;}
        ksort($query_args);
        $main_args[] = http_build_query($query_args,'Shp_',':');
        $secstr = implode(':',$main_args);

        // echo div( http_build_query($query_args,'Shp_',':') );

        // echo div( $secstr);

        // формирование подписи
        // generate signature
        $signature_value = md5("$merchant_login:$out_sum:$invid:$receipt:$password_1:Shp_item=$shp_item");
        $signature_value = md5("$merchant_login:$out_sum:$invid:$receipt:$password_1:Shp_sys=$shp_sys");
        $signature_value = md5($secstr);
        // форма оплаты товара
        // payment form
        $type = 'hidden';
        // $type = 'text';

        $wtplo = '<div class="d-fw w100">';
        $fields = [];
        foreach($query_args as $k=>$v){ 
            $fields[] = ($type == 'text'?$wtplo.$k.'':'') . input($k, $v, $type) . ($type == 'text'?'</div>':'');
        }
        $fields = implode('', $fields);

        $out = 
        //"<html>".
        "<div><img src=\"".WSD_SUB_THEME_URI.'assets/img/payment/robokassa.svg'."\">".
            "<div><b>$out_sum р.</b></div>".
            "<form action='https://auth.robokassa.ru/Merchant/Index.aspx' method=POST>".
            "<input type=$type name=MerchantLogin value=$merchant_login>".
            "<input type=$type name=OutSum value=$out_sum>".
            "<input type=$type name=InvId value=$invid>".
            "<input type=$type name=Description value='$description'>".
            "<input type=$type name=SignatureValue value=$signature_value>".
            // "<input type=$type name=Shp_item value='$shp_item'>".
            // "<input type=$type name=Shp_sys value='$shp_sys'>".
            $fields.
            "<input type=$type name=IncCurrLabel value=$incurrlabel>".
            "<input type=$type name=Culture value=$culture>".
            "<input type=$type name=Email value=$Email>".
            "<input type=$type name=ExpirationDate value=$ExpirationDate>".
            "<input type=$type name=Receipt value=$receipt>".
            ($IsTest? "<input type=$type name=IsTest value=$IsTest>" :"").
            "<input class=\"btn black w400\" type=submit value='Оплатить'>".
        "</form></div>"
        //."</html>"
        ;
        return $out;
    }


    public function payEvent(){
            
        // as a part of ResultURL script
        
        // $this->pass1 = PAY_ROBO_PASS1_T;
        // $this->pass2 = PAY_ROBO_PASS2_T;
        // your registration data
        $mrh_pass2 = "securepass2";   // merchant pass2 here
        $mrh_pass2 = $this->pass2;   // merchant pass2 here
        
        // HTTP parameters:
        $out_summ = $_REQUEST["OutSum"];
        $inv_id = $_REQUEST["InvId"];
        $crc = strtoupper($_REQUEST["SignatureValue"]);

        $sysev = filter_input(INPUT_GET, 'sysev', FILTER_UNSAFE_RAW);
        $sys = filter_input(INPUT_GET, 'sys', FILTER_UNSAFE_RAW);

        $inv_id = filter_input(INPUT_POST, 'InvId', FILTER_UNSAFE_RAW);
        $out_sum = filter_input(INPUT_POST, 'OutSum', FILTER_UNSAFE_RAW);
        $shp_sys = filter_input(INPUT_POST, 'Shp_sys', FILTER_UNSAFE_RAW);
        $crc = filter_input(INPUT_POST, 'SignatureValue', FILTER_UNSAFE_RAW);
        $fee = filter_input(INPUT_POST, 'Fee', FILTER_UNSAFE_RAW);

        $main_args = [];
        $query_args = [];

        $main_args[] = $out_sum;
        $main_args[] = $inv_id;
        $main_args[] = $mrh_pass2;
        foreach($_POST as $k=>$v){ if(strpos($k, 'Shp_') === 0) $query_args[$k] = $v; }
        ksort($query_args);
        $main_args[] = http_build_query($query_args,'Shp_',':');
        $secstr = implode(':',$main_args);

        
        // build own CRC
        // $my_crc = strtoupper(md5("$out_summ:$inv_id:$mrh_pass2"));
        // $signature_value =md5("$merchant_login:$out_sum:$invid:$receipt:$mrh_pass2:Shp_sys=$shp_sys");
        // $signature_value =md5("$out_sum:$invid:$receipt:$mrh_pass2:Shp_sys=$shp_sys");

        $signature_value =md5("$out_sum:$inv_id:$mrh_pass2:Shp_sys=$shp_sys");
        $signature_value =md5($secstr);
        $my_crc = strtoupper($signature_value);
        
        if ($my_crc != $crc)
        {
            wsd_addlog('payment', 'payEventRobo', $_data=['src'=>$my_crc,'src2'=>$crc], 
            $status = 'error', $file = __FILE__, __LINE__);
            echo "bad sign\n";
            exit();
        }
        $bill = wsd_get_bill($inv_id);

        wsd_addlog('payment', 'payEventRobo', $_data=['src'=>$my_crc],
        $status = 'success', $file = __FILE__, __LINE__);

        $query_args = [];
        foreach($_POST as $k=>$v){ if(strpos($k, 'Shp_') === 0) $query_args[substr($k, 4)] = $v; }

        $data = [];
        $data['bill_id'] = $inv_id;
        $data['sum'] = $out_sum;
        $data['fee'] = $fee;
        $bill_type = $bill['type'];
        $data['type'] = $bill_type;
        $data['payment_status'] = 'success';
        $data['args'] = $query_args;

        do_action('payment_success', 'robo', $inv_id, $data);
        // do_action('lhc_action_todo', 
        //     $query_args['todo'], $query_args['type'], $query_args['task'], $query_args);
        
        $answer = "OK$inv_id\n";
        wsd_addlog('payment', 'roboResult', 
            $_data=['answer'=> $answer], 
            $status = 'info');
        // print OK signature
        echo $answer;
        exit();
    }

    // add_action('payment_success_user', [$this, 'payEventSuccessUser'], 10, 3 );
    // add_action('payment_error_user', [$this, 'payEventFailUser'], 10, 3 );

    public function payEventUser(){
            
        // as a part of ResultURL script
        
        // $this->pass1 = PAY_ROBO_PASS1_T;
        // $this->pass2 = PAY_ROBO_PASS2_T;
        // your registration data
        $mrh_pass2 = "securepass2";   // merchant pass2 here
        $mrh_pass1 = $this->pass1;   // merchant pass2 here
        $mrh_pass2 = $this->pass2;   // merchant pass2 here
        
        // HTTP parameters:
        $out_summ = $_REQUEST["OutSum"];
        $inv_id = $_REQUEST["InvId"];
        $crc = strtoupper($_REQUEST["SignatureValue"]);

        $payres = filter_input(INPUT_POST, 'payres', FILTER_UNSAFE_RAW);
        $sysev = filter_input(INPUT_GET, 'sysev', FILTER_UNSAFE_RAW);
        $sys = filter_input(INPUT_GET, 'sys', FILTER_UNSAFE_RAW);

        $inv_id = filter_input(INPUT_POST, 'InvId', FILTER_UNSAFE_RAW);
        $out_sum = filter_input(INPUT_POST, 'OutSum', FILTER_UNSAFE_RAW);
        $shp_sys = filter_input(INPUT_POST, 'Shp_sys', FILTER_UNSAFE_RAW);
        $crc = filter_input(INPUT_POST, 'SignatureValue', FILTER_UNSAFE_RAW);
        $fee = filter_input(INPUT_POST, 'Fee', FILTER_UNSAFE_RAW);
        
        // build own CRC
        // $my_crc = strtoupper(md5("$out_summ:$inv_id:$mrh_pass2"));
        // $signature_value =md5("$merchant_login:$out_sum:$invid:$receipt:$mrh_pass2:Shp_sys=$shp_sys");
        // $signature_value =md5("$out_sum:$invid:$receipt:$mrh_pass2:Shp_sys=$shp_sys");

        $sig = "$out_sum:$inv_id:$mrh_pass2:Shp_sys=$shp_sys"; // result Query
        $sig = "$out_sum:$inv_id:$mrh_pass1:Shp_sys=$shp_sys" ;// redirect Query

        $tpl = '_osum_:_bill_id_:_p1_:_sh1n_=_sh1v_';

        $r = [];
        $r['_p1_'] = $this->pass1;
        $r['_p2_'] = $this->pass2;
        $r['_osum_'] = $out_sum;
        $r['_bill_id_'] = $inv_id;
        $r['_sh1n_'] = 'Shp_sys';
        $r['_sh1v_'] = $shp_sys;
        
        $sig = strtr($tpl, $r);
        $signature_value = md5($sig);

        $main_args = [];
        $query_args = [];

        $main_args[] = $out_sum;
        $main_args[] = $inv_id;
        $main_args[] = $this->pass1; // $mrh_pass2;
        foreach($_POST as $k=>$v){ if(strpos($k, 'Shp_') === 0) $query_args[$k] = $v; }
        $main_args[] = http_build_query($query_args,'Shp_',':');
        $secstr = implode(':',$main_args);
        $signature_value = md5($secstr);

        $my_crc = strtoupper($signature_value);
        $crc = strtoupper($crc);
        
        if ($my_crc != $crc)
        {


// Shp_pid=264:Shp_sys=robo:Shp_tarif=max:Shp_task=update:Shp_todo=tarif-up:Shp_type=tarif-up:Shp_uid=1
// Shp_pid=264:Shp_sys=robo:Shp_tarif=max:Shp_task=update:Shp_todo=tarif-up:Shp_type=tarif-up:Shp_uid=1
// 7990.00:42:aa0nHCsoN0c2WVSS9sx8:Shp_pid=264:Shp_sys=robo:Shp_tarif=max:Shp_task=update:Shp_todo=tarif-up:Shp_type=tarif-up:Shp_uid=1
// bad sign

            // echo div( http_build_query($query_args,'Shp_',':') );
            // echo div($secstr);
            // echo div($my_crc);
            // echo div($crc);
            // wsd_addlog('payment', 'payEventUserRobo', ['src'=>$my_crc,'src2'=>$crc], 
            // $status = 'error');
            echo "bad sign\n";
            exit();
        }

        // wsd_addlog('payment', 'payEventUserRobo', ['src'=>$my_crc],
        // $status = 'success');

        $query_args = [];
        foreach($_POST as $k=>$v){ if(strpos($k, 'Shp_') === 0) $query_args[substr($k, 4)] = $v; }

        $data = [];
        $data['bill_id'] = $inv_id;
        $data['sum'] = $out_sum;
        $data['fee'] = $fee;
        $data['args'] = $query_args;
        if($payres == 'success') {
            $data['payment_status'] = 'success';
            do_action('payment_success_user', 'robo', $inv_id, $data);
        }
        if($payres == 'fail') {
            $data['payment_status'] = 'fail';
            do_action('payment_fail_user', 'robo', $inv_id, $data);
        }
        
        // print OK signature
        // echo "OK$inv_id\n";
        wp_redirect(URL_ACCOUNT); // site_url('/account/')
        exit();
    }


    public function send(){
        if(!$this->sys) return false;
        $res = $this->_send();
    }


    public function _send(){
        $res = '';
        return $res;
    }


    public function success(){

    }


    public function error(){

    }
}