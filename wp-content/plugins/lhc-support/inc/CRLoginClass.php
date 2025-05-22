<?php
/*
File: CRLoginClass.php
Descriptin: CreteRegistration Login Class. Login, register, repass, confirm mail
Author: WSD
Created: 2024.09.06 11:10
*/
if ( ! defined( 'ABSPATH' ) ) { echo 'Silence gold.'; exit(403); }


class CRLoginClass{

    public int $pid; // page id
    public ?string $auid; // author user id


    public function __construct()
    {
        $this->process();
    }


    function process(){
        $this->actions();
    }


    function actions(){
        add_action('init', [$this, 'changePassword'] );
        add_action('init', [$this, 'changeAccountPassword'] );
        add_action('init', [$this, 'custom_handle_registration'] );
        add_action('init', [$this, 'custom_handle_auth']);
        // add_action('wp_loaded', 'custom_handle_auth', 10, 3);
        add_action('init', [$this, 'custom_handle_login']);
        add_action('template_redirect', [$this, 'custom_redirect_logged_in_users']);
    }


    // регистрация
    // Handle registration form submission
    function custom_handle_registration() {
        global $values, $errors, $_def_values, $reg_log;
        if(!$_def_values) $_def_values = [];
        if(!$values) $values = [];
        if(!$errors) $errors = [];
        if(!$reg_log) $reg_log = [];

        // $uid = get_current_user_id();

        // $errors['start']='Начало валидации';

        // $reg_log['test']='no err';
        if (isset($_POST['custom_registration_nonce']) 
            && wp_verify_nonce($_POST['custom_registration_nonce'], 'custom_registration')) {
                // $reg_log['test 2']='no err';
                // $reg_log['test 3']=$_POST;
    
            $email = sanitize_email($_POST['email']);
            $password = sanitize_text_field($_POST['password']);
            // $generated_password = sanitize_text_field($_POST['generated_password']);
            // $phone = sanitize_email($_POST['phone']);

            $phone =  filter_input( INPUT_POST, 'phone');
            $code =  filter_input( INPUT_POST, 'referalcode');
            $accept =  filter_input( INPUT_POST, 'accept');

            if($accept) $values['accept'] = $accept;
            if(!$accept) $errors['accept']='Требуется согласие.';

            // $reg_log['test 4']=[$email,$code,$phone,$password,$generated_password];

            // ===== 
            $validCode = false;
            if($code){
                $values['code'] = $code;
                // $reg_log['lhс_validateReferalCode $code'] = $code;
                $validCode = lhс_validateReferalCode($code);
                // $reg_log['$validCode'] = $validCode;
                if(!$validCode){
                    $errors['code']='Код не найден';
                }else
                {
                    $values['code'] = $code;
                    // $reg_log['lhc_add_referal $code'] = [1, $code];
                    // lhc_add_referal(1, $code); // !!! test
                }
            }
    
            if($email){
                $values['mail'] = $email;
                if (!is_email($email)) {
                    // echo 'Неверный email.';
                    // $reg_log['test 5']='Неверный email';
                    $errors['mail']='Неверный email.';
                    // return;
                }
            }else{
                $errors['mail']='Укажите email.';
            }
    
            $validPhone = $this->validatePhone($phone);
            if($phone){
                $values['phone'] = $phone;
            }
    
            $validPass = $this->validatePassword($password);
            if($password){
                $values['pass'] = $password;
            }
            
            // if (!$validPass) {
            //     // echo 'Неверный email.';
            //     // $reg_log['test 5']='Неверный пароль';
            //     $errors['mail']='Неверный пароль';
            //     return;
            // }

            if($errors) return;
    
            $user_id = wp_create_user($email, $password, $email);
            if (is_wp_error($user_id)) {
                echo $user_id->get_error_message();
                // $reg_log['test 6']=$user_id->get_error_message();
                $errors['user']=$user_id->get_error_message();
                return;
            }
            // $reg_log['test 7']='no err';
    
            update_user_meta($user_id, 'plain_password', $password);
    
            $verification_code = wp_generate_password(6, false, false);
            update_user_meta($user_id, 'verification_code', $verification_code);
            update_user_meta($user_id, 'phone', $phone);
            if($validCode){
                update_user_meta($user_id, 'referer', $validCode);
                update_user_meta($user_id, 'refcode', $code);
                lhc_add_referal($user_id, $code);
            }
    
            $subgect = 'Код подтверждения История-Жизни.рф';
            $subgect = 'Код подтверждения';
            $message = 'Ваш код подтверждения: ' . $verification_code;
            wp_mail($email, $subgect, $message);
    
            wp_redirect(site_url('/auth/'));
            exit;
        }
        // $errors['test 30']='no err';
    }


