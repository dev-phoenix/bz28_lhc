<?php
/**
 * PymentClass.php
 * 2024-10-10 18:40
*/

include_once 'conf/conf.php';
include 'pay/RobocassaClass.php';

class PymentClass {
    public $paysys = '';
    public $sys = '';
    public $systems = [];


    public $uid = '';
    public $bill = '';
    public $summ = '';

    public $db = null;
    public $prefix = null;
    
    public $log = false;
    public $errorlog = false;

    public function __construct($sys = 'robo'){
        global $wpdb;
        $this->db = $wpdb;
        $this->prefix = $this->db->prefix . 'lhc_';
        // wsd_addlog('payment', 'constructor', $_data=['get'=>$_GET, 'post'=>$_POST], $status = 'info', __FILE__, __LINE__);
        $this->initSys($sys);
        // $this->todo($sys);
        $this->actions($sys);
    }


    function actions(){
        add_action('init', [$this, 'postEvents'], 10, 3 );
        add_action('payment_success', [$this, 'payEventSuccess'], 10, 3 );
        add_action('payment_error', [$this, 'payEventError'], 10, 3 );
        add_action('payment_success_user', [$this, 'payEventSuccessUser'], 10, 3 );
        add_action('payment_fail_user', [$this, 'payEventFailUser'], 10, 3 );
        // add_action('template_redirect', [$this, 'postEvents'], 10, 3 );
        // // add_action('init', 'handle_delete_memory_page');
        // add_action('wp_loaded', [$this, 'handle_delete_memory_page'], 10, 3);
        // // add_action('init', 'handle_toggle_status_page');
        // add_action('wp_loaded', [$this, 'handle_toggle_status_page'], 10, 3);
        // // add_action('init', 'handle_toggle_public_status_page', 10, 3);
        // add_action('wp_loaded', [$this, 'handle_toggle_public_status_page'], 10, 3);
        // // add_action('init', 'handle_set_memory_page_password');
        // add_action('wp_loaded', [$this, 'handle_set_memory_page_password'], 10, 3);
        // // add_filter( 'post_type_link', [$this, 'modify_memo_permalink'], 10, 3 ); // memo addr replace
        // add_filter('template_include', [$this, 'restrict_memory_page_access']);
        // add_filter('upload_dir', [$this, 'upload_dir']);
    }


    // Подключение файла create-memory-page.php ?
    function postEvents() {
        global $values, $errors, $_def_values;
        global $has_error;

// ************************* urls
if(!defined('URL_ACCOUNT')) define('URL_ACCOUNT', get_permalink(50)); 
if(!defined('URL_TARIF')) define('URL_TARIF', get_permalink(321)); // /tarif/
if(!defined('URL_CREATE_MEMO_ID')) define('URL_CREATE_MEMO_ID', 323); // /create-memory-page/ id
if(!defined('URL_CREATE_MEMO')) define('URL_CREATE_MEMO', get_permalink(URL_CREATE_MEMO_ID)); // /create-memory-page/
if(!defined('URL_LOGIN')) define('URL_LOGIN', get_permalink(48)); /*/login/*/
if(!defined('URL_REGISTER')) define('URL_REGISTER', get_permalink(47)); /*/register/*/
if(!defined('URL_REPASS')) define('URL_REPASS', get_permalink(317)); /*/repass/*/
if(!defined('URL_PARTNERSHIP')) define('URL_PARTNERSHIP', get_permalink(347)); // /acception/
if(!defined('URL_ACCEPTION')) define('URL_ACCEPTION', get_permalink(347)); // /acception/
// if(!defined('URL_TARIF')) define('URL_TARIF', get_permalink(53)); // /create-memory-page/ old
// ************************* helpers
        
        // include plugin_dir_path(__FILE__) . 'create-memory-page.php';
        // addMess('include_create_memory_page_template','include_create_memory_page_template');
        // if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_memory_page'])) {
        //     $this->createMemoryPage();
        // }
        if( $_SERVER['REQUEST_METHOD'] === 'POST' ) {

            $this->todo();

            // wsd_addlog('post', 'po__st', $_data=['get'=>$_GET, 'post'=>$_POST], 
            // $status = 'info', $file = __FILE__, __LINE__);
        }
        // addMess('test =====');
        // wsd_addlog('all', 'all_event', $_data=['get'=>$_GET, 'post'=>$_POST], 
        // $status = 'info', $file = __FILE__, __LINE__);
    }


