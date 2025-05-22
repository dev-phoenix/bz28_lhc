
<?php
/*
File: PartnerClass.php
Descriptin: Partner System Class
Author: WSD
Created: 2024.10.10 20:50
*/

if (!session_id()) {
    session_start();
}

// echo 'curl-test:OK2';
// exit(200);
class PartnerClass{
    public $db = null;
    public $prefix = '';
    public $table = '';
    public $log = false;
    public $errorlog = false;

    
    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->prefix = $this->db->prefix . 'lhc_';
        // $pr = $wpdb->prefix;
        // $q = "SELECT * FROM {$pr}options WHERE option_id = 1";
        // $r = $this->db->get_results( $q ); //, OBJECT
        // addMess($r, 'db q');
        $this->checkDbTables();
    }

    // ======================
    // ======================
    


    /**
     * chemkDbTables
     * Проверка существавания таблиц в базе
     */
    public function checkDbTables()
    {
        $table = 'partner';
        if ( $this->tableNotExists($table) ) $this->addDbTables($table);
        $table = 'referal';
        if ( $this->tableNotExists($table) ) $this->addDbTables($table);
        $table = 'bill';
        if ( $this->tableNotExists($table) ) $this->addDbTables($table);
        $table = 'payment';
        if ( $this->tableNotExists($table) ) $this->addDbTables($table);
        $table = 'cashout';
        if ( $this->tableNotExists($table) ) $this->addDbTables($table);
        $table = 'balanse';
        if ( $this->tableNotExists($table) ) $this->addDbTables($table);
        // $table = 'log';
        // if ( $this->tableNotExists($table) ) $this->addDbTables($table);
        // `wp_lhc_balanse`, `wp_lhc_bill`, `wp_lhc_cashout`, `wp_lhc_log`,
        // `wp_lhc_partner`, `wp_lhc_payment`, `wp_lhc_referal`;
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

    public function alterTable(){
        $add = 'alter table `wp_lhc_partner` add column `amo_contact_id` int unsigned default 0 after `uid`';
    }
    /**
     * addDbTables
     * Добавлеие таблиц в базу
     */
    public function addDbTables($table)
    {
        $prefix = $this->db->prefix . 'lhc_';
        if($this->log) addMess($prefix . $table, 'addDbTables', 'warning');
        /*
        -candidate
        partner [uid, approve, fee, code, name, surname, mail, phone]
        referal [uid, ruid, adddate, code, buycou, sum]
        bill [uid, tarif, success, created, updated, paiddatetime, sum, pay, goods, comment, paidata]
        */

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset_collate = $this->db->get_charset_collate();
        switch($table){
            case 'partner':
                $table_name = $prefix . 'partner';
                $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    uid int UNSIGNED NOT NULL,
                    approve tinyint NOT NULL,
                    created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                    updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                    approved datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                    fee tinyint UNSIGNED NOT NULL,
                    code char(32) NOT NULL,
                    name VARCHAR(32) NOT NULL,
                    surname VARCHAR(32) NOT NULL,
                    mail VARCHAR(32) NOT NULL,
                    phone VARCHAR(32) NOT NULL,
                    fullsum dec(10,2) NOT NULL,
                    currentsum dec(10,2) NOT NULL,
                    PRIMARY KEY  (id)
                ) $charset_collate;";
                maybe_create_table( $table_name, $sql );
                break;
            
            case 'referal':
                $table_name = $prefix . 'referal';
                $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    uid int UNSIGNED NOT NULL,
                    ruid int UNSIGNED NOT NULL,
                    created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                    updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                    code char(32) NOT NULL,
                    buycou int UNSIGNED NOT NULL,
                    sum dec(10,2) NOT NULL,
                    fee dec(10,2) NOT NULL,
                    PRIMARY KEY  (id)
                ) $charset_collate;";
                maybe_create_table( $table_name, $sql );
                break;
            
            case 'bill':
                $table_name = $prefix . 'bill';
                $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    uid int UNSIGNED NOT NULL,
                    ruid int UNSIGNED NOT NULL,
                    pid int UNSIGNED NOT NULL,
                    status tinyint UNSIGNED NOT NULL,
                    type char(32) default '',
                    created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                    updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                    paid datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                    sum dec(10,2) NOT NULL,
                    pay dec(10,2) NOT NULL,
                    fee dec(10,2) NOT NULL,
                    rfee dec(10,2) NOT NULL,
                    goods tinytext NOT NULL,
                    comment text NOT NULL,
                    data text NOT NULL,
                    PRIMARY KEY  (id)
                ) $charset_collate;";
                maybe_create_table( $table_name, $sql );
                break;
        
            case 'payment':
                $table_name = $prefix . 'payment';
                $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    uid int UNSIGNED NOT NULL,
                    created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                    sysname char(32) NOT NULL,
                    sum dec(10,2) NOT NULL,
                    data text NOT NULL,
                    PRIMARY KEY  (id)
                ) $charset_collate;";
                maybe_create_table( $table_name, $sql );
                break;
        
            case 'cashout':
                $table_name = $prefix . 'cashout';
                $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    uid int UNSIGNED NOT NULL,
                    status tinyint UNSIGNED NOT NULL,
                    created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                    updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                    paid datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                    sysname char(32) NOT NULL,
                    account char(32) NOT NULL,
                    sum dec(10,2) NOT NULL,
                    pay dec(10,2) NOT NULL,
                    data text NOT NULL,
                    PRIMARY KEY  (id)
                ) $charset_collate;";
                maybe_create_table( $table_name, $sql );
                break;
    
            case 'balanse':
                $table_name = $prefix . 'balanse';
                $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    uid int UNSIGNED NOT NULL,
                    balanse dec(10,2) NOT NULL,
                    PRIMARY KEY  (id)
                ) $charset_collate;";
                maybe_create_table( $table_name, $sql );
                break;
        
            case 'log':
                $table_name = $prefix . 'log';
                $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    name char(32) NOT NULL,
                    status char(16) NOT NULL,
                    created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                    data text NOT NULL,
                    PRIMARY KEY  (id)
                ) $charset_collate;";
                maybe_create_table( $table_name, $sql );
                break;
        }
    }
    
    // ==============================


    /**
     * addCandidat
     * Добавление пользователя в таблицу кандидатов
     */
    public function addCandidat($uid, &$_data)
    {
        $this->table = $this->prefix . 'partner';
        // $file = '';
        // $_data = 
        // $serialized = 0;
        // if( gettype($_data) == 'string' || gettype($_data) == 'integer' ) {
        //      /* $data = $d; $pre = false; */ 
        // }
        // else {
        //     $serialized = 1;
        //     $_data = serialize( $_data );
        // }

        // id mediumint(9) NOT NULL AUTO_INCREMENT,
        // uid int UNSIGNED NOT NULL,
        // approve tinyint NOT NULL,
        // created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        // updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        // approved datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        // fee tinyint UNSIGNED NOT NULL,
        // code char(32) NOT NULL,
        // name VARCHAR(32) NOT NULL,
        // surname VARCHAR(32) NOT NULL,
        // mail VARCHAR(32) NOT NULL,
        // phone VARCHAR(32) NOT NULL,
        // fullsum dec(10,2) NOT NULL,
        // currentsum dec(10,2) NOT NULL,

        $code = '';

        $data = [];
        $data['uid'] = $uid;
        $data['approve'] = 0;
        $data['created'] = current_datetime()->format('Y-m-d H:i:s');
        $data['updated'] = '0000-00-00 00:00:00';
        $data['approved'] = '0000-00-00 00:00:00';
        $data['fee'] = 0;
        $data['code'] = $code;
        $data['name'] = $_data['name'];
        $data['surname'] = $_data['surname'];
        $data['mail'] = $_data['mail'];
        $data['phone'] = $_data['phone'];
        $data['fullsum'] = 0;
        $data['currentsum'] = 0;
        
        $code = strtoupper(hash('crc32',$data['uid'].$data['created'].$data['phone'])) . '-' . $uid;
        $data['code'] = $code;
        
        // if(!array_key_exists('goods', $data) ) $data['goods'] = '';
        // if(!array_key_exists('comment', $data) ) $data['comment'] = '';

        // $q = "insert into table";
        // $status = [
        //     'created' => 0,
        //     'paid' => 1,
        // ];

        // https://developer.wordpress.org/reference/classes/wpdb/#insert-row
        // Data to insert (in column => value pairs). 
        // Both $data columns and $data values should be “raw” (neither should be SQL escaped).
        
        // global $wpdb;
        $res = $this->db->insert(
            $this->table, // указываем таблицу
            array( // 'название_колонки' => 'значение'
                'uid' => $data['uid'],
                'approve' => $data['approve'],
                'created' => $data['created'],
                'updated' => $data['updated'],
                'approved' => $data['approved'],
                'fee' => $data['fee'],
                'code' => $data['code'],
                'name' => $data['name'],
                'surname' => $data['surname'],
                'mail' => $data['mail'],
                'phone' => $data['phone'],
                'fullsum' => $data['fullsum'],
                'currentsum' => $data['currentsum'],
            ), 
            array( 
                '%d', // %d - значит число
                '%d',

                '%s',
                '%s',
                '%s', // %s - значит строка

                '%d',
                '%s',

                '%s',
                '%s',
                '%s',
                '%s',

                '%.2f',
                '%.2f',
            ) 
        );
        if($res){
            $last_id = $this->db->insert_id;
            $_data['partner_id'] = $last_id;
        }
    }


    /**
     * setPartner
     * Добавление кандидата в таблицу партнёров
     */
    public function setPartner($id, $_data)
    {
        $this->table = $this->prefix . 'partner';

        $code = '';

        $data = [];
        // $data['uid'] = $uid;
        $data['approve'] = $_data['approve'];
        // $data['created'] = current_datetime()->format('Y-m-d H:i:s');
        $data['updated'] = current_datetime()->format('Y-m-d H:i:s');
        $data['approved'] = '0000-00-00 00:00:00';
        if( $data['approve'] == 1) $data['approved'] = current_datetime()->format('Y-m-d H:i:s');
        $data['fee'] = $_data['fee'];
        // $data['code'] = $code;
        // $data['name'] = $_data['name'];
        // $data['surname'] = $_data['surname'];
        // $data['mail'] = $_data['mail'];
        // $data['phone'] = $_data['phone'];
        // $data['fullsum'] = 0;
        // $data['currentsum'] = 0;
        
        // $code = strtoupper(hash('crc32',$data['uid'].$data['created'].$data['phone'])) . '-' . $uid;
        // $data['code'] = $code;

        $this->db->update(
            $this->table,
            array(
                // 'uid' => $data['uid'],
                'approve' => $data['approve'],
                // 'created' => $data['created'],
                'updated' => $data['updated'],
                'approved' => $data['approved'],
                'fee' => $data['fee'],
                // 'code' => $data['code'],
                // 'name' => $data['name'],
                // 'surname' => $data['surname'],
                // 'mail' => $data['mail'],
                // 'phone' => $data['phone'],
                // 'fullsum' => $data['fullsum'],
                // 'currentsum' => $data['currentsum'],
            ),
            array( 'id' => (int)$id )
        );

        // event action partner approve send to amo
        $q = "select * from {$this->table} where id = %d";
        $q = $this->db->prepare($q, [ (int)$id ]);
        $partner = $this->db->get_row( $q, ARRAY_A );
        if($partner && $partner['approve'] == 1){
            $uid = $partner['uid'];
            // do_action('amoCreateContact', $uid, $partner, 'partner');
        }
    }


    public function setPartnerAmoId($pid, $aid){
        $this->table = $this->prefix . 'partner';
        $res = $this->db->update(
            $this->table,
            array(
                'amo_contact_id' => $aid,
            ),
            array( 'id' => (int)$pid )
        );
        if($this->log) addMess([$pid, $aid,$res],'setPartnerAmoId');
    }


    /**
     * addReferal
     * Добавление реферала с привязкой к партнёру
     * uid реферал
     * code код партнёра
     */
    public function addReferal($uid, $code)
    {
        global $reg_log;
        if(!$reg_log) $reg_log = [];
        // $reg_log['addReferal '.__LINE__.' '.microtime()]=[$uid, $code];
        // $file = '';
        // $_data = 
        // $serialized = 0;
        // if( gettype($_data) == 'string' || gettype($_data) == 'integer' ) {
        //      /* $data = $d; $pre = false; */ 
        // }
        // else {
        //     $serialized = 1;
        //     $_data = serialize( $_data );
        // }

        // id mediumint(9) NOT NULL AUTO_INCREMENT,
        // uid int UNSIGNED NOT NULL,
        // ruid int UNSIGNED NOT NULL,
        // created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        // updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        // code char(32) NOT NULL,
        // buycou int UNSIGNED NOT NULL,
        // sum dec(10,2) NOT NULL,
        // fee dec(10,2) NOT NULL,

        // $code = '';
        // $reg_log['addReferal 1 '.__LINE__.' '.microtime()]='1-'.$code;
        $ruid = lhс_validateReferalCode($code);
        // $reg_log['addReferal $ruid '.__LINE__.' '.microtime()] = var_export($ruid, 1);
        if(!$ruid) return;
        // $reg_log['addReferal 2 '.__LINE__.' '.microtime()]='2';

        $data = [];
        $data['uid'] = $uid;
        $data['ruid'] = $ruid;
        $data['created'] = current_datetime()->format('Y-m-d H:i:s');
        $data['updated'] = '0000-00-00 00:00:00';
        $data['code'] = $code;
        $data['buycou'] = 0;
        $data['sum'] = 0;
        $data['fee'] = 0;
        
        // $code = strtoupper(hash('crc32',$data['uid'].$data['created'].$data['phone'])) . '-' . $uid;
        // $data['code'] = $code;
        
        // if(!array_key_exists('goods', $data) ) $data['goods'] = '';
        // if(!array_key_exists('comment', $data) ) $data['comment'] = '';

        // $q = "insert into table";
        // $status = [
        //     'created' => 0,
        //     'paid' => 1,
        // ];

        // https://developer.wordpress.org/reference/classes/wpdb/#insert-row
        // Data to insert (in column => value pairs). 
        // Both $data columns and $data values should be “raw” (neither should be SQL escaped).
        
        // global $wpdb;
        $fields = array( // 'название_колонки' => 'значение'
                'uid' => $data['uid'],
                'ruid' => $data['ruid'],
                'created' => $data['created'],
                'updated' => $data['updated'],
                'code' => $data['code'],
                'buycou' => $data['buycou'],
                'sum' => $data['sum'],
                'fee' => $data['fee'],
            );
        $format = array( 
                '%d',
                '%d',

                '%s',
                '%s',
                '%s',

                '%d',
                '%.2f',
                '%.2f',
            ) ;
        // $this->db->show_errors();
        // $this->db->hide_errors();
        $this->table = $this->prefix . 'referal';
        $err = $this->db->print_error();
        $res = $this->db->insert( $this->table, $fields, $format );
        // $reg_log['insert tab '.__LINE__.' '.microtime()]=$this->table;
        // $reg_log['insert $res '.__LINE__.' '.microtime()]=var_export($res, 1);
        // $reg_log['insert $fields '.__LINE__.' '.microtime()]=$fields;
        // $reg_log['insert $format '.__LINE__.' '.microtime()]=$format;
        // $reg_log['insert last_error '.__LINE__.' '.microtime()]=$this->db->last_error;
        return $res;
    }
    
    // ==============================


    /**
     * getCandidates
     * Получить список кандидатов
     */
    public function getCandidates($page = 0, $count = 1000)
    {
        $this->table = $this->prefix . 'partner';
        $where = [];
        $where['approve'] = 0;
        $where = $this->buildWhere($where);
        $offset = $count * $page;
        $q = "
            SELECT * 
            FROM $this->table
            $where
            order by `id` desc
            limit $count offset $offset
            "; 
        $res = $this->db->get_results($q);
        return $res;
    }


    /**
     * getPartners
     * Получить список партнёров
     */
    public function getPartners($page = 0, $count = 1000, $_where = [], $equal=[] )
    {
        $this->table = $this->prefix . 'partner';
        $tablebill = $this->prefix . 'bill';
        $where = [];
        $_where['approve'] = 1;
        $where = $this->buildWhere($where);
        $where = $this->buildWhere_equal($_where, $equal);
        $offset = $count * $page;

        $sel = ', b.sum';
        $join = "inner join ( select sum(`sum`) as sum "
            ."from $tablebill as b1 on b1.ruid = p.uid and status = 1 ) as b" ;
            
        $sel = ", sum(b.sum) as 'sum', sum(b.rfee) as 'percent sum'";
        $join = "left join $tablebill as b on b.ruid = p.uid and b.status = 1";
        // $join = '';
        // $join = '';
        $q = "
            SELECT p.* $sel
            FROM $this->table as p
            $join
            $where
            group by p.id
            order by p.`id` desc
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
        $res = $this->db->get_results($q);
        return $res;
    }


    /**
     * getPartner
     * Получить запись партнёра
     */
    public function getPartner($id)
    {
        $this->table = $this->prefix . 'partner';
        $where = [];
        $where['id'] = (int)$id;
        $where = $this->buildWhere($where);
        // $offset = $count * $page;
        $q = "
            SELECT * 
            FROM $this->table
            $where
            "; 
        $res = $this->db->get_row($q, ARRAY_A);
        if($res) $res = (array)$res; else $res = [];
        return $res;
    }


    /**
     * getPartner
     * Получить запись партнёра по uid
     */
    public function getPartnerUser($uid)
    {
        $this->table = $this->prefix . 'partner';
        $where = [];
        $where['uid'] = (int)$uid;
        $where = $this->buildWhere($where);
        // $offset = $count * $page;
        $q = "
            SELECT * 
            FROM $this->table
            $where
            "; 
        // wsd_addlog('payment', 'calcPercent', ['getPartnerUser'=>$q], 'info');
        // $res = $this->db->get_results($q);
        $res = $this->db->get_row($q, ARRAY_A);
        if($res) $res = (array) $res;
        // wsd_addlog('payment', 'calcPercent', ['$res'=>$res], 'info');
        if($res) $res = $res; else $res = [];
        // wsd_addlog('payment', 'calcPercent', ['$res'=>$res], 'info');
        return $res;
    }


    /**
     * getReferals
     * Получить список рефералов
     */
    public function getReferals($uid, $page = 0, $count = 1000, $all = false)
    {
        $this->table = $this->prefix . 'referal';
        $where = [];
        if(!$all) $where['ruid'] = $uid;
        $where = $this->buildWhere($where);
        $offset = $count * $page;
        $q = "
            SELECT * 
            FROM $this->table
            $where
            order by `id` desc
            limit $count offset $offset
            "; 
        $res = $this->db->get_results($q);
        return $res;
    }


    /**
     * getReferer
     * Получить id реферера указанного реферала
     */
    public function getReferer($uid)
    {
        $this->table = $this->prefix . 'referal';
        $where = [];
        $where['uid'] = (int)$uid;
        $where = $this->buildWhere($where);
        // $offset = $count * $page;
        $q = "
            SELECT ruid
            FROM $this->table
            $where
            "; 
        $res = $this->db->get_var($q);
        // $res = $this->db->get_row($q, ARRAY_A);
        // if($res) $res = (array)$res[0]; else $res = [];
        return $res;
    }
    
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

    /**
     * getCandidatesCount
     * Получить количество кандидатов
     */
    public function getCandidatesCount($page = 0, $count = 1000)
    {
        $where = [];
        $where['approve'] = 0;
        $where = $this->buildWhere($where);
        $this->table = $this->prefix . 'partner';
        $q = "
            SELECT count(*) 
            FROM $this->table
            $where
            "; 
        $cou = $this->db->get_var($q);
        return $cou;
    }


    /**
     * getPartnersCount
     * Получить количество партнёров
     */
    public function getPartnersCount($page = 0, $count = 1000)
    {
        $where = [];
        $where['approve'] = 1;
        $where = $this->buildWhere($where);
        $this->table = $this->prefix . 'partner';
        $q = "
            SELECT count(*) 
            FROM $this->table
            $where
            "; 
        $cou = $this->db->get_var($q);
        return $cou;
    }


    /**
     * getReferalsCount
     * Получить количество рефералов
     */
    public function getReferalsCount($uid, $all = false)
    {
        $where = [];
        if(!$all) $where['ruid'] = $uid;
        $where = $this->buildWhere($where);
        $this->table = $this->prefix . 'referal';
        $q = "
            SELECT count(*) 
            FROM $this->table
            $where
            "; 
        $cou = $this->db->get_var($q);
        return $cou;
    }


    /**
     * validateReferalCode
     * Проверка существования реферального кода
     */
    public function validateReferalCode($code){
        global $reg_log;
        if(!$reg_log) $reg_log = [];
        // $reg_log['m validateReferalCode $code 14 ? '.__LINE__.' '.microtime()] = var_export('>>'.$code.'<<',1);

        // $reg_log['m validateReferalCode $code ? '.__LINE__.' '.microtime()] = var_export('>>'.$code.'<<',1);
        // $reg_log['m validateReferalCode $code 2 ? '.__LINE__.' '.microtime()] = var_export('>>'.$code.'<<',1);

        if(!$code) return false;
        $where = [];
        // $reg_log['m validateReferalCode $code 3 ? '.__LINE__.' '.microtime()] = var_export('>>'.$code.'<<',1);
        $where['code'] = $code;
        // $reg_log['m validateReferalCode $code 4 ? '.__LINE__.' '.microtime()] = var_export('>>'.$code.'<<',1);
        // $reg_log['m validateReferalCode $where '.__LINE__.' '.microtime()] = $where;
        $where = $this->buildWhere($where);
        $this->table = $this->prefix . 'partner';
        $q = "
            SELECT uid 
            FROM $this->table
            $where
            "; 
        $uid = $this->db->get_var($q);
        // $reg_log['m validateReferalCode $q '.__LINE__.' '.microtime()] = $q;
        // $reg_log['m validateReferalCode $uid '.__LINE__.' '.microtime()] = $uid;
        return $uid;
    }
    
    // ==============================


    public function _buildWhere_equal($key, $value, $eq){
        // $f = [$key, $value];
        // addMess([$key, $value, $eq],'$key, $value, $eq');
        $equal = '=';
        if( $eq ) $equal = $eq;

        if( $key == 'from' ) $key = 'created';
        if( $key == 'to' ) $key = 'created';

        if( $key == 'paid_from' ) $key = 'paid';
        if( $key == 'paid_to' ) $key = 'paid';

        if( $key == 'b_paid_from' ) $key = 'b.paid';
        if( $key == 'b_paid_to' ) $key = 'b.paid';

        // if( $eq = 'like' ) $equal = $eq;
        $w = '`' . $key . '` ' . $equal . ' %s';
        $w = '' . $key . ' ' . $equal . ' %s';
        return $w;
        // return '`' . $key . '` = \'' . $value . '\'';
    }


    public function buildWhere_equal($where=[], $equal=[]){
        $_equal = [];
        // addMess($equal,'$equal');
        foreach($where as $k=>$v){ $_equal[$k]='='; if(isset($equal[$k])) $_equal[$k] = $equal[$k]; }
        // addMess($_equal,'$_equal');
        $where = array_map([$this, '_buildWhere_equal'],
            array_keys($where), array_values($where), array_values($_equal));
        $where = implode(' and ', $where);
        if($where) $where = 'where '.$where;
        return $where;
    }

    // ==============================


    /**
     * getSales
     * Получить список продаж
     */
    public function getSales($page = 0, $count = 1000, $_where = [], $equal = [])
    {
        // $this->prefix = $this->db->prefix . 'lhc_';
        $table = $this->prefix . 'bill';
        $tableuser = $this->db->prefix . 'users';
        $tablepartner = $this->prefix . 'partner';
        // $out = [];
        // array_push($out, 'get Logs');
        // $q = "
        //     SELECT * 
        //     FROM $this->table
        //     WHERE post_status = 'publish' 
        //     AND post_type = 'memo'
        //     "; // page
        
        $offset = $count * $page;

        // $_where['b.uid'] = 'u.ID';

        // addMess($equal,'$equal');
        $where = $this->buildWhere_equal($_where, $equal);
        // addMess($_where,'$_where');
        // addMess($where,'$where');
        // addMess(array_values( $_where ),'array_values');
        $q = "
            SELECT b.* , u.user_email, p.phone, p.name, p.surname
            FROM $table as b
            left join $tableuser as u on b.uid = u.ID
            left join $tablepartner as p on b.uid = p.uid
            $where
            order by `id` desc
            limit $count offset $offset
        "; 
        $q = "
            SELECT *
            FROM $table
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
        $log = $this->db->get_results($q, ARRAY_A);
        return $log;
    }


    /**
     * getSales
     * Получить сумму продаж
     */
    public function getSalesSum( $_where = [], $equal = [])
    {
        // $this->prefix = $this->db->prefix . 'lhc_';
        $table = $this->prefix . 'bill';
        $tableuser = $this->db->prefix . 'users';
        $tablepartner = $this->prefix . 'partner';
        // $out = [];
        // array_push($out, 'get Logs');
        // $q = "
        //     SELECT * 
        //     FROM $this->table
        //     WHERE post_status = 'publish' 
        //     AND post_type = 'memo'
        //     "; // page
        
        // $offset = $count * $page;

        // $_where['b.uid'] = 'u.ID';

        // addMess($equal,'$equal');
        $where = $this->buildWhere_equal($_where, $equal);
        // addMess($_where,'$_where');
        // addMess($where,'$where');
        // addMess(array_values( $_where ),'array_values');
        $q = "
            SELECT b.* , u.user_email, p.phone, p.name, p.surname
            FROM $table as b
            left join $tableuser as u on b.uid = u.ID
            left join $tablepartner as p on b.uid = p.uid
            $where
            order by `id` desc
            limit $ count offset $ offset
        "; 
        $q = "
            SELECT sum(`sum`) as 'sum', sum(`rfee`) as 'rfee'
            FROM $table
            $where
            "; 
        $prep = array_values( $_where );
        $args = [$q];
        if(count($prep))  $args[] = $prep;
        $args = [$q, $prep];

        // addMess($args,'$args');
        // addMess(array_values( $_where )??null,'$args 2');
        if(count($_where)) $q = $this->db->prepare( ...$args );
        // addMess($q,'$q');
        // $log = $this->db->get_row($q);
        $log = $this->db->get_row($q, ARRAY_A);
        return $log;
    }


    /**
     * getSalesCount
     * Получить количество продаж
     */
    public function getSalesCount($_where = [], $equal = [])
    {
        $table = $this->prefix . 'bill';
        $tableuser = $this->db->prefix . 'users';
        $tablepartner = $this->prefix . 'partner';
        $where = $this->buildWhere_equal($_where, $equal);
            // left join $tablepartner as p on b.uid = p.uid
        $q = "
            SELECT count(*) 
            FROM $table
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
     * getBillListUnique
     * получить список уникальных значений
     * по имени колонки
    */
    public function getBillListUnique($name, $where=[]){
        $table = $this->prefix . 'bill';
        $fields = [
            'uid',
            'ruid',
            'status',
            'pid',
            'goods',
        ];
        if(!in_array($name, $fields)) return [];
        $where = $this->buildWhere($where);
        $q = "
            SELECT distinct $name
            FROM $table
            $where
        ";
        // addMess($q,'$q');
        $cou = $this->db->get_col($q);
        return $cou;
    }

    // ==============================


    public function setWhere($where = []){
        $this->where = [];
    }


    public function resetWhere(){
        $this->where = [];
    }

    static function init(){
        global $partnerClass;
        if( !$partnerClass ){
            $partnerClass = new PartnerClass();
        }
    }
}

