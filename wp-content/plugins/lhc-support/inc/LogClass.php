<?php
/*
File: LogClass.php
Descriptin: Log System Class
Author: WSD
Created: 2024.10.10 20:50
*/
if ( ! defined( 'ABSPATH' ) ) { echo 'Silence gold.'; exit(403); }

class LogClass{
    public $db = null;
    public $prefix = '';
    public $table = '';

    
    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->prefix = $this->db->prefix . 'lhc_';
        $this->table = $this->prefix . 'log';
        // $pr = $wpdb->prefix;
        // $q = "SELECT * FROM {$pr}options WHERE option_id = 1";
        // $r = $this->db->get_results( $q ); //, OBJECT
        // addMess($r, 'db q');
        $this->initDB();
    }

    // ======================
    // ======================


    public function initDB(){
        $table = 'log';
        if ( $this->tableNotExists($table) ) $this->addDbTables($table);
    }


    function tableNotExists($table){
        // $table_name = $wpdb->base_prefix.'custom_prices';
        $prefix = $this->db->prefix . 'lhc_';
        $table_name = $prefix . $table;
        $q = 'SHOW TABLES LIKE %s';
        $query = $this->db->prepare( $q, $this->db->esc_like( $table_name ) );
        $res = $this->db->get_var( $query );
        // addMess($query, '$query');
        // addMess($res, '$res');
        // addMess(( ! $res == $table_name ), "( ! $res == $table_name )");
        return ( ! $res == $table_name );
    }


    /**
     * addDbTables
     * Добавление таблиц в базу
     */
    public function addDbTables($table)
    {
        addMess($this->prefix . $table, 'addDbTables', 'warning');
        /*
        -candidate
        partner [uid, approve, fee, code, name, surname, mail, phone]
        referal [uid, ruid, adddate, code, buycou, sum]
        bill [uid, tarif, success, created, updated, paiddatetime, sum, pay, goods, comment, paydata]
        */

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset_collate = $this->db->get_charset_collate();
        switch($table){
        
            case 'log':
                $table_name = $this->prefix . 'log';
                $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    event char(16) NOT NULL,
                    status char(16) NOT NULL,
                    created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                    name char(128) NOT NULL,
                    file char(128) NOT NULL,
                    line char(8) NOT NULL,
                    serialized tinyint NOT NULL,
                    data text NOT NULL,
                    PRIMARY KEY  (id)
                ) $charset_collate;";
                maybe_create_table( $table_name, $sql );
                break;
        }
    }
    // 123456789 123456789 123456789 123456789 123456789 123456789 
    // 123456789 123456789 123456789 123456789 123456789 123456789 


    public function addLog($event, $name, $_data='', $status = 'info', $file = '', $line = ''){

        // $file = '';
        // $_data = 
        $serialized = 0;
        if( gettype($_data) == 'string' || gettype($_data) == 'integer' ) {
             /* $data = $d; $pre = false; */ 
        }
        else {
            $serialized = 1;
            $_data = serialize( $_data );
        }
        $data = [];
        $data['event'] = $event;
        $data['status'] = $status;
        $data['created'] = current_datetime()->format('Y-m-d H:i:s');
        $data['name'] = $name;
        $data['file'] = $file;
        $data['line'] = $line;
        $data['serialized'] = $serialized;
        $data['data'] = $_data;
        
        // if(!array_key_exists('goods', $data) ) $data['goods'] = '';
        // if(!array_key_exists('comment', $data) ) $data['comment'] = '';

        // $q = "insert into table";
        // $status = [
        //     'created' => 0,
        //     'paid' => 1,
        // ];

        // global $wpdb;
        $this->db->insert( 
            $this->prefix . 'log', // указываем таблицу
            array( // 'название_колонки' => 'значение'
                'event' => $data['event'],
                'status' => $data['status'],
                'created' => $data['created'],
                'name' => $data['name'],
                'file' => $data['file'],
                'line' => $data['line'],
                'serialized' => $data['serialized'],
                'data' => $data['data'],
            ), 
            array( 
                '%s',
                '%s',
                '%s',
                '%s', // %s - значит строка
                '%s',
                '%s',
                '%d', // %d - значит число
                '%s',
            ) 
        );
    }
    
    // ==============================


    public function _buildWhere($key, $value, $eq){
        // $f = [$key, $value];
        // addMess([$key, $value, $eq],'$key, $value, $eq');
        $equal = '=';
        if( $eq ) $equal = $eq;
        if( $key == 'from' ) $key = 'created';
        if( $key == 'to' ) $key = 'created';
        // if( $eq = 'like' ) $equal = $eq;
        $w = '`' . $key . '` ' . $equal . ' %s';
        return $w;
        // return '`' . $key . '` = \'' . $value . '\'';
    }


    public function buildWhere($where=[], $equal=[]){
        $_equal = [];
        // addMess($equal,'$equal');
        foreach($where as $k=>$v){ $_equal[$k]='='; if(isset($equal[$k])) $_equal[$k] = $equal[$k]; }
        // addMess($_equal,'$_equal');
        $where = array_map([$this, '_buildWhere'],
            array_keys($where), array_values($where), array_values($_equal));
        $where = implode(' and ', $where);
        if($where) $where = 'where '.$where;
        return $where;
    }

    // ==============================


    /**
     * getLogs
     * Получить список логов
     */
    public function getLogs($page = 0, $count = 1000, $_where = [], $equal = [])
    {
        // $out = [];
        // array_push($out, 'get Logs');
        // $q = "
        //     SELECT * 
        //     FROM $this->table
        //     WHERE post_status = 'publish' 
        //     AND post_type = 'memo'
        //     "; // page
        
        $offset = $count * $page;

        // addMess($equal,'$equal');
        $where = $this->buildWhere($_where, $equal);
        // addMess($_where,'$_where');
        // addMess($where,'$where');
        // addMess(array_values( $_where ),'array_values');
        $q = "
            SELECT * 
            FROM $this->table
            $where
            order by `id` desc
            limit $count offset $offset
            "; 
        $prep = array_values( $_where );
        $args = [$q];
        if(count($prep))  $args[] = $prep;
        $args = [$q, $prep];
        // addMess($args,'$args');
        // addMess(array_values( $_where )??null,'$args 2');
        if(count($_where)) $q = $this->db->prepare( ...$args );
        // addMess($q,'$q');
        $log = $this->db->get_results($q);
        return $log;
    }


    /**
     * getLogCount
     * Получить количество логов
     */
    public function getLogCount($_where = [], $equal = [])
    {
        $where = $this->buildWhere($_where, $equal);
        $q = "
            SELECT count(*) 
            FROM $this->table
            $where
        ";

        $prep = array_values( $_where );
        $args = [$q];
        if(count($prep))  $args[] = $prep;
        // $args = [$q, $prep];
        // addMess($args,'$args');
        // addMess(array_values( $_where )??null,'$args 2');
        if(count($_where)) $q = $this->db->prepare( ...$args );
        // $q = $this->db->prepare( ...$args );
        // $q = $this->db->prepare( $q, array_values( $_where )??null);
        $cou = $this->db->get_var($q);
        return $cou;
    }


    /**
     * getListUnique
     * получить список уникальных значений
     * по имени колонки
    */
    public function getListUnique($name){
        $fields = [
            'event',
            'status',
            'created',
            'name',
            'file',
            'line',
            'data',
        ];
        if(!in_array($name, $fields)) return [];
        $q = "
            SELECT distinct $name
            FROM $this->table
        ";
        // addMess($q,'$q');
        $cou = $this->db->get_col($q);
        return $cou;
    }

}
function has_user_role($role, $uid = 0){
    if($uid){
        $user = get_userdata($uid);
    }else{
        $user = wp_get_current_user();
    }
    return in_array( $role, (array) $user->roles );
    // if ( in_array( 'author', (array) $user->roles ) ) {
    //     //The user has the "author" role
    // }
}


