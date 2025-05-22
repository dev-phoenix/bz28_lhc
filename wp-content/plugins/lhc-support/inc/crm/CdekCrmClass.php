<?php
/**
* CdekCrmClass.php
* File: CdekCrmClass.php
* Descriptin: CDEK delivery crm api Class
* Author: WSD
* Created: 2024.10.16 06:15
*/

if ( ! defined( 'ABSPATH' ) ) { echo 'Silence gold.'; exit(403); }

/*

[Список офисов](https://api-docs.cdek.ru/36982648.html)
заказы клиента, созданные под тестовой учетной записью, 
не отображаются в личном кабинете клиента на сайте www.lk.cdek.ru;
*/
class CdekCrmClass{
    public $apiname = 'cdek';
    public $apititle = 'Cdek Api';
    public $istest = true;
    public $key = '';
    public $pass = '';
    public $oakey = '';
    public $token = '';

    public $task = '';
    public $url = '';
    public $path = '';
    public $query = ''; 
    public $data = '';
    public $mapkey = '';
    public $mapkeysugg = '';

    public $urls = [
        'ofices' => 'deliverypoints',
        // 'token' => 'oauth/token',
        'token' => 'oauth/token?parameters', // ?parameters
        
        'countries' => 'location/countries',
        'regions' => 'location/regions',
        'cities' => 'location/cities',
        'postalcodes' => 'location/postalcodes',
        'suggest_cities' => 'location/suggest/cities',
        'tariff' => 'calculator/tariff',
        'tarifflist' => 'calculator/tarifflist',
        'addorder' => 'orders',
        'info' => 'orders/{uuid}',
        'info_v2' => 'orders', // {cdek_number}
        'info_v3' => 'orders', // {im_number} // по коду в ис магазина
        'infocourier' => 'intakes/{uuid}', // https://api-docs.cdek.ru/29948360.html
    ];

    // https://api-docs.cdek.ru/63345430.html

    public $code = '';
    public $codes = [
        '200' => 'Ok',
        '204' => 'NoData',

        400 => 'Bad request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not found',
        500 => 'Internal server error',
        502 => 'Bad gateway',
        503 => 'Service unavailable',
    ];

    public $by_webhook = false;

    public $codeText = '';
    public $res = false;

    public $method_post = false;
    public $post_type = 'json';
    
    public $tosend = true;
    public $answer=null;
    public $log = false;
    public $errorlog = false;
    public $testSendDbg = false;

    public $settings = [];
    

    public function __construct(){
        $this -> initData();

        // add_action('template_redirect', [$this, 'add_actions'], 10, 3 ); // ?
        // add_action('amoCreateContact', [$this, 'createContact'], 10, 2, 3);
        // add_action('amoAddDealReferal', [$this, 'addDealReferal'], 10, 3);
        // add_action('amoAddDeal', [$this, 'addDeal'], 10, 2);
    }


