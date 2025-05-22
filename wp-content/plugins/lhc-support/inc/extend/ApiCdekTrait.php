<?php
/**
 * ApiCdekTrait.php
 * 
 * author: WSD
 * created: 2024-11-18 16:50
*/
if ( ! defined( 'ABSPATH' ) ) { echo 'Silence gold.'; exit(403); }


trait ApiCdekTrait
{


    /**
     * entrance for cdek api
     */
    function cdek_api(){
        if(!is_user_logged_in()){ echo '{}'; exit();}
        // echo 'api';exit();
        $todo = filter_input(INPUT_POST, 'todo', FILTER_UNSAFE_RAW);
        $type = filter_input(INPUT_POST, 'type', FILTER_UNSAFE_RAW);
        $task = filter_input(INPUT_POST, 'task', FILTER_UNSAFE_RAW);

        $country    = filter_input(INPUT_POST, 'country', FILTER_UNSAFE_RAW);
        $region     = filter_input(INPUT_POST, 'region', FILTER_UNSAFE_RAW);
        $city       = filter_input(INPUT_POST, 'city', FILTER_UNSAFE_RAW);

        global $cdekCrmClass;
        if( !$cdekCrmClass ){
            $cdekCrmClass = new CdekCrmClass();
        }

        if($task == 'get'){
            $res=[];
            switch($type){
                case 'add-bill':
                    $pid = filter_input(INPUT_GET, 'pid', FILTER_VALIDATE_INT);
                    // $res = lhc_cdek_get_ofices($country, $region, $city); // 'ru', 81, 44
                    if($pid){
                        $query = [];
                        $query['pid'] = $pid;
                        $query['checkout'] = 2;
                        $query['checktype'] = 'qr';
                        $url = URL_CREATE_MEMO . '?' . $q;
                        wp_redirect( $url );
                        exit;
                    }
                    break;
                case 'tarif':
                    $ofice = filter_input(INPUT_POST, 'ofice', FILTER_UNSAFE_RAW);
                    // $ofice = json_decode( file_get_contents( 'php://input' ), true );
                    $ofice = json_decode($ofice, 1);
                    $data = [];
                    // $data['location'] = $ofice['location'];
                    $data = $ofice;
                    $res = lhc_cdek_get_tarif($data, $istest = false);

                    $get_option_key = 'woocommerce_'.'official_cdek'.'_settings';
                    $opts = get_option( $get_option_key, null );
                    $del_opts = json_decode($opts['delivery_price_rules'],1);
                    $res['cost'] = ceil($res['total_sum'] * ( $del_opts['office'][0]['value'] / 100 ));
                    break;
                case 'countries':
                    $res = lhc_cdek_get_countries();
                    break;
                case 'regions':
                    $_regs = lhc_cdek_get_regions($country);
                    foreach($_regs as $reg){ 
                        if(isset($reg['region_code']))
                        $res[$reg['region_code']] = $reg['region'];
                    }
                    asort($res, SORT_NATURAL | SORT_FLAG_CASE);
                    break;
                case 'cities':
                    $_regs = lhc_cdek_get_cities($region);
                    foreach($_regs as $reg){ 
                        if(isset($reg['code']))
                        $res[$reg['code']] = $reg['city'];
                    }
                    asort($res, SORT_NATURAL | SORT_FLAG_CASE);
                    break;
                case 'ofices':
                    $res = lhc_cdek_get_ofices($country, $region, $city); // 'ru', 81, 44
                    break;
            }
            echo json_encode($res);
            exit();
        }
    }
}

// 'country_code' => $address['country'] ?? 'RU',