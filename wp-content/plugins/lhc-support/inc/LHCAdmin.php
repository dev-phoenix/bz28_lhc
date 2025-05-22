<?php
/**
 * File: LHCAdmin.php
 * Author: WSD
 * Created: 2024-11-12 12:16
*/

// LHCAdmin.php
//  * https://история-жизни.рф/wp-admin/admin.php
// ? page = wc-settings
// & tab = shipping
// & section = official_cdek


//============================

class PartnerAdminClass {
    public function __construct()
    {
        $this->actions();
    }

    function actions(){
        add_action( 'admin_menu', [$this, 'lhc_admin_partner_menu']);
        add_action( 'admin_menu', [$this, 'mt_add_pages'] );
    }


    function lhc_admin_partner_menu() {
        add_menu_page( 'Партнёры опции', 'Партнёры', 'manage_options',
            'admin-partner', [$this, 'lhc_admin_partner'] );
    }


    // action function for above hook
    function mt_add_pages() {
        add_action('load-lhc_page_lhc-settings',[$this, 'load_lhc_page_lhc_settings']);
        // // Add a new submenu under Options: раздел Настройки
        // add_options_page('LHC Options', 'LHC Options', 'manage_options', 'lhc-options', [$this, 'mt_options_page'] );

        // // Add a new submenu under Manage: раздел Инструменты
        // add_management_page('LHC Manage', 'LHC Manage', 'manage_options', 'lhc-manage', [$this, 'mt_manage_page'] );

        // // Add a new top-level menu (ill-advised):
        // add_menu_page('Test Toplevel', 'Test Toplevel', 'manage_options', __FILE__, [$this, 'mt_toplevel_page'] );

        // Add a submenu to the custom top-level menu:
        add_submenu_page('admin-partner', 'LHC Settings', 'LHC Settings',
            'manage_options', 'lhc-settings', [$this, 'lhc_settings_page'] );

        // Add a second submenu to the custom top-level menu:
        // add_submenu_page('admin-partner', 'LHC Sublevel 2', 'LHC Sublevel 2',
        //     'manage_options', 'sub-page2', [$this, 'mt_sublevel_page2'] );

        // add_submenu_page('admin-partner','Shipping', 'Shipping',
        //     'manage_options', 'shipping', [$this, 'mt_sublevel_page'] );
    }


    function load_lhc_page_lhc_settings() {
        echo "<h3>LHC Settings action</h3>";
        // do_action('load-lhc_page_lhc-settings');
    }

    function show_messages(){
        // addMess('ddd','fff');
        echo div( showMess(0,0), 'mt-20');
    }


    function lhc_settings_page() {
        $this->style();
        echo "<h2>LHC Settings</h2>";
        $tabs = [];
        $tabs['lhc-shipping'] = 'LHC Shipping';
        $current_tab = 'def';
        if (isset($_GET['tab']) //|| $_GET['tab'] == 'lhc-shipping'
        ) {
            $current_tab = $_GET['tab'];
        }
        include CR_PLAGIN_DIR.'/inc/views/html-admin-settings.php';
        do_action('load-lhc_page_lhc-settings');
    }


    function lhc_admin_partner() {
        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
        echo '</pre> <div class="wrap">';
        echo ' Партнёры, опции. ';
        echo '</div><pre>';
    }


    // mt_options_page() displays the page content for the Test Options submenu
    function mt_options_page() {
        echo "<h2>Test Options</h2>";
    }


    // mt_manage_page() displays the page content for the Test Manage submenu
    function mt_manage_page() {
        echo "<h2>Test Manage</h2>";
    }


    // mt_toplevel_page() displays the page content for the custom Test Toplevel menu
    function mt_toplevel_page() {
        echo "<h2>Test Toplevel</h2>";
    }


    // mt_sublevel_page() displays the page content for the first submenu
    // of the custom Test Toplevel menu
    function mt_sublevel_page() {
        echo "<h2>Test Sublevel</h2>";
    }


    // mt_sublevel_page2() displays the page content for the second submenu
    // of the custom Test Toplevel menu
    function mt_sublevel_page2() {
        echo "<h2>Test Sublevel 2</h2>";
    }


    static function initHlsPartnerAdmin(){
        global $adminPartnerClass;
        if(!$adminPartnerClass){
            $adminPartnerClass = new PartnerAdminClass();
        }
    }

    function style(){
        $css = ' 
        <style>
.d-f{
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
}
.d-fw{
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
}
.d-c{
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
}
.d-cc{
    display: flex;
    flex-direction: column !important;
    align-items: center;
    justify-content: center;
}
.d-n{ display: none; }
.tac{ text-align: center; }
.ma{ margin: auto; }

.as-e{ align-self: end;}
.ai-e{ align-items: end;}

.g-20{ gap: 20px; }
.g-10{ gap: 10px; }


.w100{ width: 100%;}
.h100{ height: 100%;}
.w100v{ width: 100vw;}
.h100v{ height: 100vh;}

.mt-20{
    margin-top: 20px;
}
.mb-20{
    margin-bottom: 20px;
}
.pt-20{
    padding-top: 20px;
}
.pb-20{
    padding-bottom: 20px;
}
.f-gap-20{
    gap: 20px;
}

.w150 { width: 150px;}
.w200 { width: 200px;}
.w250 { width: 250px;}
.w300 { width: 300px;}


.mess-wrap > * {
    border: 1px solid #ff0000;
    background-color: #ff000022;
    border-radius: 5px;
    margin-bottom: 5px;
    padding: 10px;
    gap: 20px;
}
.mess-wrap > * .mess-title{
        align-self: start;
    }
.mess-wrap > *.mess-error{
        border: 1px solid #ff0000;
        background-color: #ff000022;
    }
.mess-wrap > *.mess-success{
        border: 1px solid #00ff00;
        background-color: #00ff0022;
    }
.mess-wrap > *.mess-system{
        border: 1px solid #0000ff;
        background-color: #0000ff22;
    }
.mess-wrap > *.mess-message{
        border: 1px solid #000099;
        background-color: #00009922;
    }
.mess-wrap > *.mess-warning{
        border: 1px solid #00ffff;
        background-color: #00ffff22;
    }

.mess-wrap > *.error, .mess-wrap > *.success, .mess-wrap > *.system,
.mess-wrap > *.message, .mess-wrap > *.warning{
        
    }
</style>
        ';
        echo $css;
    }
}

add_action( 'init', ['PartnerAdminClass', 'initHlsPartnerAdmin']);

//============================