    public function initData(){
        $lhc_setting_name = 'lhc_setting';
        $lhc_settings = get_option( $lhc_setting_name );
        $this->settings = $lhc_settings;
        
        $this->istest = !!$this->settings['cdek_istest'];
        $this->log = !!$this->settings['cdek_log'];
        $this->errorlog = !!$this->settings['cdek_errorlog'];
        $this->testSendDbg = !!$this->settings['cdek_debug'];
        $this->tosend = !!$this->settings['cdek_tosend'];
        
        // public $log = false;
        // public $errorlog = false;
        // public $testSendDbg = false;
    
        // $this->istest = false;
        // $this->by_webhook = false;
        // $this->tosend = false;

        // $this->log = true;
        // $this->errorlog = true;

        if(!$this->istest){
            if( CDEK_API_URL){
                $this->url = CDEK_API_URL;
            }
            if(CDEK_LOGIN_KEY){
                $this->key = CDEK_LOGIN_KEY;
            }
            if(CDEK_PASS_KEY){
                $this->pass = CDEK_PASS_KEY;
            }
        }

        // addMess($this->istest?'test':'not test', 'istest');
        if($this->istest){
            $this->url = CDEK_API_URL_TEST;
            $this->key = CDEK_LOGIN_KEY_TEST;
            $this->pass = CDEK_PASS_KEY_TEST;
        }

        if(YANDEX_MAP_APIKEY){
            $this->mapkey = YANDEX_MAP_APIKEY;
        }
        if(YANDEX_MAP_APIKEY_SUGGEST){
            $this->mapkeysugg = YANDEX_MAP_APIKEY_SUGGEST;
        }

        // if(AMO_CLIENT_LOGIN){
        //     $this->url = 'https://' . AMO_CLIENT_LOGIN . '.amocrm.ru/api/v4/';
        // }
        // if(AMO_CLIENT_LOGIN){
        //     $this->url = 'https://' . AMO_CLIENT_LOGIN . '.amocrm.ru/api/v4/';
        // }

    //     $this->objects ['account'] = 'account';
    //     $this->objects ['contacts'] = 'contacts';
    //     $this->objects ['leads'] = 'leads';

    //     $this->objectsInfo ['account'] = 'аккаут';
    //     $this->objectsInfo ['contacts'] = 'контакты';
    //     $this->objectsInfo ['leads'] = 'сделки';
    }


    // ================================
    // bilding query data