    function todo(){
        // $todo = filter_input(INPUT_POST, 'todo', FILTER_UNSAFE_RAW);
        // $inv_id = $_REQUEST["InvId"];


        $sysev = filter_input(INPUT_GET, 'sysev', FILTER_UNSAFE_RAW); // system pay resultanswer
        $payres = filter_input(INPUT_GET, 'payres', FILTER_UNSAFE_RAW); // user answer
        $sys = filter_input(INPUT_GET, 'sys', FILTER_UNSAFE_RAW);
        $inv_id = filter_input(INPUT_POST, 'InvId', FILTER_UNSAFE_RAW);
        $pay_systems = ['robo'];
        if($sysev){
            $_sys = filter_input(INPUT_POST, 'Shp_sys', FILTER_UNSAFE_RAW);
            // $this->payEvent();
            if($sys == 'robo'){ $this->init('robo'); }
            if(in_array( $sys, $pay_systems)){ $this->sys->payEvent(); }
            
        }
        if($payres){
            $_sys = filter_input(INPUT_POST, 'Shp_sys', FILTER_UNSAFE_RAW);
            $status = 'success';
            if($payres == 'fail') $status = 'error';
            $this->payEvent($payres, 'payUserRequest', $status);
            if($sys == 'robo'){ $this->init('robo'); }
            if(in_array( $sys, $pay_systems)){ $this->sys->payEventUser(); }
        }
        if($todo && 0){
            
            // as a part of ResultURL script
            
            // your registration data
            $mrh_pass2 = "securepass2";   // merchant pass2 here
            $mrh_pass2 = "securepass2";   // merchant pass2 here
            
            // HTTP parameters:
            $out_summ = $_REQUEST["OutSum"];
            $inv_id = $_REQUEST["InvId"];
            $crc = strtoupper($_REQUEST["SignatureValue"]);
            
            // build own CRC
            $my_crc = strtoupper(md5("$out_summ:$inv_id:$mrh_pass2"));
            
            if ($my_crc != $crc)
            {
            echo "bad sign\n";
            exit();
            }
            
            // print OK signature
            echo "OK$inv_id\n";
            
            // perform some action (change order state to paid)

            // switch($todo){
            //     case 'qr-query':
            //         $this->modalProcessQRQuery();
            //         break;
            //     case 'page-pass':
            //         $this->modalProcessPagePass();
            //         break;
            //     case 'become-partner':
            //         $this->modalProcessBecomePartner();
            //         break;
            //     case 'curl-test':
            //         $this->curlTestUnswer();
            //         break;
            // }
            // $return = filter_input(INPUT_POST, 'return', FILTER_UNSAFE_RAW);
            // if($return){
            //     switch($return){
            //         case '/account/':
            //             wp_redirect(URL_ACCOUNT); // site_url('/account/')
            //             exit;
            //             break;
            //     }
            // }
        }
    }


    public function payEvent($ev='', $evtype='payEvent', $status = 'info'){
        $inv_id = filter_input(INPUT_POST, 'InvId', FILTER_UNSAFE_RAW);

        if($this->log) wsd_addlog('payment', $evtype, $_data=['ev'=>$ev, 'get'=>$_GET, 'post'=>$_POST], 
        $status , $file = __FILE__, __LINE__);
    }


    public function payEventSuccess($sys, $bill_id, $data){
        // addMess([$sys, $bill_id, $data],'payEventSuccess');
        // wsd_addlog('payment', 'bill_update', $_data=['data'=>$data, 'get'=>$_GET, 'post'=>$_POST], 
        // $status = 'info', $file = __FILE__, __LINE__);
        if(isset($data['payment_status']) && $data['payment_status'] == 'success'){
            // addMess('[$sys, $bill_id, $data]','do payEventSuccess');
            // $inv_id = filter_input(INPUT_POST, 'InvId', FILTER_UNSAFE_RAW);
            $bill = $this->updateBillPayEvent($sys, $bill_id, $data);

            // if bill is qr delivery query
            if(isset($data['type']) && $data['type'] == 'qr'){
                $pid = $bill['pid'];
                lhc_qr_send_query($pid);
            }

            if($this->log) wsd_addlog('payment', 'bill_update', ['data'=>$data, 'get'=>$_GET, 'post'=>$_POST], 
            $status = 'info');


        }
    }



