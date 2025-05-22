<?php
/*
File: CAClass.php
Descriptin: Custom Account Class
Author: WSD
Created: 2024.09.06 10:25

Before cleaning, this file was contained 2297 rows
*/
if ( ! defined( 'ABSPATH' ) ) { echo 'Silence gold.'; exit(403); }

if (!session_id()) {
    session_start();
}

include 'extend/LoadFilesTrait.php';
include 'extend/ApiCdekTrait.php';
include 'jobs/LoadFileClass.php';

// add_action('wp_loaded', 'paylogtest', 10, 3);
// function paylogtest(){
// if($this->log) wsd_addlog('all', 'all_event2', $_data=['get'=>$_GET, 'post'=>$_POST], 
// $status = 'info', $file = __FILE__, __LINE__);  
// }

// echo 'curl-test:OK2';
// exit(200);
class CAClass{

    use LoadFilesTrait;
    use ApiCdekTrait;

    public int $pid; // page id
    public ?string $auid; // author user id
    public $log = false;
    public $errorlog = false;

    public $settings = [];

    public function __construct(int $pid, ?string $auid, bool $doprocess=true)
    {
        $lhc_setting_name = 'lhc_setting';
        $lhc_settings = get_option( $lhc_setting_name );
        $this->settings = $lhc_settings;

        global $values, $errors, $_def_values;
        global $has_error;
        
        $this->pid = $pid;
        $this->auid = $auid;
        if($doprocess) $this->process();
    }


    function process(){
        $this->actions();
    }


    function actions(){
        add_action('lhc_action_todo', [$this, 'action_todo'], 10, 4 );
        // template_redirect вызывается после построения страницы до показа
        add_action('template_redirect', [$this, 'include_create_memory_page_template_process'], 10, 3 );
        // add_action('init', 'handle_delete_memory_page');
        add_action('wp_loaded', [$this, 'handle_delete_memory_page'], 10, 3);
        // add_action('init', 'handle_toggle_status_page');
        add_action('wp_loaded', [$this, 'handle_toggle_status_page'], 10, 3);
        // add_action('init', 'handle_toggle_public_status_page', 10, 3);
        add_action('wp_loaded', [$this, 'handle_toggle_public_status_page'], 10, 3);
        // add_action('init', 'handle_set_memory_page_password');
        add_action('wp_loaded', [$this, 'handle_set_memory_page_password'], 10, 3);
        // add_filter( 'post_type_link', [$this, 'modify_memo_permalink'], 10, 3 ); // memo addr replace
        add_filter('template_include', [$this, 'restrict_memory_page_access']);
        add_filter('upload_dir', [$this, 'upload_dir']);
    }

    function upload_dir($pathes){
        $path = $pathes['baseurl'];
        $path = parse_url($path, PHP_URL_PATH);
        $pathes['baseurlpath'] = $path;
        return $pathes;
    }


    // Подключение файла create-memory-page.php
    function include_create_memory_page_template_process() {
        global $values, $errors, $_def_values;
        global $has_error;
        // addSys(__line(false));

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
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_memory_page'])) {
            $this->createMemoryPage();
        }
        // addMess(1,'chch');
        if( $_SERVER['REQUEST_METHOD'] === 'POST' ) {

            // addMess(1,'isPOST');
            // api shluse
            $this->todo();
            $inv_id = filter_input(INPUT_POST, 'InvId', FILTER_UNSAFE_RAW);
            if($inv_id){
                $this->payEvent();
            }
        }
        // addMess('test =====');
        // if($this->log) wsd_addlog('all', 'all_event', $_data=['get'=>$_GET, 'post'=>$_POST], 
        // $status = 'info', $file = __FILE__, __LINE__);  
        