    public function validatePhone($phone){
        global $errors;
        $phone = preg_replace("/[^\d]/", "", $phone);
        $err=[];
        if(!$phone){
            $err[] = 'Укажите телефон.';
        }
        if( strlen($phone) < 8 ){
            $err[] = 'Телефон не может быть меньше 8 цифр. ('.strlen($phone).')';
        }
        if( strlen($phone) > 12 ){
            $err[] = 'Телефон не может быть больше 12 цифр. ('.strlen($phone).')';
        }
        if($err){
            if($phone) $err[]=$phone;
            $errors['phone'] = implode('<br/>', $err);
            return false;
        }
        return true;
    }


    public function validatePassword($pass){
        global $errors;
        $err=[];
        if(!$pass){
            $err[] = 'Укажите пароль';
        }
        if( strlen($pass) < 8 ){
            $err[] = 'Пароль должен быть 8 или более символов.';
        }
        $p = str_split($pass);
        $len = strlen($pass);
        $ch = 0;
        $num = 0;
        $digs = '0123456789';
        $digs = str_split($digs);
        foreach($p as $v){
            if(in_array($v, $digs)) $num++;
        }
        $ch = $len - $num;
        if( !$ch || !$num ){
            $err[] = 'Пароль должен содержать и цифры, и символы.';
        }
        if($err){
            // if($phone) $err[]=$phone;
            $errors['pass'] = implode('<br/>', $err);
            return false;
        }
        return true;
    }


    // новый пароль
    function changePassword(){
        if (isset($_POST['custom_repass_nonce']) 
            && wp_verify_nonce($_POST['custom_repass_nonce'], 'custom_repass')) {

            $email = sanitize_email($_POST['login_email']);
            if (!is_email($email)) {
                echo 'Неверный email.';
                addMess('Неверный email.');
                // addMess(site_url('/auth/'));
                // addMess(site_url('/login/'));
                return;
                // wp_redirect(site_url('/auth/'));
                // exit;
            }

            $user = get_user_by( 'email', $email );
            if(!$user){
                echo 'Неверный email..';
                addMess('Неверный email..');
                // addMess(site_url('/auth/'));
                // addMess(site_url('/login/'));
                return;
                // wp_redirect(site_url('/auth/'));
                // exit;
            }
            $verification_code = wp_generate_password(16, false, false);
            $userdata = [];
            $userdata['ID'] = $user->id;
            $userdata['user_pass'] = $verification_code;
            // addMess($userdata);
            // return;

            wp_set_password( $verification_code, $user->id );
            
            // wp_update_user( $userdata );


            wp_mail($email, 'Смена пароля', 'Ваш новый пароль: ' . $verification_code);

            $url = site_url('/auth/');
            $url = site_url('/login/');

            wp_redirect( $url );
            exit;
        }
    }