    public function payEventError($data){
        if(isset($data['payment_status']) && $data['payment_status'] == 'error'){
            // $inv_id = filter_input(INPUT_POST, 'InvId', FILTER_UNSAFE_RAW);

            if($this->log) wsd_addlog('payment', 'pay_error', $_data=['get'=>$_GET, 'post'=>$_POST], 
            $status = 'info', $file = __FILE__, __LINE__);
        }
    }


    public function payEventSuccessUser($sys, $bill_id, $data){
        // addMess([$sys, $bill_id, $data],'payEventSuccess');
        // wsd_addlog('payment', 'bill_update', $_data=['data'=>$data, 'get'=>$_GET, 'post'=>$_POST], 
        // $status = 'info', $file = __FILE__, __LINE__);
        if(isset($data['payment_status']) && $data['payment_status'] == 'success'){
            // addMess('[$sys, $bill_id, $data]','do payEventSuccess');
            // $inv_id = filter_input(INPUT_POST, 'InvId', FILTER_UNSAFE_RAW);
            // $this->updateBillPayEvent($sys, $bill_id, $data);

            // wsd_addlog('payment', 'bill_update', $_data=['data'=>$data, 'get'=>$_GET, 'post'=>$_POST], 
            // $status = 'info', $file = __FILE__, __LINE__);


        }
    }



    public function payEventFailUser($data){
        if(isset($data['payment_status']) && $data['payment_status'] == 'fail'){
            // $inv_id = filter_input(INPUT_POST, 'InvId', FILTER_UNSAFE_RAW);

            // wsd_addlog('payment', 'pay_error', $_data=['get'=>$_GET, 'post'=>$_POST], 
            // $status = 'info', $file = __FILE__, __LINE__);
        }
    }

    public function init($name){
        $this->initSys($name);
    }


    // public function todo(){
    //     $this->initSys($name);
    // }


    public function initSys($name){
        if(array_key_exists($name, $this->systems)){
            $this->sys = $this->systems[$name];
            return;
        }
        switch($name){
            case 'robo': $this->initSysRobocassa(); break;
        }
    }


    public function initSysRobocassa(){
        $this->sys = new RobocassaClass();
        $this->systems['robo'] = $this->sys;
    }


    public function form($uid, $summ, $bill, $todo='', $task='', $data=[]){
        if(!$this->sys) return false;
        return $this->sys->form($uid, $summ, $bill, $todo, $task, $data);
    }


    public function send(){
        if(!$this->sys) return false;
        $res = $this->_send();
    }


    public function _send(){
        $res = $this->sys->send();
    }


    public function log(){

    }


    public function event($name='', $data = []){

    }


    public function success(){

    }


    public function error(){}
    
    // ==============================


    public function _buildWhere($key, $value){
        // $f = [$key, $value];
        return '`' . $key . '` = \'' . $value . '\'';
    }


    public function buildWhere($where=[]){
        $where = array_map([$this, '_buildWhere'], array_keys($where), array_values($where));
        $where = implode(' and ', $where);
        if($where) $where = 'where '.$where;
        return $where;
    }

    // ==============================


    public function getBillPost($post_id, $type=''){
        return $this->_getBillByPostId($post_id, $by='pid', $type);
    }
    public function getBill($bill_id){
        return $this->_getBillByPostId($bill_id, $by='id', $type='');
    }


    /**
     * getBillByPostId
     * Получить запись счёта по id страницы
     */
    public function _getBillByPostId($id, $by='pid', $type='')
    {
        $table = $this->prefix . 'bill';
        $where = [];
        // if($type == 'post') $where['pid'] = (int)$pid;
        $where[$by] = (int)$id;
        if($type) $where['type'] = $type;
        $where = $this->buildWhere($where);
        // $offset = $count * $page;
        $q = "
            SELECT * 
            FROM $table
            $where
            "; 
        // $res = $this->db->get_results($q);
        $res = $this->db->get_row($q, ARRAY_A);
        if($res) $res = (array)$res; else $res = [];
        return $res;
    }
    public function getAllBill($from=0, $to=0){}
    public function getAllUserBill($uid, $from=0, $to=0){}