        // remember referal code
        $code = filter_input(INPUT_GET, 'ref');
        if($code){
            $validCode = lhс_validateReferalCode($code);
            if($validCode){
                global $refcodequery;
                setcookie('referalcode', $code, time() + (86400 * 30), "/");
                $refcodequery = $code;
            }
        }
    }


    /**
     * 
     * $todo  form
     * $type  bill
     * $task  form
     * $data  form args
     */
    public function action_todo($todo='', $type='', $task='', $data=[]){
        // wsd_addlog('payment', 'action_todo', $data, 'info');
        wsd_addlog('payment', 'action_todo', ['todo'=>$todo, 'type'=>$type, 'task'=>$task, 'data'=>$data], 'info');
        switch($todo){
            case 'tarif-up':
                $res = $this->doUpdateTarif($todo, $type, $task, $data);
                break;
        }
    }

    public function doUpdateTarif($todo='', $type='', $task='', $data=[]){
        // wsd_addlog('payment', 'action_todo', $data, 'info');
        // wsd_addlog('payment', 'action_todo', ['todo'=>$todo, 'type'=>$type, 'task'=>$task, 'data'=>$data], 'info');
        switch($type){
            case 'tarif-up':
                // wsd_addlog('payment', 'action_todo', ['task'=>$task], 'info');
                switch($task){
                    case 'update':
                        // wsd_addlog('payment', 'action_todo', ['do'=>$task], 'info');
                        $pid = $data['pid'];
                        $tarif = $data['tarif'];
                        // wsd_addlog('payment', 'action_todo', ['st 1'=>$task], 'info');
                        $oldtarif = get_post_meta($pid, 'tarif', 1);
                        $from_tarifs = ['econ', 'std', 'max', 'hero'];
                        $from_tarifs = ['econ'];
                        // wsd_addlog('payment', 'action_todo', ['st 2'=>$task], 'info');
                        if(!$oldtarif) $oldtarif='econ';
                        // wsd_addlog('payment', 'action_todo', ['st 3'=>$task], 'info');
                        if(!in_array($oldtarif, $from_tarifs)){
                            wsd_addlog('payment', 'action_todo', ['st 4'=>$task], 'info');
                            wsd_addlog('payment', 'action_todo', ['pid'=>$pid, 'old_tarif'=>$oldtarif, 'new_tarif'=>$tarif,
                                'status'=>'filed'], 'error');
                        }else{
                            // wsd_addlog('payment', 'action_todo', ['st 5'=>$task], 'info');
                            update_post_meta($pid, 'tarif', $tarif);
                            // wsd_addlog('payment', 'action_todo', ['st 6'=>$task], 'info');
                            wsd_addlog('payment', 'action_todo', ['pid'=>$pid, 'old_tarif'=>$oldtarif, 'new_tarif'=>$tarif], 'success');
                        }
                        // wsd_addlog('payment', 'action_todo', ['done'=>$task], 'info');
                        break;
                }
                break;
        }

    }


    function todo(){
        $todo = filter_input(INPUT_POST, 'todo', FILTER_UNSAFE_RAW);
        // addMess($todo,'$todo');
        // addMess($_GET,'get');
        // addMess($_POST,'post');
        // addMess($_REQUEST,'REQUEST');
        // addMess($_SERVER,'server');
        $res = '';
        if($todo){
            switch($todo){
                case 'tarif-up':
                case 'qr-query':
                    $res = $this->modalProcessQRQuery();
                    break;
                case 'page-pass':
                    $this->modalProcessPagePass();
                    break;
                case 'become-partner':
                    $this->modalProcessBecomePartner();
                    break;
                case 'to-partner':
                    $this->processUpdateCandidate();
                    break;
                case 'curl-test':
                    $this->curlTestUnswer();
                    break;
                case 'add-file':
                    $this->loadMemoryFile();
                    break;
                case 'cdek-api':
                    $this->cdek_api();
                    break;
            }
            $return = filter_input(INPUT_POST, 'return', FILTER_UNSAFE_RAW);
            if($return){
                switch($return){
                    case 'account':
                    case '/account/':
                        wp_redirect(URL_ACCOUNT); // site_url('/account/')
                        // if($res!='') echo json_encode($res);
                        exit;
                    break;
                    case 'cands':
                        wp_redirect(URL_ACCOUNT.'?acctab=cands'); // site_url('/account/')
                        exit;
                    case 'parts':
                        wp_redirect(URL_ACCOUNT.'?acctab=parts'); // site_url('/account/')
                        exit;
                    break;
                }
            }
        }
    }

    public function payEvent(){
        $inv_id = filter_input(INPUT_POST, 'InvId', FILTER_UNSAFE_RAW);

        if($this->log) wsd_addlog('payment', 'payEvent', $_data=['get'=>$_GET, 'post'=>$_POST], 
        $status = 'info', $file = __FILE__, __LINE__);
    }


    /**
     * тестовый обработчик запросов робокассы
    */
    public function curlTestUnswer(){
        echo 'OK:curl-test';
        exit(200);
    }

    public function removeMemoryFile(){
    }


    // Обработчик удаления страниц памяти
    function handle_delete_memory_page() {
        if (isset($_POST['delete_memory_page']) && is_user_logged_in()) {
            if (!is_user_logged_in()) {
                wp_redirect(URL_LOGIN ); // site_url('/login/')
                exit;
            }
            $post_id = intval($_POST['delete_memory_page']);
            $user_id = get_current_user_id();
    
            // Проверяем, действительно ли страница памяти принадлежит текущему пользователю
            $memory_pages = get_user_meta($user_id, 'memory_pages', true);
            $ids = [];
            foreach($memory_pages as $page_url){
                $id = url_to_postid($page_url);
                $ids[]=$id;
            }
            if (in_array($post_id, $ids)) {
                $key = array_search(get_permalink($post_id), $memory_pages);
                if ( $key !== false) {
                    // Удаляем страницу памяти
                    wp_delete_post($post_id, true);
        
                    // Удаляем URL из метаданных пользователя
                    unset($memory_pages[$key]);
                    update_user_meta($user_id, 'memory_pages', $memory_pages);
        
                    // Перенаправление для предотвращения повторной отправки формы
                    wp_redirect(URL_ACCOUNT); // site_url('/account/')
                    exit;
                }
            }
        }
    }


    // ==============================
    // Обработчик изменения статуса публикации страниц памяти
    function handle_toggle_status_page() {
        // addMess('handle_toggle_status_page 1');
        if (isset($_POST['toggle_status_page']) && is_user_logged_in()) {
            // addMess('handle_toggle_status_page 2');
            if (!is_user_logged_in()) {
                wp_redirect(URL_LOGIN ); // site_url('/login/')
                exit;
            }
            // addMess($_POST, '$_POST');
            $post_id = intval($_POST['toggle_status_page']);
            $user_id = get_current_user_id();
            // addMess($post_id, '$post_id');
            // addMess($user_id, '$user_id');
            // $memory_page_url2 = get_permalink($post_id);
            // addMess($memory_page_url2, '$memory_page_url2');
    
            // Проверяем, действительно ли страница памяти принадлежит текущему пользователю
            $memory_pages = get_user_meta($user_id, 'memory_pages', true);
            // addMess($memory_pages, '$memory_pages');
            // addMess(($key = array_search(get_permalink($post_id), $memory_pages)) !== false, '!== false');
            
                // $post_id = url_to_postid($page_url);
                // addMess($memory_pages, '$memory_pages');
    
            $ids = [];
            $perms=[];
            foreach($memory_pages as $page_url){
                $id = url_to_postid($page_url);
                $ids[]=$id;
                // $perms[url_to_postid($page_url)] = (($page_url));
                // $perms[$page_url] = ((url_to_postid($page_url)));
            }

            // addMess($perms, 'ids '.count($perms));
            // $perms=[];
            // foreach($memory_pages as $page_url){
            //     // $post_id = url_to_postid($page_url);
            //     $id = url_to_postid($page_url);
            //     $perms[$id] = get_permalink($id);
            // }
            // addMess($perms, '$perms '.count($perms));
            // is_public


            $public = filter_input(INPUT_POST, 'post_status', FILTER_UNSAFE_RAW);
            $pid = filter_input(INPUT_POST, 'toggle_status_page', FILTER_VALIDATE_INT);
            $pids = $this->getUserMemoryPageIds();
            $is_author = in_array($pid, $pids);
            
    
            if ($pid && $is_author) {
            // if (in_array($post_id, $ids)) {
                // if (($key = array_search(get_permalink($post_id), $memory_pages)) !== false) {
                // addMess($key, '$key');
                
                // Меняем статус публикации страницы памяти
                // $post_status = isset($_POST['post_status']) && $_POST['post_status'] === 'publish' ? 'publish' : 'draft';
                $post_status = $public === 'publish' ? 'publish' : 'draft';
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_status' => $post_status,
                ));
                // addMess($post_status, '$post_status');

                if ($post_status === 'publish') {
                    addSys('Опубликована страница с id: '.$pid);
                }else{
                    addSys('Снята с публикации страница с id: '.$pid);
                }
    
                // Перенаправление для предотвращения повторной отправки формы
                wp_redirect(URL_ACCOUNT); // site_url('/account/')
                exit;
            }
        }
    }


    function getUserMemoryPages(){
        $user = wp_get_current_user();
        if(!$user) return [];
        $args = array(
            'post_type' => 'memory',
            // 'post_type' => 'page',
            'author' => $user->ID,
            // 'post_status' => 'future',
            'post_status' => [
                'publish',
                'pending',
                'draft',
                'auto-draft',
                'future',
                'private',
                'inherit',
                'trash'
            ],
            // "author_name" => "john",
            // 'parameter1' => 'value',
            // 'parameter2' => 'value',
            // 'parameter3' => 'value',
        // 'orderby' => 'comment_count'
            'posts_per_page' => -1,
        );
        // The Query
        $the_query = new WP_Query( $args );
        // The Loop
        $cou = 0;
        $pages = [];
        if ( $the_query->have_posts() ) {
        //     echo '<ul>';
            while ( $the_query->have_posts() ) {
                $cou++;
                $the_query->the_post();
                $post_id = get_the_ID();
                $page_url = get_permalink($post_id);
                $pages[$post_id] = $page_url;
            }
        }
        wp_reset_postdata();
        return $pages;
    }

    function getUserMemoryPageIds(){
        return array_keys( $this->getUserMemoryPages() );
    }


    // ==============================
    // Обработчик изменения статуса публичности страницы памяти
    function handle_toggle_public_status_page() {

        // addMess($_POST, '$_POST');
            // addMess('handle_toggle_public_status_page 1');
        if (isset($_POST['toggle_public_status']) && is_user_logged_in()) {
            // addMess('handle_toggle_public_status_page 2');
            if (!is_user_logged_in()) {
                wp_redirect(URL_LOGIN ); // site_url('/login/')
                exit;
            }

            $post_id = intval($_POST['toggle_public_status']);
            $user_id = get_current_user_id();
            // addMess($post_id, '$post_id');
            // addMess($user_id, '$user_id', 'mess');

            // $memory_page_url2 = get_permalink($post_id);
            // addMess($memory_page_url2, '$memory_page_url2');
    
            // Проверяем, действительно ли страница памяти принадлежит текущему пользователю
            $memory_pages = get_user_meta($user_id, 'memory_pages', true);
            // addMess($memory_pages, '$memory_pages');
            // addMess(get_post_permalink($post_id), 'get_post_permalink($post_id)');
            // addMess(get_permalink($post_id), 'get_permalink($post_id)');
            
            // addMess(($key = array_search(get_permalink($post_id), $memory_pages)) !== false, '!== false');
            
            // addMess($key, '$key');


            $public = filter_input(INPUT_POST, 'is_public', FILTER_UNSAFE_RAW);
            $pid = filter_input(INPUT_POST, 'toggle_public_status', FILTER_VALIDATE_INT);
            $pids = $this->getUserMemoryPageIds();
            $is_author = in_array($pid, $pids);

            // addMess($pids, '$pids');
            // addMess($is_author, 'IS author', 'mess');


            // addMess($key, '$key');
    
            // $perms=[];
            // foreach($memory_pages as $page_url){
            //     // $perms[url_to_postid($page_url)] = (($page_url));
            //     $perms[$page_url] = ((url_to_postid($page_url)));
            // }
            // addMess($perms, 'ids '.count($perms));
            // $perms=[];
            // foreach($memory_pages as $page_url){
            //     // $post_id = url_to_postid($page_url);
            //     $id = url_to_postid($page_url);
            //     $perms[$id] = get_permalink($id);
            // }
            // addMess($perms, '$perms '.count($perms));
    
            if ($pid && $is_author) {
            // if (($key = array_search(get_permalink($post_id), $memory_pages)) !== false) {
                

                // $is_public = isset($_POST['is_public']) && $_POST['is_public'] === 'yes' ? 'yes' : 'no';
                $is_public = $public === 'yes' ? 'yes' : 'no';
                
                // addMess($is_public, 'is_public');
                update_post_meta($post_id, '_is_public', $is_public);
    
                if ($is_public === 'yes') {
                    delete_post_meta($post_id, '_memory_page_password');
                }else{
                    
                }
                if ($is_public === 'yes') {
                    addSys('Снято ограничеие доступа к странице с id: '.$pid);
                }else{
                    addSys('Ограничен доступ к странице с id: '.$pid);
                }
    
                // Перенаправление для предотвращения повторной отправки формы
                // addMess('exit', 'wp_redirect ');
                wp_redirect(URL_ACCOUNT); // site_url('/account/')
                exit;
            }
        }
    }


    // ==============================
    // Подача заявки на партнёрство
    function modalProcessBecomePartner(){
        if (!is_user_logged_in()) {
            wp_redirect(URL_LOGIN ); // site_url('/login/')
            exit;
        }

        $user_id = get_current_user_id();
        $bePartnerSent = get_user_meta($user_id, 'be-partner-sent', 1);
        if ($bePartnerSent) {
            wp_redirect(URL_ACCOUNT ); // site_url('/login/')
            exit;
        }
        $user = get_userdata($user_id);

        $uid = filter_input(INPUT_POST, 'uid', FILTER_VALIDATE_INT);
        $name = filter_input(INPUT_POST, 'name', FILTER_UNSAFE_RAW);
        $surname = filter_input(INPUT_POST, 'surname', FILTER_UNSAFE_RAW);
        $mail = filter_input(INPUT_POST, 'mail', FILTER_UNSAFE_RAW);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_UNSAFE_RAW);
        $accept = filter_input(INPUT_POST, 'accept', FILTER_UNSAFE_RAW);

        $phone = get_user_meta($user_id, 'phone', 1);

        $data = [];
        $data['user_id'] = $user_id;
        $data['name'] = $name;
        $data['surname'] = $surname;
        $data['mail'] = $user->user_email;
        $data['phone'] = $phone;
        $data['todo'] = 'curl-test';

        wsd_partner_add_candidate($user_id, $data);
        // send data to amoCrm
        // wsd_crm_event('partner-candidate', $data, 'amo'); // перенесено в одобрение заявки
        $res = $this->sendPartnershipQueryToAdmin($data);
        
        // !
        // regist user query try
        if($res) update_user_meta($user_id, 'be-partner-sent', true);

        wp_redirect(URL_ACCOUNT); // site_url('/account/')
        exit;
    }


    /**
     * отправка запроса на партнёрство на почту админа
     */
    function sendPartnershipQueryToAdmin($args){
        $out = 0;
        // addMess('sendQrQuery','sendQrQuery');

        $adm_user = get_userdata(1);
        // addMess($user->user_email, 'email '.$user->id);
        // addMess($user, '$user');

        // $format = 'There are %d monkeys in the %s';
        // echo sprintf($format, $num, $location);
        $fs = [
            'qr-1' => 'Заявка на партнерство',
            'qr-2' => $args['user_id'],
            'qr-3' => $args['name'],
            'qr-4' => $args['surname'],
            'qr-5' => $args['mail'],
            'qr-6' => $args['phone'],
        ];
        $mess = [
            sprintf('%s.', $fs['qr-1']),
            sprintf('From site: %s', get_bloginfo('url') ),
            // sprintf('page_id: %s', $fs['qr-2']),
            sprintf('Date: %s', current_datetime()->format("Y-m-d H:i")),
            sprintf('User ID: %s', $fs['qr-2']),
            sprintf('User name: %s', $fs['qr-3']),
            sprintf('User surname: %s', $fs['qr-4']),
            sprintf('Email: %s', $fs['qr-65']),
            sprintf('Phone: %s', $fs['qr-6']),
        ];
        // addMess($mess,'$mess');
        $mess = implode("\n",$mess);
        // addMess([$mess],'$mess');

        $to = [$adm_user->user_email];
        $subject = 'Заявка на партнерство';
        $message = $mess;
        $headers = [];
        $attachments = [];

        if($this->log) addMess([$to, $subject, $message, $headers, $attachments], 'wp_mail: $to, $subject, $message, $headers, $attachments');

        if($this->isrelis){
            $res = wp_mail( $to, $subject, $message, $headers, $attachments );
            if($res) $out = 1;
        }

        return $out;
    }


    /*
    ... huge part of business ligic is deleted
    */


    public int $error_cou = 0;
    public Array $errors = [];
    public int $pages_limit = 1;
    public int $photos_cou = 10;
    public int $videos_cou = 0;
    public int $audio_cou = 0;
    public int $relation_cou = 1000;
    public bool $is_new = false;
    public bool $is_hero = false;
    public string $tarif = 'econ';
    public string $page_url = '';
    public string $qr_url = '';
    public string $ava_url = '';
    public string $latitude = '';
    public string $longitude = '';
    
    /**
     * Создание статьи или извлечение сохранённой.
     * Сохранение основных данных.
     * Summary of saveRequred
     * @return mixed
     */
    function saveRequred(){
        $pid = filter_input(INPUT_POST, 'pid', FILTER_VALIDATE_INT);

        // addMess($pid,'saveRequred $pid 0');
        if($pid){
            // addMess($pid,'saveRequred $pid 1');
            $post = get_post( $pid );
            // addMess($pid,'saveRequred $pid 1');
            if(!$post) return false;
            $user = wp_get_current_user();
            if(!$user) return false;
            $userid = $user->ID;
            $authorid = $post->post_author;

            if(!$userid!=1 && $userid != $authorid) return false;
        }

        $fields =[];

        // Обработка данных формы
        $surname = sanitize_text_field($_POST['info-surname']);
        $name = sanitize_text_field($_POST['info-name']);
        $patronymic = sanitize_text_field($_POST['info-patronymic']);
        $birth_date = sanitize_text_field($_POST['info-date-from']); // birth_date
        $death_date = sanitize_text_field($_POST['info-date-to']); // death_date
        
        $err = $this->checkRequireds($pid);
        // addMess($err,'saveRequred $err');
        // если есть все обязательные данные, сохраняем
        if(!$err){
            $pid = $this->saveRequireds($pid);
            // addMess($pid,'saveRequred $pid 2');
            $this->saveAva($pid);
        }else{
            $this->error_cou++;
        }
        return $pid;
    }



    /*
    ... huge part of business ligic is deleted
    */



    /**
     * Сохранение содержимого страницы
     * Summary of savePostContent
     * @param mixed $pid
     * @return void
     */
    function savePostContent($pid){
        if(!$pid) return;
        
        // Создание страницы памяти
        $post_content = '<img src="' . esc_url($this->ava_url) . '" alt="Фото">';
        $post_content .= '<p>' . esc_html($surname) . ' ' . esc_html($name) . ' ' . esc_html($patronymic) . '</p>';
        $post_content .= '<p>' . esc_html($birth_date) . ' — ' . esc_html($death_date) . '</p>';
    
        if (!empty($gallery_urls)) {
            $post_content .= '<div class="gallery">';
            foreach ($gallery_urls as $key => $url) {
                $thumbnail_url = $this->gallery_thumbnail_urls[$key];
                $post_content .= '<a href="' . esc_url($url) . '" data-lightbox="memory-gallery" class="gallery-item"><img src="' . esc_url($thumbnail_url) . '" alt="Gallery Photo"></a>';
            }
            $post_content .= '</div>';
        }
    
        if (!empty($video_urls)) {
            $post_content .= '<div class="videos">';
            foreach ($video_urls as $url) {
                // Используем короткий код для вставки видео
                $post_content .= '[video src="' . esc_url($url) . '" controls="true"]';
            }
            $post_content .= '</div>';
        }
        
        // Добавление QR-кода и кнопки для скачивания в PDF в контент страницы памяти
        $post_content .= '<img src="' . esc_url($this->qr_url) . '" alt="QR Code">';
        $post_content .= '<a href="' . esc_url($this->qr_url) . '" download>Скачать QR-код в PDF</a>';
        
        // Обновление контента страницы памяти
        // wp_update_post(array(
        //     'ID' => $post_id,
        //     'post_content' => $post_content
        // ));
        wp_update_post(array(
            'ID' => $pid,
            'post_content' => $post_content
        ));
    }
    

    /**
     * блокироваине дублей запросов
     */
    function mtiha(){
        $ret = false;
        if(!array_key_exists('mtiha', $_SESSION)){
            $_SESSION['mtiha']=[];
        }
        $mtiha_a = $_SESSION['mtiha'];
        $mtiha = filter_input(INPUT_POST, 'mtiha', FILTER_UNSAFE_RAW);
        if($mtiha){
            if(in_array($mtiha, $mtiha_a)) $ret = true;
            // $mtiha_a[$mtiha] = time();
            $mtiha_a[time()] = $mtiha;
            $_SESSION['mtiha'] = $mtiha_a;
            if($ret){
                $t = [];
                foreach($mtiha_a as $k=>$v){
                    $d = date('m-d H:i:s', $k);
                    $t[$d]=$v;
                }
                if($this->log) addMess($mtiha,'mtiha','sys');
                if($this->log) addMess($t,'mtiha','sys');
            }
        }
        
        return $ret;
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
     * Основной метод обработки запросов
     */
    function createMemoryPage(){
        if($this->mtiha()) {
            addSys('mtiha');
            return;
        }
        global $values, $errors, $_def_values;
        global $has_error;
        if(!$has_error)$has_error=[];
        if(!$errors)$errors=[];
        // addMess('createMemoryPage','createMemoryPage');

        $pid = $this->saveRequred();
        // addMess($pid,'createMemoryPage $pid');
        if(!$pid) return;
        if(!$this->error_cou){
            $this->saveTarif($pid);
            $this->setLimits($pid);
            $this->savePhoto($pid);
            $this->saveVideo($pid);
            $this->saveAudio($pid);
            $this->saveRelations($pid);
            $this->saveCementry($pid);
            $this->saveQr($pid); // Создание и добавление QR кода
            // $this->queryQrCard($pid); // Запрос на заказ пластины с QR кодом
            $this->savePostContent($pid);

            // $dt = current_datetime()->format('Y-m-d_H:i:s');
            $dt = time();
            update_post_meta($pid, 'time_updated', $dt);

            if($this->is_new){
                $tarif = get_post_meta( $pid, 'tarif', 1 );
                $sum = lhc_tarif_sum($tarif);
                if(!$sum){

                    $uid = get_current_user_id();
                    $ruid = 0;

                    $t_title = lhc_tarif_title($tarif);


                    $bill = [];
                    $bill['data_type'] = 'user_create';
                    $bill['goods'] = "Тариф: $t_title";
                    $bill['pid'] = $pid;
                    $bill['uid'] = $uid;
                    $bill['ruid'] = $ruid;
                    
                    global $wpdb;
                    $q = "SELECT ruid FROM wp_lhc_referal where uid = {$uid}";
                    $ruid = (int) $wpdb->get_var($q);
                    if($ruid) $bill['ruid'] = $ruid;
                    if($this->log) wsd_addlog('create', 'amoDataBuild', 
                        ['ruid'=>$ruid, 'query'=>$q, 'bill'=>$bill], 'info');

                    if($partner){
                        
                    }
                    if($bill['ruid']) {
                        $table = $wpdb->prefix . 'lhc_partner';
                        $where = [];
                        $where['uid'] = (int)$ruid;
                        $where['approve'] = 1;
                        $where = $this->buildWhere($where);
                        // if($this->log) wsd_addlog('payment2', 'calcPercent', ['$ruid'=>$ruid,'$ruid2'=>(int)$ruid,'$where'=>$where], 'system');
                        // $offset = $count * $page;
                        $q = "
                            SELECT * 
                            FROM $table
                            $where
                        ";
                        $partner = $wpdb->get_row($q, ARRAY_A);
                        // if($res) $res = (array) $res;
                        // ********
                        $bill['amo_contact_id'] = $partner['amo_contact_id'];
                        // if($this->log) wsd_addlog('payment', 'bill_update', ['stg'=>3, 'query'=>$q, 'partner'=>$partner], 'info');
                        // if($this->log) wsd_addlog('payment', 'bill_update', ['stg'=>3, 'bill'=>$bill], 'info');
                        wsd_crm_event('pymentWithFeeReferer', $bill, 'amo');
                        // if($this->log) wsd_addlog('payment', 'bill_update', ['stg'=>4, 'bill'=>$bill], 'info');
                    }
                    else{
                        // if($this->log) wsd_addlog('payment', 'bill_update', ['stg'=>5, 'bill'=>$bill], 'info');
                        wsd_crm_event('pyment', $bill, 'amo');
                        // if($this->log) wsd_addlog('payment', 'bill_update', 
                        // ['stg'=>6, 'bill'=>$bill], 'info');
                    }
                }
            }
        }

        $errors = array_merge($errors, $this->errors);
        $has_error = array_merge($has_error, $this->errors);
        // $has_error = $this->errors;

    
        $fields = [
            'title' =>'',
            'editor' => '',
            'excerpt' => '',
            'custom-fields' => '',
            'thumbnail' => '',
            'page-attributes' => '',
            
            'tarif' => '',
            'troopstype' => '',
            'epitaph' => '',
            'biography' => '',
            'cemetery-name' => '',
            'cemetery-adres' => '',
            'cemetery-schedule' => '',
            'cemetery-waymark' => '',
        ];
    
    
        $fields2 = [
            'tarif' => '',
            'troopstype' => '',
            'epitaph' => '',
            'biography' => '',
            'cemetery-name' => '',
            'cemetery-adres' => '',
            'cemetery-schedule' => '',
            'cemetery-waymark' => '',
        ];
        
        // Сохранение координат в метаданные поста
        // update_post_meta($post_id, 'is_memory_page', 1);
        // update_post_meta($post_id, '_memory_page_latitude', $latitude);
        // update_post_meta($post_id, '_memory_page_longitude', $longitude);
    
    
    
        // // save text form fields
        // $fields1 = [
        //     'is_memory_page' => 1,
        //     'ava' => $this->ava_url,
        //     'qr' => $this->qr_url,
        //     // 'photo' => implode('||',$gallery_urls),
        //     'video' => implode('||',$video_urls),
        //     'photo' => serialize($gallery_imgs),
        //     // 'video' => serialize($video_urls),
            
        //     'lat' => $latitude,
        //     'long' => $longitude,
        // ];
        // $fields2 = [
        //     'surname' => '',
        //     'name' => '',
        //     'patronymic' => '',
        //     'birth_date' => '',
        //     'death_date' => '',
    
        //     'tarif' => '',
        //     'troopstype' => '',
        //     'epitaph' => '',
        //     'biography' => '',

        //     'cemetery-name' => '',
        //     'cemetery-adres' => '',
        //     'cemetery-schedule' => '',
        //     'cemetery-waymark' => '',
        // ];

        // foreach($fields1 as $k=>$v){
        //     update_post_meta( $pid, $k, $v ); 
        // }
        // foreach($fields2 as $k=>$v){
        //     $v = sanitize_text_field($_POST[$k]);
        //     update_post_meta( $pid, $k, $v ); 
        // }

        $k = 'text-biography';
        $v = sanitize_text_field(strtr($_POST[$k],["\r\n"=>'[br]',"\r"=>'[br]',"\n"=>'[br]']));
        update_post_meta( $pid, $k, $v ); 
    
        flush_rewrite_rules( true ); // !
        
        // Сохранение ссылки на созданную страницу в метаданных пользователя
        // $user_id = get_current_user_id();
        // $user_memory_pages = get_user_meta($user_id, 'memory_pages', true);
        // if (!$user_memory_pages) {
        //     $user_memory_pages = array();
        // }
        // $user_memory_pages[] = $this->page_url;
        // update_user_meta($user_id, 'memory_pages', $user_memory_pages);
        
        // Перенаправление в личный кабинет
        if(!$this->error_cou){
            // if($this->is_new)
            $url = URL_ACCOUNT;
            $next = filter_input(INPUT_POST, 'create_memory_page', FILTER_UNSAFE_RAW);
            if($next == 'next-edit')$url = site_url('/create-memory-page/?pid='.$pid);
            // wp_redirect(URL_ACCOUNT); // site_url('/account/')
            wp_redirect( $url );
            exit;
        }
        if($this->log) addMess($this->error_cou, 'error_cou');
    }
}


function lhc_qr_send_query($pid){
    global $createPage;
    $createPage->queryQrCardPayd($pid);
}