//============================


add_action( 'init', ['PartnerClass', 'init']);


function wsd_partner_add_candidate($uid, &$data){
    global $partnerClass;
    if( !$partnerClass ){
        $partnerClass = new PartnerClass($sys);
    }
    // $partnerClass->init();
    $partnerClass->addCandidat($uid, $data);
    // return $partnerClass->event($event, $data);
}


function addTestCandidate(){
    $uid = 10;
    $data = [];
    $data['name'] = 'Name';
    $data['surname'] = 'Surname';
    $data['mail'] = 'Mail';
    $data['phone'] = 'Phone';
    wsd_partner_add_candidate($uid, $data);
}


function lhc_get_referals($uid = 0, $page = 0, $count = 1000, $all = false){
    global $partnerClass;
    if(!$uid) $uid = get_current_user_id();
    $list = $partnerClass->getReferals($uid, $page, $count, $all);
    return $list;
}


function lhc_get_referalsCou($uid=0, $all = false){
    global $partnerClass;
    if(!$uid) $uid = get_current_user_id();
    $list = $partnerClass->getReferalsCount($uid, $all);
    return $list;
}


function lhc_add_referal($uid, $code){
    global $partnerClass;
    if(!$uid) $uid = get_current_user_id();
    $res = $partnerClass->addReferal($uid, $code);
    return $res;
}