    public $bill_type = 'tarif'; // tarif qr
    // добавление счёта по user id и page id
    public function addBill($uid, $pid, $data){
        // addMess([$uid, $pid, $data], 'wsd_add_bill 1');
        if(!$uid) return;
        if(!$pid) return;
        // addMess([$uid, $pid, $data], 'wsd_add_bill 1.2');
        if(!array_key_exists('sum', $data) || !$data['sum']) return false;
        // addMess([$uid, $pid, $data], 'wsd_add_bill 2');

        $data['uid'] = $uid;
        if( !array_key_exists('ruid', $data) )$data['ruid'] = 0;
        $data['pid'] = $pid;
        $data['status'] = 0;
        $data['created'] = current_datetime()->format('Y-m-d H:i:s');
        $data['updated'] = '0000-00-00 00:00:00';
        $data['paid'] = '0000-00-00 00:00:00';
        $data['pay'] = 0;
        // addMess($data, 'add bill');
        if(!isset($data['data']) || $data['data'] == '') $data['data'] = ''; // paydata
        else $data['data'] = json_encode( $data['data'] );
        // addMess($data, 'add bill');

        if(!isset($data['type'])) $data['type'] = 'tarif'; // tarif qr
        
        if(!array_key_exists('goods', $data) ) $data['goods'] = '';
        if(!array_key_exists('comment', $data) ) $data['comment'] = '';

        $q = "insert into table";
        $status = [
            'created' => 0,
            'paid' => 1,
        ];

        // addMess([$uid, $pid, $data], 'wsd_add_bill ');
        global $wpdb;
        $prefix = $this->db->prefix . 'lhc_';
        $table = $prefix . 'bill'; // указываем таблицу
        $fields = 
            array( // 'название_колонки' => 'значение'
                'uid' => $data['uid'],
                'ruid' => $data['ruid'],
                'pid' => $data['pid'], // post id
                'status' => $data['status'],
                'type' => $data['type'],
                'created' => $data['created'],
                'updated' => $data['updated'],
                'paid' => $data['paid'],
                'sum' => $data['sum'],
                'pay' => $data['pay'],
                'goods' => $data['goods'],
                'comment' => $data['comment'],
                'data' => $data['data'],
            );
        $formats = [
            'uid' => '%d', // %d - значит число
            'ruid' => '%d',
            'pid' => '%d',
            'status' => '%d',
            'type' => '%s',
            'created' => '%s',
            'updated' => '%s',
            'paid' => '%.2f',
            'sum' => '%.2f',
            'pay' => '%s', // %s - значит строка
            'goods' => '%s',
            'comment' => '%s',
            'data' => '%s',
        ];
        $format = 
            array( 
                '%d', // %d - значит число
                '%d',
                '%d',
                '%d',
                '%s',
                '%s',
                '%s',
                '%.2f',
                '%.2f',
                '%s', // %s - значит строка
                '%s',
                '%s',
                '%s',
            );
        // addMess($fields, 'addBill $fields');
        $res = $wpdb->insert( $table, $fields, $format);
        $last_id = 0;
        if($res) $last_id = $this->db->insert_id;
        // addMess($last_id, 'addBill ');
        // if(!$res) addMess($this->db->last_error, 'addBill ');
        // $reg_log['insert last_error '.__LINE__.' '.microtime()]=$this->db->last_error;

        // $data['uid'] = $uid;
        // $data['sum'] = $sum;
        // $data['goods'] = "Тариф: $t_title";

        // uid int UNSIGNED NOT NULL,
        // status tinyint UNSIGNED NOT NULL,
        // created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        // updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        // paid datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        // sum dec(10,2) NOT NULL,
        // pay dec(10,2) NOT NULL,
        // goods tinytext NOT NULL,
        // comment text NOT NULL,
        // paydata text NOT NULL,

        return $last_id;
    }
    
