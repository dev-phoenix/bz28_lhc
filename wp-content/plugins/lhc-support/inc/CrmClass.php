<?php
/**
 * CrmClass.php
 * 18:40
*/

// include_once CR_PLAGIN_DIR.'inc/conf/conf.php';
include_once 'conf/conf.php';
include 'crm/AmoCrmClass.php';
include 'crm/CdekCrmClass.php';

class CrmClass{
    public $crmName = null;
    public $crm = null;
    public $systems = [];
    public $data = null;


    public function __construct($sys = 'amo'){
        $this->initSys($sys);
        add_action('init', [$this, 'initEvent']);
    }


    public function init($name){
        $this->initSys($name);
    }


    // init sysem event
    public function initEvent(){
        // addMess(__DIR__);
        $this->crm->initEvent();
    }


    public function initSys($name){
        if(array_key_exists($name, $this->systems)){
            $this->sys = $this->systems[$name];
            return;
        }
        switch($name){
            case 'amo': $this->initSysAmo(); break;
        }
    }


    public function initSysAmo(){
        $this->crm = new AmoCrmClass();
        $this->systems['amo'] = $this->crm;
    }


    public function send($task, $data){
        if(!$this->crm) return false;
        $res = $this->_send($task, $data);
        return $res;
    }


    public function _send($task, $data){
        return $this->crm->send($task, $data);
    }


    public function log(){

    }


    public function event($name='', $data = []){

    }
}
global $crmClass;
$crmClass = new CrmClass('amo');

/**
 * sys  - pyment system name [ robo ]
 * summ - billing summ
 * bid  - bill id
 */
function wsd_crm_event($event, $data, $sys='amo'){
    global $crmClass;
    if( !$crmClass ){
        $crmClass = new CrmClass($sys);
    }
    $crmClass->init($sys);
    return $crmClass->send($event, $data);
    // return $crmClass->event($event, $data);
}