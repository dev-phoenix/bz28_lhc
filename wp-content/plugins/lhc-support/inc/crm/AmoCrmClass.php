<?php
/**
 * AmoCrmClass.php
 * 18:40
*/


class AmoCrmClass{
    // event-path-names
    public $shluseUrls = [
        // 'partner-candidate'=>'http://xn----ltbdbacb3bdujm6o.xn--p1ai/account/',
        'partner-candidate'=>'https://' . AMO_CLIENT_LOGIN . '.amocrm.ru/api/v4/contacts',
        'pymentWithFeeReferer'=>'https://' . AMO_CLIENT_LOGIN . '.amocrm.ru/api/v4/leads',
        'pyment'=>'https://' . AMO_CLIENT_LOGIN . '.amocrm.ru/api/v4/leads',
    ];
    public $task = '';
    public $url = '';
    public $path = '';
    public $query = '';
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
    public $codeText = '';
    public $res = false;

    public $by_webhook = false;

    public $objectsInfo = [];
    public $objects = [];

    public $method_post = false;
    public $contact_src = 'partner';
    public $tosend = true;
    public $answer=null;
    public $log = false;
    public $errorlog = false;



    public function __construct(){
        $this->by_webhook = false;
        // $this->tosend = false;
        // $this->log = true;
        // $this->errorlog = true;
        if(AMO_CLIENT_LOGIN){
            $this->url = 'https://' . AMO_CLIENT_LOGIN . '.amocrm.ru/api/v4/';
        }
        $this -> initData();

        // add_action('template_redirect', [$this, 'add_actions'], 10, 3 ); // ?
        add_action('amoCreateContact', [$this, 'createContact'], 10, 2, 3);
        add_action('amoAddDealReferal', [$this, 'addDealReferal'], 10, 3);
        add_action('amoAddDeal', [$this, 'addDeal'], 10, 2);
    }


    public function initData(){
        $this->objects ['account'] = 'account';
        $this->objects ['contacts'] = 'contacts';
        $this->objects ['leads'] = 'leads';

        $this->objectsInfo ['account'] = 'аккаут';
        $this->objectsInfo ['contacts'] = 'контакты';
        $this->objectsInfo ['leads'] = 'сделки';
    }


    // init sysem event
    public function initEvent(){
        // addMess(__DIR__);
    }