function has_cap( $cap, ...$args ){
    $user = wp_get_current_user();
    return $user->has_cap($cap, $args);
}


function set_role( $uid, $role ){
    // $user = wp_get_current_user();
    $user = get_userdata($uid);
    return $user->set_role($role);
}


/**
 * 
*/
function wsd_addlog($event, $name, $_data='', $status = 'info', $file = '', $line = ''){
    global $logClass;
    if( !$logClass ){
        $logClass = new LogClass();
    }
    $bt = debug_backtrace(2);
    if(!$file) $file = $bt[0]['file'];
    if(!$line) $line = $bt[0]['line'];
    $file = str_replace(ABSPATH, '/',$file);
    $file = str_replace('/wp-content/plugins', '[plags]',$file);
    $file = str_replace('/wp-content/themes', '[thems]',$file);
    return $logClass->addLog($event, $name, $_data, $status, $file, $line);
}


/**
 * 
*/
function wsd_getlog( $page = 0, $count = 1000, $where = [], $equal=[] ){
    global $logClass;
    if( !$logClass ){
        $logClass = new LogClass();
    }
    return $logClass->getLogs($page, $count, $where, $equal);
}


/**
 * 
*/
function wsd_getlogcou( $where = [], $equal=[] ){
    global $logClass;
    if( !$logClass ){
        $logClass = new LogClass();
    }
    return $logClass->getLogCount($where, $equal);
}


/**
 * 
*/
function wsd_getlogListUnique( $name ){
    global $logClass;
    if( !$logClass ){
        $logClass = new LogClass();
    }
    return $logClass->getListUnique($name);
}