    public function updateBill($bill_id, $data){
        
        $formats = [
            'uid' => '%d', // %d - значит число
            'ruid' => '%d',
            'pid' => '%d',
            'status' => '%d',
            'type' => '%s',
            'created' => '%s',
            'updated' => '%s',
            'paid' => '%.2f',
            'sum' => '%.2f',
            'pay' => '%s', // %s - значит строка
            'goods' => '%s',
            'comment' => '%s',
            'data' => '%s',
        ];
        $format = [];
        $data['updated'] = current_datetime()->format('Y-m-d H:i:s');
        // $data['paid'] = current_datetime()->format('Y-m-d H:i:s');
        // addMess($data, 'updateBill');
        if(isset($data['data']) && $data['data'] != '')  $data['data'] = json_encode( $data['data'] ); // paydata
        // addMess($data, 'updateBill');
        $updata = $data;

        foreach($data as $k=>$v){
            $format[] = $formats[$k];
        }
        // array(
        //     'updated' => $data['updated'],
        //     'status' => $data['status'],
        //     'paid' => $data['paid'],
        //     'pay' => $data['sum'],
        //     'fee' => $data['fee'],
        // );
        $where = array( 'id' => (int) $bill_id );

        // $this->calcRefererPercents($updata,$where);

        $prefix = $this->db->prefix . 'lhc_';
        $table = $prefix . 'bill';
        $upargs = [$table, $updata, $where, $format];
        $res = $this->db->update( ...$upargs );
        // addMess($upargs , '$upargs');
        // addMess($res , 'res');
        return $bill_id;
    }
    