function lhc_get_referer($uid){
    global $partnerClass;
    // if(!$uid) $uid = get_current_user_id();
    $res = $partnerClass->getReferer($uid);
    return $res;
}


/**
 * 
*/
function lhс_validateReferalCode($code){
    global $partnerClass;
    return $partnerClass->validateReferalCode($code);
}


/**
 * 
*/
function lhc_get_partners($page = 0, $count = 1000, $where = [], $equal=[] ){
    global $partnerClass;
    return $partnerClass->getPartners($page, $count, $where, $equal);
}


/**
 * 
*/
function lhc_get_partner($id){
    global $partnerClass;
    return $partnerClass->getPartner($id);
}


/**
 * 
*/
function lhc_get_partner_userid($uid){
    // wsd_addlog('payment', 'calcPercent', ['lhc_get_partner_userid'=>$uid], 'info');
    global $partnerClass;
    $par_row = $partnerClass->getPartnerUser($uid);
    // wsd_addlog('payment', 'calcPercent', ['$par_row'=>$par_row], 'info');
    return $par_row;
}


/**
 * 
*/
function lhc_update_candidate($id, $data){
    global $partnerClass;
    return $partnerClass->setPartner($id, $data);
}


function lhc_setPartnerAmoId($pid, $aid){
    global $partnerClass;
    return $partnerClass->setPartnerAmoId($pid, $aid);
}


