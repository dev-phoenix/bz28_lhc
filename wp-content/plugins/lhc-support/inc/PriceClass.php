<?php
/**
 * PriceClass.php
 * 2024-10-10 18:40
*/

class PriceClass{
    public $tarif = [
        'econ'=>'0',
        'std'=>'4990',
        'max'=>'7990',
        'hero'=>'5990',
    ];
    public $tarifOld = [
        'econ'=>'0',
        // 'econ'=>'2490',
        'std'=>'',
        'max'=>'',
        'hero'=>'11990',
    ];
    public $tarifNames = [
        'econ'=>'Эконом',
        'std'=>'Стандарт',
        'max'=>'Макси',
        'hero'=>'Герой',
    ];
    public $pageLimit = [
        'econ'=>'1',
        'std'=>'1000',
        'max'=>'1000',
        'hero'=>'1000',
    ];

    public $carrency = '₽';
    public $sum = '0';
    public $sumold = '';
    public $name = '';
    public $title = '';
    public $limit = '';




    public function __construct(){ // $sys = 'robo'){
        // $this->initSys($sys);
    }


    public function init($name){
        if(!$name) $name = 'econ';
        if(! array_key_exists($name, $this->tarif)) $name = 'econ';
        $this->name = $name;
        if(array_key_exists($name, $this->tarif)) $this->sum = $this->tarif[ $name ];
        if(array_key_exists($name, $this->tarifOld)) $this->sumold = $this->tarifOld[ $name ];
        if(array_key_exists($name, $this->tarifNames)) $this->title = $this->tarifNames[ $name ];
        if(array_key_exists($name, $this->pageLimit)) $this->limit = $this->pageLimit[ $name ];
    }


    public function costSets(){
        global $tarif_sets;
        if(isset($_SESSION['cost_sets'])) {
            if(!$tarif_sets){
                $tarif_sets=[];
                $tarif_sets['cost_sets'] = [];
            }
            $tarif_sets['cost_sets'] = $_SESSION['cost_sets'];
        }
    }


    public function correctSum($sum){
        $this->costSets();
        global $tarif_sets;
        if( isset($tarif_sets['cost_sets']) && isset($tarif_sets['cost_sets']['all_cost']) ){
            if($sum > 0){
                $sum = $tarif_sets['cost_sets']['all_cost'];
            }
        }
        // addMess($tarif_sets, '$tarif_sets');
        return $sum;
    }


    public function correctOldSum($sum){
        $this->costSets();
        global $tarif_sets;
        if( isset($tarif_sets['cost_sets']) && isset($tarif_sets['cost_sets']['all_cost']) ){
            $_sum = $this->sum;
            if($_sum > 0){
                $sum = $_sum;
            }
        }
        // addMess($tarif_sets, '$tarif_sets');
        return $sum;
    }


    public function getSum(){ return $this->correctSum( $this->sum ); }
    public function getSumOld(){ return $this->correctOldSum( $this->sumold ); }
    public function getTitle(){ return ( $this->title ); }
    public function getName(){ return ( $this->name ); }
    public function getLimit(){ return ( $this->limit ); }

}

function lhc_tarif_sum($name){
    global $priceClass;
    if( !$priceClass ){
        $priceClass = new PriceClass();
    }
    $priceClass->init($name);
    return $priceClass->getSum();
}
function lhc_tarif_sum_old($name){
    global $priceClass;
    if( !$priceClass ){
        $priceClass = new PriceClass();
    }
    $priceClass->init($name);
    return $priceClass->getSumOld();
}
function lhc_tarif_title($name){
    global $priceClass;
    if( !$priceClass ){
        $priceClass = new PriceClass();
    }
    $priceClass->init($name);
    return $priceClass->getTitle();
}
function lhc_tarif_name($name){
    global $priceClass;
    if( !$priceClass ){
        $priceClass = new PriceClass();
    }
    $priceClass->init($name);
    return $priceClass->getName();
}
function lhc_tarif_limit($name){
    global $priceClass;
    if( !$priceClass ){
        $priceClass = new PriceClass();
    }
    $priceClass->init($name);
    return $priceClass->getLimit();
}