    // where pyment is success
    public function updateBillPayEvent($sys, $bill_id, $data){
        // id mediumint(9) NOT NULL AUTO_INCREMENT,
        // uid int UNSIGNED NOT NULL,
        // ruid int UNSIGNED NOT NULL,
        // pid int UNSIGNED NOT NULL,
        // status tinyint UNSIGNED NOT NULL,
        // created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        // updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        // paid datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            // way char(32) NOT NULL,
        // sum dec(10,2) NOT NULL,
        // pay dec(10,2) NOT NULL,
        // fee dec(10,2) NOT NULL,
        // rfee dec(10,2) NOT NULL,
        // goods tinytext NOT NULL,
        // comment text NOT NULL,
        // data text NOT NULL,

        $pay_systems = ['robo'];
        // addMess([$sys, $pay_systems],'do payEventSuccess');
        if(!in_array($sys, $pay_systems))return;

        $_data = [];
        $_data['data'] = $data;
        $_data['get'] = $_GET;
        $_data['post'] = $_POST;
        $_data = serialize( $_data );
        
        $bill = wsd_get_bill($bill_id);
        $uid = $bill['uid'];
        $data['uid'] = $uid;

        // up bill status
        $data['status'] = 0;
        $payd = $data['sum'] + $data['fee'];
        if( $bill['sum'] <= $payd ) $data['status'] = 1;

        $lhc_todo = 0;
        if($data['status'] == 1 && array_key_exists('args', $data)){
            $query_args = $data['args'];
            $todo = $query_args['todo']; // form
            $type = $query_args['type']; // bill
            $task = $query_args['task']; // form
            $lt_queue = $query_args['queue']??60; // form
            $lhc_todo = 1;
        }
        
        if($lhc_todo && $lt_queue == 10)  do_action('lhc_action_todo', $todo, $type, $task, $query_args);
        

        if( $bill['sum'] < $payd ) {
            $overpay = $payd + $bill['sum'];
            if( $overpay ){
                $this->add2balance($uid, $ovarpay);
            }
        }

        // $data['uid'] = $uid;
        // if( !array_key_exists('ruid', $data) )$data['ruid'] = 0;
        // $data['pid'] = $pid;
        // $data['status'] = 0;
        // $data['created'] = date('Y-m-d H:i:s');
        // $data['updated'] = '0000-00-00 00:00:00';
        // $data['paid'] = '0000-00-00 00:00:00';
        
        $data['updated'] = current_datetime()->format('Y-m-d H:i:s');
        $data['paid'] = current_datetime()->format('Y-m-d H:i:s');
        // $data['pay'] = 0;
        // $data['data'] = $_data; // paydata

        $updata = array(
            'updated' => $data['updated'],
            'status' => $data['status'],
            'paid' => $data['paid'],
            'pay' => $data['sum'],
            'fee' => $data['fee'],
        );
        
        if($lhc_todo && $lt_queue == 20)  do_action('lhc_action_todo', $todo, $type, $task, $query_args);
        
        $where = array( 'id' => (int) $bill_id );

        $this->calcRefererPercents($updata,$where);
        
        if($lhc_todo && $lt_queue == 30)  do_action('lhc_action_todo', $todo, $type, $task, $query_args);
        

        $prefix = $this->db->prefix . 'lhc_';
        $table = $prefix . 'bill';
        $res = $this->db->update( $table, $updata, $where);

        // $res = $wpdb->insert( $table, $fields, $format);
        $last_id = 0;
        if($res) $last_id = $this->db->insert_id;
        // addMess($last_id, 'addBill ');
            // wsd_addlog('payment', 'bill_update', [$updata, $where], 'system');
        if(!$res){
            addMess($this->db->last_error, 'updateBillPayEvent last_error');
            if($this->log) wsd_addlog('payment', 'bill_update_error', $this->db->last_error, 'error');
            // wsd_addlog('payment', 'bill_update_error', ['error'=>$this->db->last_error], 
            // $status = 'error');
        }
        
        if($lhc_todo && $lt_queue == 40)  do_action('lhc_action_todo', $todo, $type, $task, $query_args);
        

        $this->addPayment($sys, $bill_id, $data);
        $bill = wsd_get_bill($bill_id);
        $uid = $bill['uid'];
        $pid = $bill['pid'];
        
        if($lhc_todo && $lt_queue == 50)  do_action('lhc_action_todo', $todo, $type, $task, $query_args);
        
        
        $payd = 0;
        if(
            $bill['type'] == 'tarif'
            || $bill['type'] == 'tarif-up'
            ){
            update_post_meta($pid, 'payd', 1);
            $payd = get_post_meta($pid, 'payd', 1);
        }
        if( $bill['type'] == 'qr'){
            update_post_meta($pid, 'qr-payd', 1);
            $payd = get_post_meta($pid, 'qr-payd', 1);
        }
        $bill['is_payd'] = $payd;
        $bill['is_payd_pid'] = $pid;
        // addMess($bill, 'updateBillPayEvent $bill');

        $bill['data_type'] = 'bill';
        // wsd_addlog('payment', 'bill_update', ['stg'=>10, 'bill'=>$bill], 'info');
        
        if($lhc_todo && $lt_queue == 60)  do_action('lhc_action_todo', $todo, $type, $task, $query_args);
        
        if($bill['ruid']) {
            $ruid = $bill['ruid'];
            // wsd_addlog('payment', 'bill_update', ['stg'=>2, 'bill'=>$bill], 'info');
            // $partner = lhc_get_partner_userid($bill['ruid']);
            //*****

            $table = $this->prefix . 'partner';
            $where = [];
            $where['uid'] = (int)$ruid;
            $where = $this->buildWhere($where);
            // wsd_addlog('payment2', 'calcPercent', ['$ruid'=>$ruid,'$ruid2'=>(int)$ruid,'$where'=>$where], 'system');
            // $offset = $count * $page;
            $q = "
                SELECT * 
                FROM $table
                $where
            ";
            $partner = $this->db->get_row($q, ARRAY_A);
            // if($res) $res = (array) $res;
            // ********
            $bill['amo_contact_id'] = $partner['amo_contact_id'];
            // wsd_addlog('payment', 'bill_update', ['stg'=>3, 'query'=>$q, 'partner'=>$partner], 'info');
            // wsd_addlog('payment', 'bill_update', ['stg'=>3, 'bill'=>$bill], 'info');
            wsd_crm_event('pymentWithFeeReferer', $bill, 'amo');
            // wsd_addlog('payment', 'bill_update', ['stg'=>4, 'bill'=>$bill], 'info');

            if(isset($data['rfee']) && $data['rfee']){

            }else $data['rfee'] = 0;
            $tup =  current_datetime()->format('Y-m-d H:i:s');
            $q = 'update `wp_lhc_referal` set sum = sum + 2 , fee = fee + 1 where uid = 1 and ruid = 10';
            $q = "update `wp_lhc_referal` "
                . "set buycou = buycou + 1, sum = sum + {$bill['sum']} ,  fee = fee + {$bill['rfee']} , updated = '$tup' "
                . "where uid = {$bill['uid']} and ruid = {$bill['ruid']}";
            $this->db->query($q);
            wsd_addlog('payment', 'bill_update', ['stg'=>'add ref %', 'query'=>$q, 'bill'=>$bill], 'info');
        }
        else{
            // wsd_addlog('payment', 'bill_update', ['stg'=>5, 'bill'=>$bill], 'info');
            wsd_crm_event('pyment', $bill, 'amo');
            // wsd_addlog('payment', 'bill_update', ['stg'=>6, 'bill'=>$bill], 'info');
        }
        
        if($lhc_todo && $lt_queue == 70)  do_action('lhc_action_todo', $todo, $type, $task, $query_args);

        return $bill;
    }