    // ?
    public function add_actions($name=''){
        $tasks = [];
        switch($name){
            case 'amoCreateContact': add_action($name, [$this, 'createContact'], $tasks); break;
            case 'amoAddDealReferal': add_action($name, [$this, 'addDealReferal'], $tasks); break;
            case 'amoAddDeal': add_action($name, [$this, 'addDeal'], $tasks); break;
        }
    }


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
        return [$data];
    }


    public function upUrl(&$url){
        $r=[];
        $r['xn----ltbdbacb3bdujm6o.xn--p1ai'] = 'история-жизни.рф';
        $url = strtr($url, $r);
        return $url;
    }


    public function amo_add_lead_partner_obj($_data){
        $data_type = 'bill';
        if(array_key_exists('data_type', $_data)) $data_type = $_data['data_type'];

        // for build in difrents data types
        if($data_type == 'bill'){

        }
    
        // $_data = ['sum'=>1000];
    
        $defContact = AMO_DEFOULT_CONTACT;
    
        $uid = 0;
        $pid = 0;
        // $uid_phone = '';
        // $partner_percent = 15;
        // $partner_percent = 0;
        // $uid = $_data['user_id'];
        if(array_key_exists('user_id', $_data))$uid = $_data['user_id'];
        if(array_key_exists('uid', $_data))$uid = $_data['uid'];
        $pid = $_data['pid'];
        $rid = $_data['rid'];
        if(!$uid) return;
        if(!$pid) return;

        $data = $this->amo_add_lead_obj($_data);
        if(!$data) return;
    
        // $uid = 10;
        // $user = get_userdata($uid);
        // $user_id = $user->ID;
        // $uid_email = $user->user_email;
        // $uid_phone = get_user_meta($uid, 'phone', 1);

        $event_name = $_data['goods'];
        $price = (int)$_data['sum'];

        $tarif = get_post_meta($pid, 'tarif', 1);
        $page_url = get_permalink($pid); // $post_id


        if( $this->kinar('amo_contact_id', $_data) ) $defContact = (int)$_data['amo_contact_id'];
    
        $stage_by_tarif = [];
        $stage_by_tarif['econ'] = 71028670; // 70764670;
        $stage_by_tarif['std'] =  71028674;
        $stage_by_tarif['max'] =  71028678;
        $stage_by_tarif['hero'] = 71028682;
        $stage_by_tarif['unknown_p'] = 71025794; // Партнерские продажи
        $stage_by_tarif['unknown_f'] = 70764666; // Прямые продажи
        $stage_by_tarif['success'] = 142;
        $stage_by_tarif['closed'] = 143;
    
        $pype_by_type = [];
        $pype_by_type['forward'] = 8742210;
        $pype_by_type['partnership'] = 8780478;

        // $event_name = 'tarif: econ; partner: ' . $pype_by_type['partnership'];
        // $event_name = 'tarif: econ; pipe: ' . $pype_by_type['partnership'];
    
        // $data['status_id'] = $stage_by_tarif['max']; // ID статуса hero
        unset( $data['status_id'] );
        if( $this->kinar($tarif, $stage_by_tarif) ) $data['status_id'] = $stage_by_tarif[$tarif]; // ID статуса hero
        $data['pipeline_id'] = $pype_by_type['partnership']; // ID воронки
        // $data['name'] = 'tarif: std';
        // $data['name'] = $data['pipeline_id'] .': '.$event_name;
        // $data['price'] = 3000;
        $data['_embedded']['contacts'][0]['id'] = $defContact;
    
        // unset($data['status_id']);
    
        return $data;
    }


    // is key in array
    public function kinar( $k, $arr){ return array_key_exists($k, $arr); }

    
    public function amo_add_lead_obj($_data){
        $data_type = 'bill';
        if(array_key_exists('data_type', $_data)) $data_type = $_data['data_type'];

        // for build in difrents data types
        if($data_type == 'bill'){

        }

        if($this->log) wsd_addlog('amo', 'dataObject', 
            ['stage'=>'start_bind', 'data'=> $_data],
            'info');
    
        // $_data = ['sum'=>1000];
    
        $defContact = AMO_DEFOULT_CONTACT;
    
        $uid = 0;
        $pid = 0;
        $uid_phone = '';
        $partner_percent = 15;
        $partner_percent = 0;
        // $uid = $_data['user_id'];
        if(array_key_exists('user_id', $_data))$uid = $_data['user_id'];
        if(array_key_exists('uid', $_data))$uid = $_data['uid'];
        $pid = $_data['pid'];

        if($this->log) wsd_addlog('amo', 'dataObject', 
            ['stage'=>'start_bind', 'data'=> ['$uid'=>$uid, '$pid'=>$pid]],
            'info');

        if(!$uid) return 1;
        if(!$pid) return 2;
    
        // $uid = 10;
        $user = get_userdata($uid);
        $user_id = $user->ID;
        $uid_email = $user->user_email;
        $uid_phone = get_user_meta($uid, 'phone', 1);

        $event_name = $_data['goods'];
        $price = (int)$_data['sum'];
        
        // $event_name = 'tarif: econ';
        // $price = 0;
        // $uid_phone = '3333333333';
        // $page_url = '//some/page/url';

        $tarif = get_post_meta($pid, 'tarif', 1);
        $page_url = get_permalink($pid); // $post_id
        $this->upUrl($page_url);
    
        $data = [];
        // $data['email'] = $uid_email;
    
        $data['status_id'] = 71025794; // ID статуса не разобрано Партнерские продажи
        $data['status_id'] = 70764666; // ID статуса не разобрано Прямые продажи
        $data['status_id'] = 142; // ID статуса успешно реализовано
        $data['status_id'] = 143; // ID статуса акрыто и не реализовано
        $data['status_id'] = 70764670; // ID статуса econ
        $data['status_id'] = 70764674; // ID статуса std
        $data['status_id'] = 70764678; // ID статуса max
        $data['status_id'] = 70764682; // ID статуса hero
        
        $data['pipeline_id'] = 8780478; // ID воронки Партнерские продажи
        $data['pipeline_id'] = 8742210; // ID воронки Прямые продажи
    
    
        $stage_by_tarif = [];
        $stage_by_tarif['econ'] = 70764670;
        $stage_by_tarif['std'] = 70764674;
        $stage_by_tarif['max'] = 70764678;
        $stage_by_tarif['hero'] = 70764682;
        $stage_by_tarif['unknown_p'] = 71025794; // Партнерские продажи
        $stage_by_tarif['unknown_f'] = 70764666; // Прямые продажи
        $stage_by_tarif['success'] = 142;
        $stage_by_tarif['closed'] = 143;
    
        $pype_by_type = [];
        $pype_by_type['forward'] = 8742210;
        $pype_by_type['partnership'] = 8780478;
    

        $data = [];
        if( $this->kinar($tarif, $stage_by_tarif) )$data['status_id'] = $stage_by_tarif[$tarif]; // ID статуса hero    
        $data['pipeline_id'] = $pype_by_type['forward']; // ID воронки
    
        $data['name'] = $event_name;
        $data['price'] = $price;
        $data['created_by'] = (int)0; //  I D пользователя, создающий сделку.  0 = созданной роботом.
    
        // $data['custom_fields_values'] = [];
        // $field = [];
        // $field["field_id"] = 294471;
        // $field["values"] = [ ["value"=> $uid_email] ];
        // // $data['custom_fields_values'][] = $field;
        
        // $fields = [];
        // //  page_url lead
        // $fields[] = [ "field_id" => 306195, "values" => [ [ "value"=> $page_url ] ] ];
        // //  user_id contact lead
        // $fields[] = [ "field_id" => 334529, "values" => [ [ "value"=> "$user_id" ] ] ];
        // // phone
        // $fields[] = [ "field_id" => 315649, "values" => [ [ "value"=> $uid_phone ] ] ];
        // // email
        // $fields[] = [ "field_id" => 315655, "values" => [ [ "value"=> $uid_email ] ] ];
        // //  Adress page, url
        // $fields[] = [ "field_id" => 317861, "values" => [ [ "value"=> $page_url ] ] ];
        // $data['custom_fields_values'] = $fields;
    
        $fields = [];
        $fields[] = [ "field_id" => 315649, "values" => [ [ "value"=> "$uid_phone" ] ] ]; // phone
        $fields[] = [ "field_id" => 315655, "values" => [ [ "value"=> "$uid_email" ] ] ]; // email
        $fields[] = [ "field_id" => 317861, "values" => [ [ "value"=> "$page_url" ] ] ]; //  Adress page
        $fields[] = [ "field_id" => 306195, "values" => [ [ "value"=> "$page_url" ] ] ]; // page_url
        $fields[] = [ "field_id" => 366827, "values" => [ [ "value"=> "$pid" ] ] ]; // post_id
        $fields[] = [ "field_id" => 334529, "values" => [ [ "value"=> "$user_id" ] ] ]; // user_id
        $data['custom_fields_values'] = $fields;
        $data['_embedded'] = [];
        $data['tags_to_add'] = [];
        $data['_embedded'] = [];
        $data['_embedded']['contacts'] = [];
        $data['_embedded']['contacts'][0] = [];
        $data['_embedded']['contacts'][0]['id'] = $defContact;

        if($this->log) wsd_addlog('amo', 'dataObject', 
            ['stage'=>'end_bind', 'data'=> $data],
            'info');
    
        return $data;
    }
    
    public function amo_add_contact_obj($_data){
        // addMess($_data);
        $data_type = 'partner';
        if(array_key_exists('data_type', $_data)) $data_type = $_data['data_type'];

        // for build in difrents data types
        if($data_type == 'partner'){

        }
    
        // $_data = ['sum'=>1000];
    
        // $defContact = AMO_DEFOULT_CONTACT;
    
        $uid = 0;
        $uid_phone = '';
        $partner_percent = 15;
        $partner_percent = 0;
        // $uid = $_data['user_id'];
        if(array_key_exists('user_id', $_data))$uid = $_data['user_id'];
        if(array_key_exists('uid', $_data))$uid = $_data['uid'];
        if(array_key_exists('mail', $_data))$uid_email = $_data['mail'];
        if(array_key_exists('phone', $_data))$uid_phone = $_data['phone']; // .'p';
        if(array_key_exists('fee', $_data))$partner_percent = $_data['fee'];
        $user = get_userdata($uid);

        $name = $_data['name'];
        $surname = $_data['surname'];
        $user_id = $uid;
        if($user) $uid_email = $user->user_email;
        if(!$uid_phone) $uid_phone = get_user_meta($uid, 'phone', 1); // .'u';
    
        $data = [];
        // $data['name'] = 'mihail romanov';
        $data['first_name'] = $name;
        $data['last_name'] = $surname;
        $fields = [];
        $fields[] = [ "field_code" => "EMAIL", "values" => [ [ "value"=> $uid_email ] ] ];
        $fields[] = [ "field_code" => "PHONE", "values" => [ [ "value"=> $uid_phone ] ] ];
        $fields[] = [ "field_id" => 306169, "values" => [ [ "value"=> "$user_id" ] ] ];
        $fields[] = [ "field_id" => 318205, "values" => [ [ "value"=> "$partner_percent" ] ] ];
        $data['custom_fields_values'] = $fields;
    
        return $data;
    }


    public function afterSend($task, $data, $post=0){
        if($this->log) wsd_addlog('amo', 'afterSend', 
            ['$task'=>$task, 'code'=> $this->code, 'answer'=> $this->answer,  
            '$data'=> $data, 'object'=>$this->data], 'info');
        if(!$this->answer) return;
        $res_dec = json_decode($this->answer, 1);
        if($this->log) wsd_addlog('amo', 'afterSendAnswer', 
            ['code'=> $this->code, 'answer'=> $res_dec], 'info');
        $data_type = 'partner';
        if(array_key_exists('data_type', $data)) $data_type = $data['data_type'];

        if(array_key_exists('validation-errors', $res_dec)){
            if($this->errorlog) addMess($res_dec,'afterSend amoTest');
            if($this->errorlog) wsd_addlog('amo', 'validation-errors', $res_dec , 'error');
        }
        // addMess($data_type,'$data_type amoTest');
        // addMess($res_dec,'afterSend amoTest');

        $partner_id = 0;
        // for build in difrents data types
        if($data_type == 'partner'){
            $partner_id = $data['id'];
        }
    
        // $_data = ['sum'=>1000];
    
        $defContact = AMO_DEFOULT_CONTACT;
    
        $uid = 0;
        $uid_phone = '';
        $partner_percent = 15;
        $partner_percent = 0;
        // $uid = $_data['user_id'];
        if(array_key_exists('user_id', $_data))$uid = $_data['user_id'];
        if(array_key_exists('uid', $_data))$uid = $_data['uid'];


        if( 1 
        && array_key_exists('_embedded', $res_dec)
        && array_key_exists('leads', $res_dec['_embedded'])
        && count($res_dec['_embedded']['leads'])
        && array_key_exists('id', $res_dec['_embedded']['leads'][0])
        ){
            $id = $res_dec['_embedded']['leads'][0]['id']; // => 2759311
            // addMess('added id: '.$id, $opt_url.' amoTEST');
            if($this->log) wsd_addlog('amo', 'leadCreated', ['lead_id'=>$id, 'task'=>$task], 'success');
        }

        // 
        if( $task == 'partner-candidate'
        && array_key_exists('_embedded', $res_dec)
        && array_key_exists('contacts', $res_dec['_embedded'])
        && count($res_dec['_embedded']['contacts'])
        && array_key_exists('id', $res_dec['_embedded']['contacts'][0])
        ){
            $uid = 0;
            if(array_key_exists('user_id', $data))$uid = $_data['user_id'];
            if(array_key_exists('uid', $data))$uid = $_data['uid'];
            $amo_contact_id = $res_dec['_embedded']['contacts'][0]['id']; // => 2759311
            // addMess('added id: '.$id, $opt_url.' amoTEST');
            if($amo_contact_id) lhc_setPartnerAmoId($partner_id, $amo_contact_id);
            // wsd_addlog($res_dec,'afterSend amoTest');
            if($this->log) wsd_addlog('amo', 'contactCreated', ['contact_id'=>$amo_contact_id, 'task'=>$task], 'success');
        }
    }


    public function afterCreate($task, $data, $post){
        // partner-candidate
        $uid = $data['user_id'];
        $partner = lhc_get_partner_userid($uid);
    }


    public function send($task, $data, $ispost = false){
        $this->answer = null;
        $this->method_post = false;
        if($ispost )$this->method_post = true;
        $this->query = '';
        if($this->log) addSys( 'amo send');
        $this->task = $task;
        $this->getUrl($task);
        if(!$this->query) return false;
        $this->data = $data;
        $this->data = $this->bindData($task, $data);
        if($this->log) addMess($this->data);
        if($this->log) wsd_addlog('amo', 'beforeSend', 
            ['$task'=>$task, '$data'=> $data, 'object'=>$this->data], 'info');
        if($this->tosend)
            $res = $this->_send();
        $this->afterSend($task, $data, $ispost);
        return $res;
    }


    public function getUrl($task = ''){
        if( array_key_exists($task, $this->objects) ){
            $this->path = $this->objects[$task];
            // $this->url = $path;
            $this->query = $this->url . $this->path;
        }else{
            $this->query = $this->url . $task;
        }
        if( array_key_exists($task, $this->shluseUrls) ) $this->query = $this->shluseUrls[$task];
        if(! $this->by_webhook ) return $this->query;
        $url = '';
        if( array_key_exists($task, $this->shluseUrls) ) $url = $this->shluseUrls[$task];
        // $this->url = $url;
        return $url;
    }


    public function _send(){
        $res = '';
        // curl
        $data = [];
        $data ['amo url'] = $this->url;
        $data ['amo task'] = $this->task;
        $data ['amo query'] = $this->query;
        $data ['amo method_post'] = $this->method_post?'post':'get';
        $data ['amo data'] = $this->data;
        if($this->log) addMess($data, 'amo _send');
        $res = $this->curl();
        if($this->log) addMess($this->res, 'amo answer');
        if($this->log) wsd_addlog('amo', 'send', 
            ['data'=>$data], 'info');
        if($this->log) wsd_addlog('amo', 'answer', 
            ['code'=>$this->code, 'answer'=> json_decode($this->res,1)], $status = 'info');
        return $res;
    }


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
            'Authorization: Bearer ' . AMO_AUTHORIZATION_TOKEN
        ];
        if($this->method_post) $this->headers['Content-Type'] = 'application/json';

        $defaults = array(
        //     CURLOPT_URL => $this->url,
            CURLOPT_POST => $this->method_post,
            // CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_RETURNTRANSFER => true,   // return web page
            CURLOPT_USERAGENT => 'amoCRM-oAuth-client/1.0',
            CURLOPT_HTTPHEADER => $this->headers,
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
        if($this->method_post){
            if(!$this->data)return;
            $post_data = json_encode($this->data);
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
            if($this->errorlog) addMess($code .' -- '.$error, 'amoTEST');
            if($this->errorlog) addMess($defaults, 'curl opts amoTEST');
            if($this->errorlog){
                $dbg = debug_backtrace(2,10);
                addMess($dbg, 'err backtrace amoTEST');
            }
        }
        $this->res = $res;
        $this->answer = $res;
        
        return $res;
    }


    public function success(){
        
    }


    public function error(){
        
    }


    // тз4 2.5
    // добавить контакт партнёра в амо
    // user_id wp_partner_row
    public function createContact( $uid, $partner, $source = 'partner' ){
        if( !$partner ) return false;
        $user = get_userdata($uid);

        $send_data = [];
        
        $this->contact_src = $source;
        $this->_createContact($partner);
    }


    // тз4 4.3
    // добавить сделку партнёра: покупка реферала привязана к контакту
    // wp_bill_row update_data wp_partner_row
    public function addDealReferal( $bill, $data, $partner ){
        if( !$bill ) return false;
        $uid = $bill['uid'];
        $ruid = $bill['ruid'];
        $user = get_userdata($uid);
        $referal_user = get_userdata($ruid);

        $send_data = [];

        $this->_addDealReferal($send_data);
    }


    // тз4 5
    // добавить сделку: покупка не привязана к контакту
    // wp_bill_row update_data
    public function addDeal( $bill, $data ){
        if( !$bill ) return false;
        $uid = $bill['uid'];
        $user = get_userdata($uid);

        $send_data = [];

        $this->_addDeal($send_data);
    }

    /**
     * ================================================
    */


    // тз4 2.5
    // добавить контакт партнёра в амо
    public function _createContact( $data = [] ){
        if( !$data ) return false;
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
        $this->send('contacts', $data, $ispost = true); // event-path-name post-data
    }


    // тз4 4.3
    // добавить сделку партнёра: покупка реферала привязана к контакту
    public function _addDealReferal( $data = [] ){
        if( !$data ) return false;
        
        // $data = [];
        // $data['status_id'] = 0; // ID статуса
        // // $data['pipeline_id'] = 0; // ID воронки
        // $data['name'] = $_data['goods'];
        // $data['price'] = (int)$_data['sum'];
        // $data['created_by'] = (int)0;

        // $fields = [];
        // $fields[] = [ "field_id" => 306169, "values" => [ [ "value"=> $user_id ] ] ];
        // // $data['custom_fields_values'] = $fields;
        
        // $data['_embedded'] = [];
        // $data['tags_to_add'] = [];
        // $data['_embedded'] = [];
        // $data['_embedded']['contacts'] = [];
        // $data['_embedded']['contacts'][0] = [];
        // $data['_embedded']['contacts'][0]['id'] = $defContact;

        // pymentWithFeeReferer
        $this->send('leads', $data, $ispost = true); // event-path-name post-data
    }


    // тз4 5
    // добавить сделку: покупка не привязана к контакту
    public function _addDeal( $_data = [] ){
        if( !$_data ) return false;
        // $defContact = AMO_DEFOULT_CONTACT;

        // $uid = $_data['uid'];
        // $data = [];
        // // $data['email'] = $uid_email;
        
        // $data['status_id'] = 0; // ID статуса
        // // $data['pipeline_id'] = 0; // ID воронки
        // $data['name'] = $_data['goods'];
        // $data['price'] = (int)$_data['sum'];
        // $data['created_by'] = (int)0; //  I D пользователя, создающий сделку. При передаче значения 0, сделка будет считаться созданной роботом.
        
        // $data['custom_fields_values'] = [];
        // $field = [];
        // $field["field_id"] = 294471;
        // $field["values"] = [ ["value"=> $uid_email] ];
        // // $data['custom_fields_values'][] = $field;
        
        // $data['_embedded'] = [];
        // $data['tags_to_add'] = [];
        // $data['_embedded'] = [];
        // $data['_embedded']['contacts'] = [];
        // $data['_embedded']['contacts'][0] = [];
        // $data['_embedded']['contacts'][0]['id'] = $defContact;
        // pyment
        $this->send('leads', $data, $ispost = true); // event-path-name post-data
    }
}