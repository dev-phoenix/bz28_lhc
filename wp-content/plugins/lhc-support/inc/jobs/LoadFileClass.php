<?php
/**
 * LoadFileClass.php
 * 
 * author: WSD
 * created: 2024-10-14 04:04
*/
if ( ! defined( 'ABSPATH' ) ) { echo 'Silence gold.'; exit(403); }

class LoadFileClass {
    public $tarifs = null;
    public $tarif = 'econ';
    public $pid = 0;

    public int $photos_cou = 10;
    public int $videos_cou = 0;
    public int $audio_cou = 0;
    public int $relation_cou = 1000;

    public $out = null;


    public function __construct($pid, &$out){
        $this->out = &$out;
        $this->tarifs = new PriceClass();
        $this->pid = $pid;
        $this->tarif = get_post_meta( $pid, 'tarif', 1 );
        $this->tarifs->init( $this->tarif );
        $this->setLimits();

        $this->out['test'] = time();
    }
    function build($pid=0){}
    function save($pid=0){}
    
    /**
     * Установить лимиты согласно тарифу
     * Summary of setLimits
     * @param mixed $pid
     * @return void
     */
    function setLimits($pid=0){
        $this->photos_cou = 10;
        $this->videos_cou = 0;
        $this->audio_cou = 0;
        $this->relation_cou = 1000;
        $this->is_hero = false;
    
        switch($this->tarif){
            case 'econ':
                $this->photos_cou = 10;
                $this->videos_cou = 0;
                $this->audio_cou = 0;
                $this->is_hero = false;
                break;
            case 'std':
                $this->photos_cou = 30;
                $this->videos_cou = 10;
                $this->audio_cou = 0;
                $this->is_hero = false;
                break;
            case 'max':
                $this->photos_cou = 1000;
                $this->videos_cou = 1000;
                $this->audio_cou = 1000;
                $this->is_hero = false;
                break;
            case 'hero':
                $this->photos_cou = 1000;
                $this->videos_cou = 1000;
                $this->audio_cou = 1000;
                $this->is_hero = True;
                break;
        }
    }

}