    function changeAccountPassword(){
        // addMess('changeAccountPassword.');
        if (isset($_POST['account_profile_nonce']) 
            && wp_verify_nonce($_POST['account_profile_nonce'], 'account_profile')) {
        // addMess('changeAccountPassword..');

                $email = sanitize_email($_POST['login_email']);
                $phone = sanitize_text_field($_POST['phone']);
                $password = sanitize_text_field($_POST['password']);
                $password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW);
                $password = trim($password);

                if($phone){

                    $user = wp_get_current_user();
                    update_user_meta($user->id, 'phone', $phone);
                    // addMess($phone.'['.$user->id.']','Изменён телефон.');
                    addSys('Изменён телефон.'); // addMess
                }

                if($password){
                    wp_set_password( $password, $user->id );
                    addMess('Изменён пароль.');
                }
                // update_user_meta($user_id, 'verification_code', $verification_code);
                // update_user_meta($user_id, 'phone', $phone);


            // if (!is_email($email)) {
            //     echo 'Неверный email.';
            //     addMess('Неверный email.');
            //     return;
            //     // wp_redirect(site_url('/auth/'));
            //     // exit;
            // }

            // $user = get_user_by( 'email', $email );
            // if(!$user){
            //     echo 'Неверный email..';
            //     addMess('Неверный email..');
            //     return;
            //     // wp_redirect(site_url('/auth/'));
            //     // exit;
            // }
            // $verification_code = wp_generate_password(16, false, false);
            // $userdata = [];
            // $userdata['ID'] = $user->id;
            // $userdata['user_pass'] = $verification_code;
            // // addMess($userdata);
            // // return;

            // wp_set_password( $verification_code, $user->id );
            
            // // wp_update_user( $userdata );


            // wp_mail($email, 'Смена пароля', 'Ваш новый пароль: ' . $verification_code);

            wp_redirect(site_url('/account/'));
            exit;
        }
    }


    // подтверждение почты
    // Handle email verification
    function custom_handle_auth() {
        if (isset($_POST['custom_auth_nonce']) 
            && wp_verify_nonce($_POST['custom_auth_nonce'], 'custom_auth')) {
            $auth_code = sanitize_text_field($_POST['auth_code']);
            
            $user_query = new WP_User_Query(array(
                'meta_key' => 'verification_code',
                'meta_value' => $auth_code,
                'number' => 1
            ));
            
            $users = $user_query->get_results();
            
            if (!empty($users)) {
                $user = $users[0];
                update_user_meta($user->ID, 'email_verified', true);
                delete_user_meta($user->ID, 'verification_code');
                
                // Login the user and redirect to account page
                wp_set_current_user($user->ID);
                wp_set_auth_cookie($user->ID);
    
                $url = '/account/';
                if(isset($_COOKIE['tarif'])){
                    $url = '/create-memory-page/?tarif=' . $_COOKIE['tarif'] ;
                    setcookie('tarif', $tarif, time() - (86400 * 30), "/"); // 86400 = 1 day
                }
                wp_redirect(site_url($url));
                exit;
            } else {
                echo 'Введите код, который мы отправили вам на email.';
            }
        }
    }


    // обработка формы входа
    // Handle login form submission
    function custom_handle_login() {
        if (isset($_POST['custom_login_nonce']) 
        && wp_verify_nonce($_POST['custom_login_nonce'], 'custom_login')) {
            $email = sanitize_email($_POST['login_email']);
            $password = sanitize_text_field($_POST['login_password']);
    
            $user = wp_authenticate($email, $password);
            if (is_wp_error($user)) {
                echo 'Неверный email или пароль.';
                return;
            }
    
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID);
    
            $url = '/account/';
            // if(isset($_COOKIE['tarif'])){
            //     $url = '/create-memory-page/?tarif=' . $_COOKIE['tarif'] ;
            // }
            wp_redirect(site_url($url));
            exit;
        }
    }


    // перенаправление в аккаунт, если залогинен
    // Redirect logged-in users from registration and login pages to account page
    function custom_redirect_logged_in_users() {
        if (is_user_logged_in()) {
            if (is_page('register') || is_page('login')) {
                wp_redirect(site_url('/account/'));
                exit;
            }
        }
    }

}