    public function bindData($task, $data, $post=0){
        // partner-candidate
        $this->method_post;
        if($task == 'partner-candidate'){
            $this->method_post = true;
            $data = $this->amo_add_contact_obj($data);
        }
        if($task == 'pymentWithFeeReferer'){
            $this->method_post = true;
            $data = $this->amo_add_lead_partner_obj($data);
        }
        if($task == 'pyment'){
            $this->method_post = true;
            $data = $this->amo_add_lead_obj($data);
        }
        if($task == 'ofices'){
            $this->method_post = 0;
            $query = $this->cdek_get_ofices($data);
        }
        if($task == 'regions'){
            $this->method_post = 0;
            $query = $this->cdek_get_ofices($data);
        }
        if($task == 'tariff'){
            $this->method_post = 1;
            $this->post_type = 'json';
            $data = $this->cdek_get_tariff($data);
        }
        if($task == 'addorder'){
            $this->method_post = 1;
            $this->post_type = 'json';
            $data = $this->cdek_add_order($data);
        }
        return $data;
        // return [$data];
    }

    
    public function cdek_get_ofices($_data){

        $data = [];
        $city_code = 0;
        if(isset($_data['city_code'])) $city_code = (int)$_data['city_code'];
        if($city_code) $data['city_code'] = (int)$_data['city_code'];

        // if($this->log) wsd_addlog($this->apiname, 'dataObject', 
        //     ['stage'=>'end_bind', 'data'=> $data],
        //     'info');
    
        return $data;
    }

    
    public function cdek_get_tariff($_data){

        // $data = [];
        // $city_code = 0;
        // if(isset($_data['city_code'])) $city_code = (int)$_data['city_code'];
        // if($city_code) $data['city_code'] = (int)$_data['city_code'];

        // if($this->log) wsd_addlog($this->apiname, 'dataObject', 
        //     ['stage'=>'end_bind', 'data'=> $data],
        //     'info');

        $get_option_key = 'woocommerce_'.'official_cdek'.'_settings';
        $settings = get_option( $get_option_key, null );

        $code = 44;
        $tarif = $settings['tariff_list'][0];
        $from = json_decode($settings['pvz_code'],1);

        $from = [
            'code'=>$code,
            'country_code'=>$from['country'],
            'postal_code'=>$from['postal'],
            'city'=>$from['city'],
            'address'=>$from['address'],
        ];

        $location = $_data['location'];
        $to = [
            'code'=>$location['city_code'],
            'country_code'=>$location['country_code'],
            'postal_code'=>$location['postal_code'],
            'city'=>$location['city'],
            'address'=>$location['address_full'],
        ];

        $shipment_point = $from['address'];
        $delivery_point = $_data['code'];

        // $from=$to;
        // $tarif = 2536;
        // $tarif = 136;
        $code = "MSK151";
        $code = 44;
        $data = [
            // 'additional_order_types'=> [ 11 ],
            "type" => "1", // 1 - "интернет-магазин" 2 - "доставка" 
            // "date" => date('Y-m-d\TH:i:s+Z'),
            "currency" => "1", // rub 1
            "tariff_code" => $tarif, // 11
            // "from_location" => [
            //     // "code" => 270
            //     "code" => $code
            // ],
            // "to_location" => [
            //     // "code" => 44
            //     "code" => $code
            // ],
            
            // "from_location" => $from,
            // "to_location" => $to,

            // "services" => [
            //     [
            //         "code" => "CARTON_BOX_XS",
            //         "parameter" => "2"
            //     ]
            // ],
            "packages" => [
                [
                    "weight" => $settings['product_weight_default']*1000,
                    "length" => $settings['product_length_default'],
                    "width" => $settings['product_width_default'],
                    "height" => $settings['product_height_default'],
                ]
            ]
        ];

        $from_ofices = [1136];
        $to_ofices = [1136];
        if( in_array($tarif, $from_ofices) ){
            $data['shipment_point'] = $shipment_point; // Код ПВЗ СДЭК, на который будет производиться самостоятельный привоз клиентом
        }else{
            $data['from_location'] = $from;
        }
        if( in_array($tarif, $to_ofices) ){
            $data['delivery_point'] = $delivery_point; // на который будет доставлена посылка
        }else{
            $data['to_location'] = $to;
        }
        return $data;
        '
        {
            "type": "2",
            "date": "2020-11-03T11:49:32+0700",
            "currency": "1",
            "tariff_code": "11",
            "from_location": {
                "code": 270
            },
            "to_location": {
                "code": 44
            },
            "services": [
                {
                    "code": "CARTON_BOX_XS",
                    "parameter": "2"
                }
            ],
            "packages": [
                {
                    "height": 10,
                    "length": 10,
                    "weight": 4000,
                    "width": 10
                }
            ]
        }
        ';
    }

    
    // создание заказа, данные
    // https://api-docs.cdek.ru/29923926.html
    public function cdek_add_order($_data){

        $get_option_key = 'woocommerce_'.'official_cdek'.'_settings';
        $settings = get_option( $get_option_key, null );
        $pid = $_data['pid'];

        $code = 44; // sender city code
        $tarif = $settings['tariff_list'][0];
        $from = json_decode($settings['pvz_code'],1);
        // $bill_id = get_post_meta( $pid, 'qr-bill-id', 1 );

        $fields = [
            'qr-bill-id',
            'qr-fio',
            'qr-phone',
            'qr-adres',
            'qr-pvz',
            'qr-query',
        ];
        $vals = [];
        foreach($fields as $k=>$v){
            $vals[$v] = get_post_meta( $pid, $v, 1 );
        }
        if($this->log) {
            addMess($_data, 'create order, $_data');
            addMess($vals, 'create order, page data');
        }

        $bill_id = $vals['qr-bill-id'];
        $bill = false;
        // $bill = wsd_get_bill_post($post_id); // wsd_getBillByPostId
        if($bill_id) $bill = wsd_get_bill($bill_id); // wsd_getBillByPostId
        if(!$bill){
            $this->stop_send = 1;
            return [];
        }
        $uid = $bill['uid'];
        $tpl_wk = 'PID_p_UID_u_BILL_b_';
        $r_wk = ['_p_'=>$pid, '_u_'=>$uid, '_b_'=>$bill_id, ];
        $cost = 1000;
        $items = [
            [
                'name' => $bill['goods'],
                'ware_key' => strtr($tpl_wk, $r_wk),
                'payment' => ['value'=>0],
                'cost' => $cost, // Объявленная стоимость товара - для страховки
                // 'weight'=>$code, // Вес (за единицу товара, в граммах), вес округляется в большую сторону
                "weight" => $settings['product_weight_default']*1000,
                'amount' => 1, // Количество единиц товара (в штуках) 
            ],
        ];

        $from = [
            'code'=>$code,
            'country_code'=>$from['country'],
            'postal_code'=>$from['postal'],
            'city'=>$from['city'],
            'address'=>$from['address'],
        ];

        // $location = $_data['ofice']['location'];
        $location = $vals['qr-pvz']['location'];
        // $reciver = $_data['reciver'];
        $recipient = [
            'name'=> $vals['qr-fio'],
            'phones'=> [['number'=>$vals['qr-phone']]],
        ];
        $to = [
            'code'=>$location['city_code'],
            'country_code'=>$location['country_code'],
            'postal_code'=>$location['postal_code'],
            'city'=>$location['city'],
            'address'=>$location['address_full'],
        ];

        $shipment_point = $from['address'];
        $delivery_point = $vals['qr-pvz']['code'];
        

        $data = [];
        $data['type'] = '1';
        // $data['number'] = '';
        $data['comment'] = $bill['goods'];
        $data['tariff_code'] = $tarif;

        $from_ofices = [136];
        $to_ofices = [136];
        if( in_array($tarif, $from_ofices) ){
            $data['shipment_point'] = $shipment_point; // Код ПВЗ СДЭК, на который будет производиться самостоятельный привоз клиентом
        }else{
            $data['from_location'] = $from;
        }
        if( in_array($tarif, $to_ofices) ){
            $data['delivery_point'] = $delivery_point; // на который будет доставлена посылка
        }else{
            $data['to_location'] = $to;
        }
        $data['recipient'] = $recipient;
        $data['packages'] = [
            [
                "number" => $pid,
                "items" => $items,
                "weight" => $settings['product_weight_default']*1000,
                "length" => $settings['product_length_default'],
                "width" => $settings['product_width_default'],
                "height" => $settings['product_height_default'],
            ]
        ];
        // $data[''] = '';
        // $data[''] = '';
        // $data[''] = '';
        if(10) return $data;
        $json_exem = '{
	"number" : "ddOererre7450813980068",
	"comment" : "Новый заказ",
	"delivery_recipient_cost" : {
		"value" : 50
	},
	"delivery_recipient_cost_adv" : [ {
		"sum" : 3000,
		"threshold" : 200
	} ],
	"from_location" : {
		"code" : "44",
		"fias_guid" : "",
		"postal_code" : "",
		"longitude" : "",
		"latitude" : "",
		"country_code" : "",
		"region" : "",
		"sub_region" : "",
		"city" : "Москва",
		"kladr_code" : "",
		"address" : "пр. Ленинградский, д.4"
	},
	"to_location" : {
		"code" : "270",
		"fias_guid" : "",
		"postal_code" : "",
		"longitude" : "",
		"latitude" : "",
		"country_code" : "",
		"region" : "",
		"sub_region" : "",
		"city" : "Новосибирск",
		"kladr_code" : "",
		"address" : "ул. Блюхера, 32"
	},
	"packages" : [ {
		"number" : "bar-001",
		"comment" : "Упаковка",
		"height" : 10,
		"items" : [ {
			"ware_key" : "00055",
			"payment" : {
				"value" : 3000
			},
			"name" : "Товар",
			"cost" : 300,
			"amount" : 2,
			"weight" : 700,
			"url" : "www.item.ru"
		} ],
	"length" : 10,
	"weight" : 4000,
	"width" : 10
	} ],
	"recipient" : {
		"name" : "Иванов Иван",
		"phones" : [ {
		"number" : "+79134637228"
	} ]
	},
	"sender" : {
		"name" : "Петров Петр"
	},
	"services" : [ {
		"code" : "SECURE_PACKAGE_A2"
	} ],
	"tariff_code" : 139
}';
        $data = json_decode($json_exem, 1);
        return $data;
    }

    
    public function cdek_get_url($_data){

        $data = [];
        $city_code = 0;
        if(isset($_data['city_code'])) $city_code = (int)$_data['city_code'];
        if($city_code) $data['city_code'] = (int)$_data['city_code'];

        // if($this->log) wsd_addlog($this->apiname, 'dataObject', 
        //     ['stage'=>'end_bind', 'data'=> $data],
        //     'info');
    
        return $data;
    }


    // / bilding query data
    // ================================
    // bilding query url


    public function getUrl($task = ''){
        if( array_key_exists($task, $this->urls) ){
            $this->path = $this->urls[$task];
            // $this->url = $path;
            $this->query = $this->url . $this->path;
        }else{
            $this->query = $this->url . $task;
        }
        // if( array_key_exists($task, $this->shluseUrls) ) $this->query = $this->shluseUrls[$task];
        // if(! $this->by_webhook ) return $this->query;
        $url = '';
        // if( array_key_exists($task, $this->shluseUrls) ) $url = $this->shluseUrls[$task];
        // $this->url = $url;


        // $this->query = $this->bindUrl($task, $data);
        return $url;
    }


    public function bindUrl($task, $data, $post=0){
        $query = [];
        // partner-candidate
        $this->method_post;
        if($task == 'token'){
            $this->method_post = 2;
            $this->token = 0;
            $data = [];
            // $this->query = $this->cdek_get_token($data);
            // $query = ['parameters'=>''];
        }
        if($task == 'ofices'){
            $this->method_post = false;
            
            // $data['city_code'] = 16584;
            $query += $this->cdek_get_ofices($data);
        }
        if($task == 'regions'){
            $this->method_post = 0;
            $query = $data;
        }
        if($task == 'cities'){
            $this->method_post = 0;
            $query = $data;
        }
        if($task == 'addorder'){
            $this->method_post = 1;
            $this->post_type = 'json';
            // $query = $data;
        }
        if($task == 'info'){
            $this->method_post = 0;
            $pid = $data['pid'];
            $uuid = get_post_meta($pid, 'qr-order-uuid', 1);
            $query = [];
            $this->query = strtr($this->query, ['{uuid}'=>$uuid]);
        }
        // 'info_v2' => 'orders', // {cdek_number} 
        // 'info_v3' => 'orders', // {im_number} 
        if($task == 'info_v2'){
            $this->method_post = 0;
            $pid = $data['pid'];
            $uuid = get_post_meta($pid, 'qr-order-uuid', 1);
            $cdek_number = $uuid;
            $query = ['cdek_number'=>$cdek_number];
        }
        if($task == 'info_v3'){
            $this->method_post = 0;
            $pid = $data['pid'];
            $uuid = get_post_meta($pid, 'qr-order-uuid', 1);
            $im_number = $uuid;
            $query = ['im_number'=>$im_number]; // вод в ис магазина
        }

        if($query) $this->query = $this->query . '?' . http_build_query($query);
        return $this->query;
    }
    

    // / bilding query url
    // ================================
    // login token functionality
    

    public function cdek_get_token($_data=[]){
        $data = [];
        $data['grant_type'] = 'client_credentials';
        $data['client_id'] = $this->key;
        $data['client_secret'] = $this->pass;
        return $data;
    }


    public function getOpts(){
        $fname = __DIR__.'/cdek_opts.json';
        $opts = json_decode(file_get_contents($fname),1);
        if(!$opts) $opts = [];
        return $opts;
    }


    public function setOpts($data){
        $fname = __DIR__.'/cdek_opts.json';
        $opts = json_encode($data,1);
        $f = fopen($fname, 'w');
        // addMess(file_exists($fname),'setOpts exists');
        // addMess($fname,'setOpts $fname');
        // addMess(var_export($f,1),'setOpts fopen');
        if($f){
            fwrite($f, $opts);
            fclose($f);
        }
    }


    public function getToken(){
        $opts = $this->getOpts();
        // addMess($opts,'getToken getOpts');
        // addMess($opts['istest'] != $this->istest,'$opts[\'istest\'] != $this->istest');
        // if(!isset($opts['access_token'])) addMess($opts,'getToken getOpts');
        if( !$opts || $opts['expire'] <= time() 
            || !isset($opts['istest']) || $opts['istest'] != $this->istest ){
                addSys('get new Token');
            $this->method_post = 2;
            $this->post_type = 'form';
            $this->token = 0;
            $this->task = 'token';
            $this->getUrl('token');
            $this->data = $this->cdek_get_token();
            // $this->bindUrl('token');
            $res = $this->_send();
                // addMess($this->query,'get token query');
                // addMess($this->headers,'get token headers');
                // addMess($this->data,'get token data');
                // addMess($res,'get token res');
            if(!$res){
            }
            if($res && isset($res['expires_in']) ){
                $exp = time()+(int)$res['expires_in'];
                $res['expire'] = $exp;
                $res['istest'] = $this->istest;
                $opts = $res;
                // addMess($opts,'getToken setOpts');
                $this->setOpts($opts);
            }
        }
        $token = '';
        if(isset($opts['access_token'])) $token = $opts['access_token'];
        $this->token = $token;
        return $token;
    }

    
    // / login token functionality
    // ================================
    // send interface


    public function send($task, $data, $ispost = false, $post_type = 'json', $istest = true){
        $this->stop_send = 0;
        // $this->istest = $istest;
        $this->getToken();
        // return ['is debug stop'];
        $this->answer = null;
        $this->method_post = false;
        if($ispost )$this->method_post = true;
        $this->post_type = $post_type;
        $this->query = ''; // url
        if($this->log) addSys( $this->apiname . ' send');
        $this->task = $task;
        $this->getUrl($task);
        $this->bindUrl($task, $data);
        if(!$this->query) return false;
        $this->data = $data;
        $this->data = $this->bindData($task, $data);
        if($this->log) addMess($this->data, 'send data');
        // if($this->log) wsd_addlog($this->apiname, 'beforeSend', 
        //     ['$task'=>$task, '$data'=> $data, 'object'=>$this->data], 'info');
        $res = -1;
        if(!$this->stop_send && $this->tosend) $res = $this->_send();
        // addMess($res, $task . ' send res');
        $this->afterSend($task, $data, $ispost);
        return $res;
    }


    public function _send(){
        $res = '';
        // curl
        // $data = [];
        // $data ['amo url'] = $this->url;
        // $data ['amo task'] = $this->task;
        // $data ['amo query'] = $this->query;
        // $data ['amo method_post'] = $this->method_post?'post':'get';
        // $data ['amo data'] = $this->data;
        // if($this->log) addMess($data, 'amo _send');
        $res = $this->curl();
        if($res){
            $res = json_decode($res, 1);
        }
        if($this->log) addMess($res, $this->apiname . ' answer');
        // if($this->log) addMess($this->res, $this->apiname . ' answer');
        // if($this->log) wsd_addlog('amo', 'send', 
        //     ['data'=>$data], 'info');
        // if($this->log) wsd_addlog('amo', 'answer', 
        //     ['code'=>$this->code, 'answer'=> json_decode($this->res,1)], $status = 'info');
        return $res;
    }


    public function afterSend($task, $data, $post){
    }

    /**
     * ================================================ curl
    */


    public $headers = [];
    public function curl(){
        if(!$this->url) return;
        if(!$this->query) return;

        // $params=['name'=>'John', 'surname'=>'Doe', 'age'=>36];
        // $params = $this->data;

        // https://www.amocrm.ru/developers/content/oauth/step-by-step
        $access_token = 'xxxx';
        /** Формируем заголовки */
        $this->headers = [
            // 'Authorization: Bearer ' . CDEK_AUTHORIZATION_TOKEN
            // 'Authorization: Bearer ' . $this->token,
        ];
        if($this->token) $this->headers[] = 'Authorization: Bearer ' . $this->token;
        if($this->method_post == 1) $this->headers['Content-Type'] = 'Content-Type: application/json';
        if($this->method_post == 2) $this->headers['Content-Type'] = 'Content-Type: application/x-www-form-urlencoded';

        if($this->method_post){
            if($this->post_type == 'json') $this->headers['Content-Type'] = 'Content-Type: application/json';
            if($this->post_type == 'form') $this->headers['Content-Type'] = 'Content-Type: application/x-www-form-urlencoded';
        }

        $headers = array_values($this->headers);
        if($this->task=='token')$headers = ['Content-Type'=>'application/x-www-form-urlencoded'];
        $defaults = array(
        //     CURLOPT_URL => $this->url,
            CURLOPT_POST => !!$this->method_post,
            // CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_RETURNTRANSFER => true,   // return web page
            CURLOPT_USERAGENT => 'amoCRM-oAuth-client/1.0',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADER         => false,  // don't return headers
            CURLOPT_SSL_VERIFYPEER => 1,
            CURLOPT_SSL_VERIFYHOST => 2,
            // CURLOPT_FOLLOWLOCATION => true,   // follow redirects
            // CURLOPT_MAXREDIRS      => 10,     // stop after 10 redirects
            // CURLOPT_ENCODING       => "",     // handle compressed
            // // CURLOPT_USERAGENT      => "test", // name of client
            // CURLOPT_AUTOREFERER    => true,   // set referrer on redirect
            // CURLOPT_CONNECTTIMEOUT => 120,    // time-out on connect
            // CURLOPT_TIMEOUT        => 120,    // time-out on response
        );
        $post_data='';
        if($this->method_post){
            if(!$this->data)return;
            $post_data = ($this->data);
            if($this->method_post == 1) $post_data = json_encode($this->data);
            if($this->post_type == 'json') $post_data = json_encode($this->data);
            $defaults[CURLOPT_POSTFIELDS] = $post_data;
        }
        
        // $ch = curl_init();
        // curl_setopt_array($ch, ($options + $defaults));

        $ch = curl_init($this->query);
        

        // $fp = fopen("example_homepage.txt", "w");
        // curl_setopt($ch, CURLOPT_FILE, $fp);
        
        // curl_setopt($ch, CURLOPT_HEADER, 0);

        // curl_setopt($ch, CURLOPT_POST, true);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, $this->data);

        curl_setopt_array($ch, $defaults);

        $res = curl_exec($ch);
        $this->code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $code = $this->code;

        if($this->testSendDbg){
            $dt = [
                'istest' => $this->istest?'test':'not test',
                'task' => b($this->task),
                'code' => b($code),
                'query' => $this->query,
                'method' => $this->method_post?'POST':'GET',
                'headers' => $headers,
                'data' => $post_data,
            ];
            // addMess($this->istest?'test':'not test', 'istest');
            // addMess(b($this->task), 'task');
            // addMess(b($code), '$code');
            // addMess($this->query, 'query');
            // addMess($this->method_post?'POST':'GET', 'method');
            // addMess($this->headers, 'headers');
            // addMess($post_data, 'data');
            addMess($dt, 'request');
            addMess($res, 'responce');
            addMess(json_decode($res,1), 'responce');
        }

        if( array_key_exists($this->code, $this->codes))
            $this->codeText = $this->codes[$this->code];
        else $this->codeText = '';

        if(curl_error($ch)) {
            if($this->log) addMess(curl_error($ch), 'curl_error', 'warning');
            // fwrite($fp, curl_error($ch));
        }

        curl_close($ch);
        // fclose($fp);

        $code = (int)$code;
        $errors = [
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
        ];
        if ($code < 200 || $code > 204) {
            // throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
            $error = isset($errors[$code]) ? $errors[$code] : 'Undefined error';
            if($this->errorlog){
                addMess(b('ERROR'), 'error');
                addMess($this->query, 'query');
                addMess($code .' -- '.$error, 'cdek');
                addMess($defaults, 'curl opts cdek');
                addMess(json_decode($res,1), '$res');
                $dbg = debug_backtrace(2,10);
                addMess($dbg, 'err backtrace cdekTEST');
            }
        }
        $this->res = $res;
        $this->answer = $res;
        
        return $res;
    }


    /**
     * ================================================ interface
    */
    public function getOfices( $country_code = '' , $region_code = '' , $city_code = '' ){
        $country_code = strtoupper($country_code);
        // if( !$partner ) return false;
        // $user = get_userdata($uid);

        // $send_data = [];
        $data = [];
        if($country_code) $data['country_code'] = $country_code;
        if($region_code) $data['region_code'] = $region_code;
        if($city_code) $data['city_code'] = $city_code;
        
        // $this->contact_src = $source;
        return $this->send('ofices', $data, $ispost = false); // event-path-name post-data
        // return $this->_getOfices($data);
    }

    /**
     * ================================================
    */
    public function _getOfices( $data = [] ){ // 
        // if( !$data ) return false;
        // user_id 306169

        // $data = [];
        // $data['name'] = $_data['goods'];
        // $data['first_name'] = $_data['goods'];
        // $data['last_name'] = $_data['goods'];

        // // email
        // $fields[] = [ "field_id" => 44243, "values" => [ [ "value"=> $user_id ] ] ];    
        
        // $data['custom_fields_values'] = [];
        // $field = [];
        // $field["field_id"] = 306169;
        // $field["values"] = [ ["value"=> $user_id] ];
        // $data['custom_fields_values'][] = $field;

        // partner-candidate
        return $this->send('ofices', $data, $ispost = false); // event-path-name post-data
    }


    public function getCountries(){
        return $this->send('countries', $data=[], $ispost = false); // event-path-name post-data
    }


    public function getRegions($country_code='RU'){
        $country_code = strtoupper($country_code);
        return $this->send('regions', $data=['country_codes'=>$country_code ], $ispost = false); // event-path-name post-data
    }


    public function getCities($region_code=81){
        return $this->send('cities', $data=['region_code'=>$region_code ], $ispost = false); // event-path-name post-data
    }


    public function getTarif($data=[], $istest = true){
        // $data=['region_code'=>$region_code ];
        return $this->send('tariff', $data, $ispost = true, $post_type = 'json', $istest); // event-path-name post-data
    }


    public function addOrder($data=[]){
        // $data=['region_code'=>$region_code ];
        return $this->send('addorder', $data, $ispost = true, $post_type = 'json'); // event-path-name post-data
    }


    public function getOrderInfo($data=[]){
        // $data=['region_code'=>$region_code ];
        return $this->send('info', $data, $ispost = 0, $post_type = 'json'); // event-path-name post-data
    }

    static function init(){
        global $cdekCrmClass;
        if( !$cdekCrmClass ){
            $cdekCrmClass = new CdekCrmClass();
        }
    }
}