/**
 * 
*/
function lhc_get_partnersCou(){
    global $partnerClass;
    return $partnerClass->getPartnersCount();
}


/**
 * 
*/
function lhc_get_candidates($page = 0, $count = 1000){
    global $partnerClass;
    return $partnerClass->getCandidates($page, $count);
}


/**
 * 
*/
function lhc_get_candidatesCou(){
    global $partnerClass;
    return $partnerClass->getCandidatesCount();
}


/**
 * 
*/
function lhc_get_sales( $page = 0, $count = 1000, $where = [], $equal=[] ){
    global $partnerClass;
    return $partnerClass->getSales($page, $count, $where, $equal);
}


/**
 * 
*/
function lhc_get_sales_sum( $where = [], $equal=[] ){
    global $partnerClass;
    return $partnerClass->getSalesSum($where, $equal);
}


/**
 * 
*/
function lhc_getsalesCou( $where = [], $equal=[] ){
    global $partnerClass;
    return $partnerClass->getSalesCount($where, $equal);
}


/**
 * 
*/
function lhc_get_salesListUnique( $name, $where=[] ){
    global $partnerClass;
    return $partnerClass->getBillListUnique($name, $where);
}


//============================

function reply() {
    if ( isset($_POST['smthing']) ) {  // проверяем, задан ли параметр POST smthing
        $smthing = $_POST['smthing'];  // берем его значение
        $smthing = json_decode($smthing);  // декодируем json
        if ($smthing->type == 'good') {  // если тип в json = 'good' (ну, типа, хороший запрос)
            wp_send_json_success('Answer to good request');  // посылаем назад json с правильным сообщением 
        }
        else {
            wp_send_json_error();  // иначе - просто посылаем ))
        }
    }
}
add_action( 'init', 'reply' );  // так в WP добавляется хук на этапе инициализации

//============================