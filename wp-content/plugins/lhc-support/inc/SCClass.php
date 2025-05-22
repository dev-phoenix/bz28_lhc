<?php
/*
File: SCClass.php
Descriptin: Short Code Class
Author: WSD
Created: 2024.09.06 10:25
*/

class SCClass{

    public int $pid; // page id
    public ?string $auid; // author user id


    public function __construct()
    {
        $this->process();
    }


    function process(){
        $this->shortcodes();
    }


    function shortcodes(){
        add_shortcode('custom_registration_buttons', [$this, 'custom_registration_shortcode'] );
        add_shortcode('custom_registration_form', [$this, 'custom_registration_form_shortcode'] );
        add_shortcode('custom_repass_form', [$this, 'custom_repass_form_shortcode'] );
        add_shortcode('custom_auth_form', [$this, 'custom_auth_form_shortcode'] );
        add_shortcode('custom_login_form', [$this, 'custom_login_form_shortcode'] );
        add_shortcode('custom_account_page', [$this, 'custom_account_page_shortcode'] );
        add_shortcode('create_memory_page_form', [$this, 'create_memory_page_form_functional_shortcode'] );
        // add_action('init', [$this, 'create_memory_page_form_functional_shortcode']);
    }


    // Register shortcode for the registration/login buttons
    function custom_registration_shortcode() {
        ob_start();
        ?>
        <div>
            <a href="<?php echo site_url('/register/'); ?>" class="button">Зарегистрироваться.</a>
            <a href="<?php echo site_url('/login/'); ?>" class="button">Войти.</a>
        </div>
        <?php
        return ob_get_clean();
    }


    // Registration form shortcode
    function custom_registration_form_shortcode() {
        ob_start();
        // include(plugin_dir_path(__FILE__) . 'register-form.php');
        get_template_part( 'inc/tpl', 'register' );
        return ob_get_clean();
    }
    
    
    // Registration form shortcode
    function custom_repass_form_shortcode() {
        ob_start();
        // include(plugin_dir_path(__FILE__) . 'register-form.php');
        get_template_part( 'inc/tpl', 'repass' );
        return ob_get_clean();
    }
    
    
    // Authentication form shortcode
    function custom_auth_form_shortcode() {
        ob_start();
        // include(plugin_dir_path(__FILE__) . 'auth-form.php');
        get_template_part( 'inc/tpl', 'auth' );
        return ob_get_clean();
    }
    
    
    // Login form shortcode
    function custom_login_form_shortcode() {
        ob_start();
        // include(plugin_dir_path(__FILE__) . 'login-form.php');
        get_template_part( 'inc/tpl', 'login' );
        return ob_get_clean();
    }
    
    
    // Account page shortcode
    function custom_account_page_shortcode() {
        ob_start();
        // include(plugin_dir_path(__FILE__) . 'account-page.php');
        get_template_part( 'inc/tpl', 'account' );
        return ob_get_clean();
    }


    // Shortcode for memory page creation form
    function create_memory_page_form_functional_shortcode() {
        if(isset($_GET['tarif'])){
            $_COOKIE['tarif'] = $_GET['tarif'];
            $tarif = filter_input(INPUT_GET, 'tarif', FILTER_SANITIZE_SPECIAL_CHARS);
            setcookie('tarif', $tarif, time() + (86400 * 30), "/"); // 86400 = 1 day
        }
        // addmess($_GET,'$_GET');
        // addmess($_COOKIE,'$_COOKIE');
        if (!is_user_logged_in()) {
            wp_redirect(site_url('/register/')); // login
            exit;
        }
        ob_start();
        //include CR_PLAGIN_DIR . 'create-memory-page.php';
        // $shortcs = new SCClass();
        $createPage = new CAClass(0, 0, false);
        $createPage->include_create_memory_page_template_process();

        // get_template_part( 'inc/tpl', 'page-edit' );
        // include plugin_dir_path(__FILE__) . 'create-memory-page.php';
        return ob_get_clean();
    }

}