    /**
     * рассчитать комиссию партнёра
     * добавить событие в амо на приход с партнёром
     * добавить событие в амо на приход без партнёра
     */
    public function calcRefererPercents(&$data,$where){
        if(!isset($where['id'])) return;
        $bill_id = $where['id'];
        // wsd_addlog('payment', 'calcPercent', [$data,$where], 'info');
        $bill = $this->getBill($bill_id);
        if(!$bill) return;
        // if(!$bill['ruid']) return;


        $ruid = $bill['ruid'];
        // wsd_addlog('payment', 'calcPercent', ['ruid'=>$ruid,'$bill_id'=>$bill_id,'$bill'=>$bill], 'info');
        
        if($bill['ruid']){
            $ruid = $bill['ruid'];
            $sum = $bill['sum'];
            // wsd_addlog('payment', 'calcPercent', ['ruid'=>$ruid,'$sum'=>$sum], 'info');
            // $partner = lhc_get_partner_userid($ruid);
            //*****

            $table = $this->prefix . 'partner';
            $where = [];
            $where['uid'] = (int)$ruid;
            $where = $this->buildWhere($where);
            // wsd_addlog('payment2', 'calcPercent', ['$ruid'=>$ruid,'$ruid2'=>(int)$ruid,'$where'=>$where], 'system');
            // $offset = $count * $page;
            $q = "
                SELECT * 
                FROM $table
                $where
            "; 
            $partner = $this->db->get_row($q, ARRAY_A);
            // if($res) $res = (array) $res;
            // ********
            // wsd_addlog('payment2', 'calcPercent', ['$partner'=>$partner,'$q'=>$q], 'system');
            if($partner && $partner['approve']){
                $pfee = $partner['fee'];
                $rfee = $sum / 100 * $pfee;
                $data['rfee'] = $rfee;
            }
            $this->doActionPymentPartner($bill, $data, $partner);
        }else{
            $this->doActionPyment($bill, $data);
        }
    }


    public function add2balance($uid, $sum){
        if(!$uid) return;
        if(!$sum) return;
        $this->addBalance($uid, $sum=0);
    }


    public function addBalance($uid, $sum=0){
        if(!$uid) return;
        // if(!$sum) return;
        $balansse = $this->getBalsnceRow($uid);
        $table = $prefix . 'balsnse';
        if(!$balanse){
            $this->db->insert($table, ['uid'=>$uid, 'balanse'=>$sum], ['%d','%.2f']);
        }else{
            $_sum = $balansse['sum'] + $sum;
            $this->db->updata($table, ['balanse'=>$_sum], ['uid'=>$uid], ['%.2f'], ['%d']);
        }
    }


    public function getBalsnceRow($uid){
        if(!$uid) return;
        $table = $prefix . 'balsnse';
        $q = "select * from $table where `uid` = %d";
        $q = $this->db->prepare($q, (int) $uid);
        return $this->db->get_row($q);
    }


    public function getBalsnce($uid){
        if(!$uid) return;
        $table = $prefix . 'balsnse';
        $q = "select balanse from $table where `uid` = %d";
        $q = $this->db->prepare($q, (int) $uid);
        return $this->db->get_var($q);
    }


    // before update pyment bill
    public function doActionPymentPartner($bill, $data, $partner){
        do_action('amoAddDealReferal', $bill, $data, $partner);
    }