/**
 * ================================================ init cdek api
*/


add_action( 'init', ['CdekCrmClass', 'init']);


/**
 * ================================================ functional access interfaces
*/


function lhc_cdek_get_ofices($country_code = '' , $region_code = '' , $city_code = ''){
    global $cdekCrmClass;
    return $cdekCrmClass->getOfices($country_code, $region_code, $city_code);
}


function lhc_cdek_get_countries(){
    $file = ABSPATH . 'wp-content/plugins/cdekdelivery-lhc/vendor/giggsey/locale/data/ru.php';
    $countries = include $file;
    return $countries;
}


function lhc_cdek_get_regions($country_code='RU'){
    global $cdekCrmClass;
    return $cdekCrmClass->getRegions($country_code);
}


function lhc_cdek_get_cities($region_code=81){
    global $cdekCrmClass;
    return $cdekCrmClass->getCities($region_code);
}


function lhc_cdek_get_tarif($data=[], $istest = true){
    global $cdekCrmClass;
    // $cdekCrmClass->testSendDbg = true;
    return $cdekCrmClass->getTarif($data, $istest);
}


function lhc_cdek_add_order($data=[]){
    global $cdekCrmClass;
    // $cdekCrmClass->testSendDbg = true;
    return $cdekCrmClass->addOrder($data);
}


function lhc_cdek_get_order_info($data=[]){
    global $cdekCrmClass;
    // $cdekCrmClass->testSendDbg = true;
    return $cdekCrmClass->getOrderInfo($data);
}

// / END