    // before update pyment bill
    public function doActionPyment($bill, $data){
        do_action('amoAddDeal', $bill, $data);
    }

    public function addPayment($sys, $bill_id, $data){
        // id mediumint(9) NOT NULL AUTO_INCREMENT,
        // uid int UNSIGNED NOT NULL,
        // created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        // sysname char(32) NOT NULL,
        // sum dec(10,2) NOT NULL,
        // data text NOT NULL,

        // $user = get_userdata($uid);
        // $mail = $user->user_email;

        $_data = [];
        // $_data['data'] = $data;
        $_data['get'] = $_GET;
        $_data['post'] = $_POST;
        $_data = serialize( $_data );

        $data['created'] = current_datetime()->format('Y-m-d H:i:s');
        $data['sysname'] = $sys;
        $data['sum'] = $data['sum'];
        $data['data'] = $_data; // paydata
        // $data['updated'] = '0000-00-00 00:00:00';
        // $data['paid'] = '0000-00-00 00:00:00';
        // $data['updated'] = date('Y-m-d H:i:s');
        // $data['paid'] = date('Y-m-d H:i:s');
        // $data['pay'] = 0;

        $prefix = $this->db->prefix . 'lhc_';
        $table = $prefix . 'payment'; // указываем таблицу
        $fields = 
            array( // 'название_колонки' => 'значение'
                'uid' => $data['uid'],
                'created' => $data['created'],
                'sysname' => $data['sysname'],
                'sum' => $data['sum'],
                'data' => $data['data'],
            );
        $format = 
            array( 
                '%d', // %d - значит число
                '%s',
                '%s',
                '%.2f',
                '%s',
            );
        // addMess($fields, 'addBill $fields');
        $res = $this->db->insert( $table, $fields, $format);
        $last_id = 0;
        if($res) $last_id = $this->db->insert_id;

    }
}
global $paymentClass;
$paymentClass = new PymentClass();


/**
 * sys  - pyment system name [ robo ]
 * summ - billing summ
 * bid  - bill id
 */
function wsd_pay_form($sys, $uid, $summ, $bill_id, $todo='', $task='', $data=[]){
    global $paymentClass;
    if( !$paymentClass ){
        $paymentClass = new PymentClass($sys);
    }
    $paymentClass->init($sys);
    return $paymentClass->form($uid, $summ, $bill_id, $todo, $task, $data);
}


/**
 * Получить счёт по post id
 */
function wsd_get_bill_post($post_id, $type='tarif'){
    global $paymentClass;
    if( !$paymentClass ){
        $paymentClass = new PymentClass();
    }
    return $paymentClass->getBillPost($post_id, $type);
}


/**
 * Получить счёт по id
 */
function wsd_get_bill($bill_id){
    global $paymentClass;
    if( !$paymentClass ){
        $paymentClass = new PymentClass();
    }
    return $paymentClass->getBill($bill_id);
}


/**
 * Получить все счета
 */
function wsd_get_bills($from=0, $to=0){
    global $paymentClass;
    if( !$paymentClass ){
        $paymentClass = new PymentClass();
    }
    return $paymentClass->getAllBill($from, $to);
}


/**
 * Получить все счета юзера
 */
function wsd_get_user_bills($uid, $from=0, $to=0){
    global $paymentClass;
    if( !$paymentClass ){
        $paymentClass = new PymentClass();
    }
    return $paymentClass->getAllUserBill($uid, $from, $to);
}


/**
 * Добавить счёт
 */
function wsd_add_bill($uid, $pid, $data){
    global $paymentClass;
    if( !$paymentClass ){
        $paymentClass = new PymentClass();
    }
    return $paymentClass->addBill($uid, $pid, $data);
}


/**
 * Обновить счёт
 */
function wsd_update_bill($bill_id, $data){
    global $paymentClass;
    if( !$paymentClass ){
        $paymentClass = new PymentClass();
    }
    return $paymentClass->updateBill($bill_id, $data);
}


// устарела
function wsd_getBillByPostId($pid, $sys='robo'){
    global $paymentClass;
    if( !$paymentClass ){
        $paymentClass = new PymentClass();
    }
    return $paymentClass->_getBillByPostId($pid, 'pid');
}