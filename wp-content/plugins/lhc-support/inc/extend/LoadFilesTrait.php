<?php
/**
 * LoadFilesTrait.php
 * 
 * author: WSD
 * created: 2024-10-14 20:08
*/
if ( ! defined( 'ABSPATH' ) ) { echo 'Silence gold.'; exit(403); }


trait LoadFilesTrait
{


    /**
     * Установить лимиты согласно тарифу
     * Summary of setLimits
     * @param mixed $pid
     * @return void
     */
    function setLimits($pid){
        $this->photos_cou = 10;
        $this->videos_cou = 0;
        $this->audio_cou = 0;
        $this->relation_cou = 1000;
        $this->is_hero = false;

        if(!$pid) return;
        $tarif = get_post_meta( $pid, 'tarif', 1 );
        if(!$tarif) return;
        $this->tarif = $tarif;
        $this->initLimits($this->tarif);
    }


    public function initLimits($tarif){
        $this->photos_cou = 10;
        $this->videos_cou = 0;
        $this->audio_cou = 0;
        $this->relation_cou = 1000;
        $this->is_hero = false;
    
        switch($tarif){
            case 'econ':
                $this->photos_cou = 3;
                $this->videos_cou = 0;
                $this->audio_cou = 0;
                $this->is_hero = false;
                break;
            case 'std':
                $this->photos_cou = 30;
                $this->videos_cou = 50;
                $this->audio_cou = 0;
                $this->is_hero = false;
                break;
            case 'max':
                $this->photos_cou = 100;
                $this->videos_cou = 10;
                $this->audio_cou = 10;
                $this->is_hero = false;
                break;
            case 'hero':
                $this->photos_cou = 100;
                $this->videos_cou = 10;
                $this->audio_cou = 10;
                $this->is_hero = True;
                break;
        }
        $tar_sets = [];
        $tar_sets['photos_cou'] = $this->photos_cou;
        $tar_sets['videos_cou'] = $this->videos_cou;
        $tar_sets['audio_cou'] = $this->audio_cou;
        $tar_sets['is_hero'] = $this->is_hero;
        return $tar_sets;
    }


    public $meta_type = 'photo';
    /**
     * Распределённая загрузкка файлов 
     */
    public function loadMemoryFile(){
        if (!is_user_logged_in()) { exit(401); }
        $user_id = get_current_user_id();

        $out = [];
        $pid = filter_input(INPUT_POST, 'pid', FILTER_VALIDATE_INT);
        $todo = filter_input(INPUT_POST, 'todo', FILTER_UNSAFE_RAW);
        $type = filter_input(INPUT_POST, 'type', FILTER_UNSAFE_RAW);
        $task = filter_input(INPUT_POST, 'task', FILTER_UNSAFE_RAW);

        // exit 401 if user is not author of post with one post_id
        global $wpdb;
        $tabl = $wpdb->prefix.'posts';
        $q = 'select `post_author` from '.$tabl.' where `ID` = %d and `post_author` = %s';
        $uid = $wpdb->get_var($wpdb->prepare($q,[$pid, $user_id]));
        if( !$uid ) exit(401);
        if( $uid != $user_id ) exit(401);

        $job = new LoadFileClass($pid, $out);
        $job->build();
        $job->save();

        $this->meta_type = $type;
        switch($type){
            case 'photo':
            case 'audio':
            case 'video':
                $field = '';
                // set dir
                // gen name full prev
                // copy
                // create preview
                // return fid prevUrl

                $out['pid'] = $pid;
                $out['do'] = $todo;
                $out['type'] = $type;
                $out['task'] = $task;
                $out['res'] = 'test';
                // $out['out'] = $job->out;
                $out['status'] = 'fail';

                $this->setLimits($pid);
                $out['limit'] = $this->photos_cou;

                $meta_name = 'load-file';
                $remove_name = '';
                switch($type){
                    case 'photo': 
                        $meta_key = 'photo'; $fd = 'photo-file-remove';
                    $checkBy = 'url_pre'; $counter = 'photos_cou';
                    break;
                    case 'audio': 
                        $meta_key = 'audio'; $fd = 'photo-file-remove';
                    $checkBy = 'url'; $counter = 'audio_cou';
                    break;
                    case 'video': 
                        $meta_key = 'video'; $fd = 'photo-file-remove';
                    $checkBy = 'url'; $counter = 'videos_cou';
                    break;
                }
                
                // if($task == 'append')$prev = $this->copyPhotoFile($pid, 'load-file');
                // if($task == 'remove')$prev = $this->removePhotoFile($pid, 'load-file');

                if($task == 'append'){
                    // post_id meta_key data_type input_name return_data_value
                    $prev = $this->copyPhotoFile($pid, $meta_key, $type, 'load-file', $checkBy);
                }
                if($task == 'remove'){
                    // post_id meta_key input_name compare_data_field return_data_value
                    $prev = $this->removePhotoFile($pid, $meta_key , $fd, $checkBy, $counter);
                }
                $out['data'] = $prev;

                // function removePhotoFile($pid,
                // $f_ = 'photo', $fd = 'photo-file-remove', 
                // $checkBy = 'url_pre', $counter = 'photos_cou'){
                
                $arr = get_post_meta( $pid, $type, 1 );
                $out['count'] = count($arr);
                if($prev){
                    // $prev = $prev[$checkBy];
                    // $out[$checkBy] = $prev;
                    $out[$checkBy] = false;
                    if(isset($prev[$checkBy]))$out[$checkBy] = $prev[$checkBy];
                    $out['status'] = 'success';
                }
                break;
            case 'audio':
                // set dir
                // gen name
                // copy
                // return fid furl title
                break;
            case 'video':
                // set dir
                // gen name
                // copy
                // return fid furl title
                break;
            case 'reserve':
                break;
        }


        echo json_encode($out);
        exit(200);
    }



    /**
     * (PHP 4 >= 4.3.0, PHP 5, PHP 7, PHP 8) exif_imagetype
     */
    function imageCreateFromAny($filepath) {
        $type = exif_imagetype($filepath); // [] if you don't have exif you could use getImageSize()
        $allowedTypes = array(
            1,  // [] gif
            2,  // [] jpg
            3,  // [] png
            6   // [] bmp
        );
        if (!in_array($type, $allowedTypes)) {
            // addMess('unknown','fileType');
            return false;
        }
        // addMess($type,'fileType');
        switch ($type) {
            case 1 :
                $im = imageCreateFromGif($filepath);
            break;
            case 2 :
                // $im = imageCreateFromJpeg($filepath);
                $im = imagecreatefromjpeg($filepath);
            break;
            case 3 :
                $im = imageCreateFromPng($filepath);
            break;
            case 6 :
                $im = imageCreateFromBmp($filepath);
            break;
        }
        // addMess(file_exists($filepath), 'img exists');
        // addMess($filepath,'img $filepath');
        // addMess($im,'img obj');
        // addMess(var_export($im,1),'img obj');
        return $im;  
    }


    function cropAva($pid){
        $upload_dir = wp_upload_dir();
        $ava_dir = $upload_dir['basedir'] . '/avatar/';
        $ava_x = filter_input(INPUT_POST, 'ava-x', FILTER_VALIDATE_INT);
        $ava_y = filter_input(INPUT_POST, 'ava-y', FILTER_VALIDATE_INT);
        $ava_w = filter_input(INPUT_POST, 'ava-w', FILTER_VALIDATE_INT);
        $ava_h = filter_input(INPUT_POST, 'ava-h', FILTER_VALIDATE_INT);
        $crop = ['x' => $ava_x, 'y' => $ava_y, 'width' => $ava_w, 'height' => $ava_h];
        // addMess($crop, 'cropAva');
        if(!$ava_w || !$ava_h) return;


        $ava_x_ = get_post_meta($pid, 'ava-x', 1);
        $ava_y_ = get_post_meta($pid, 'ava-y', 1);
        $ava_w_ = get_post_meta($pid, 'ava-w', 1);
        $ava_h_ = get_post_meta($pid, 'ava-h', 1);
        if(
            $ava_x == $ava_x_ &&
            $ava_y == $ava_y_ &&
            $ava_w == $ava_w_ &&
            $ava_h == $ava_h_ 
        )return;

        $ava_src = get_post_meta( $pid, 'ava_src-path', 1 );
        // addMess($ava_src, '$ava_src');
        if(!$ava_src) return;
        $ava = get_post_meta( $pid, 'ava-path', 1 );
        if($ava && $ava_src != $ava) unlink($ava);

        $prefix = 'pm_'.$pid.'_ava_';
        $prefix_src = $prefix.'src_';
        $n = $prefix . str_replace( $prefix_src, '', basename($ava_src));
        $n_ = explode('.',$n);
        $ext = array_pop( $n_ );
        array_push($n_, 'png');
        $n = implode('.', $n_);
        $src_to = $ava_dir . '/' . $n;

        $im = $this->imageCreateFromAny($ava_src);
        // addMess(file_exists($ava_src), 'exists');
        // addMess(exif_imagetype($ava_src), 'exif_imagetype');
        // addMess($im, 'imageCreateFromAny $im');
        // addMess([imagesx($im), imagesy($im)], 'size $im');
        if(!$im) return;
        // $im = false;
        // $ext = strtolower($ext);
        // if($ext == 'png') $im = imagecreatefrompng($ava_src);
        // if($ext == 'jpg') $im = imagecreatefromjpeg($ava_src);

        // $ax = imagesx($im);
        // $ay = imagesy($im);
        // $ava_x = min($ax, $ava_x);
        // $ava_y = min($ay, $ava_y);

        $im2 = imagecrop($im, $crop);
        // addMess($im2, 'imagecrop $im2');
        // addMess(var_export($im,1),'var_export obj');
        if ( $im2 !== FALSE ) {
            unlink($src_to);
            $res = imagepng($im2, $src_to);
            // addMess($res, 'imagepng $im2');
            imagedestroy($im2);

            $path = $upload_dir['baseurl'];
            $path = parse_url($path, PHP_URL_PATH);
            $url = $path . '/avatar';
            $link = $url . '/' . basename($src_to);

            update_post_meta($pid, 'ava', $link);
            update_post_meta($pid, 'ava-path', $src_to);
            update_post_meta($pid, 'ava-x', $ava_x);
            update_post_meta($pid, 'ava-y', $ava_y);
            update_post_meta($pid, 'ava-w', $ava_w);
            update_post_meta($pid, 'ava-h', $ava_h);
        }
        imagedestroy($im);
    }


    /**
     * Сохранение аватарки
     * @param mixed $pid
     * @return void
     */
    function saveAva($pid){
        if(!$pid) return;

        $upload_dir = wp_upload_dir();
        $ava_dir = $upload_dir['basedir'] . '/avatar';
        if (!file_exists($ava_dir)) {
            mkdir($ava_dir, 0755, true);
        }

        // ======== move meta file to path
        $ava_ = get_post_meta( $pid, 'ava-file', 1 );
        $ava = get_post_meta( $pid, 'ava-path', 1 );
        if($ava_ && !$ava){
            update_post_meta($pid, 'ava-path', $ava_);
            $ava = $ava_;
        }
        $ava_src = get_post_meta( $pid, 'ava_src-path', 1 );
        $prefix = 'pm_'.$pid.'_ava_';
        $prefix_src = $prefix.'src_';

        if( !$this->is_new && !$ava_src && $ava ){
            $n = $prefix_src . basename($ava);
            $src_to = $ava_dir . '/' . $n;
            // addMess($src_to,'copy');
            copy($ava,$src_to);
            update_post_meta($pid, 'ava_src-path', $src_to);
            $ava = $ava_;

            $path = $upload_dir['baseurl'];
            $path = parse_url($path, PHP_URL_PATH);
            $url = $path . '/avatar';
            $link_to = $url . '/' . basename($src_to);
            update_post_meta($pid, 'ava_src', $link_to);
        }
        // ======== end move meta file to path

        $M = 1024*1024;
        $L = ['cou'=>1, 'size'=> 20 * $M, 'sizes'=> 20 * $M];
        $file_limit['info-ava-file'] = $L;
       // addMess($_FILES, 'ava $_FILES');
        if(
            array_key_exists('info-ava-file', $_FILES)
            && !$_FILES['info-ava-file']['error']
        ){
            $k = 'info-ava-file';
            $field = $_FILES[$k];
            $limit = $file_limit[$k];
            $from = $field['tmp_name'];
            $name = $field['name'];
            $size = $field['size'];
            $error = $field['error'];
            $val = trim((string)$from);
            if($val && $size <= $limit['size'] && $error == 0){
                // Обработка загруженного фото
                $uploaded_file = $_FILES['info-ava-file'];

                $ava_1 = get_post_meta( $pid, 'ava-path', 1 );
                unlink($ava_1);
                $ava_2 = get_post_meta( $pid, 'ava_src-path', 1 );
                unlink($ava_2);
               // addMess($ava_1, 'ava 1');
               // addMess($ava_2, 'ava 2');
                //================
                // $file_dir = 'avatar';
                // $preview_dir = 'avatar';
                // $size = 10;

                // $upload_dir = wp_upload_dir();
                // $video_dir = $upload_dir['basedir'] . '/' . $file_dir;

                $files = $field;
                $key = 0;
                if(!is_array($files['name'])){
                    foreach ($files as &$v) {
                        $v = [$v];
                    }
                }
               // addMess($files, 'ava $files');

                $name = ($files['name'][$key]);
                $loaded_name = $name;
               // addMess($name, 'ava $name 0');

                // $prefix = 'pm_'.$pid.'__';
                // $sufix = 'p'.$pid;
                $sufix = '';
                $name = $prefix_src.basename($name);  // page memory PM_
               // addMess($name, 'ava $name 1');

                // relplace to free name
                $name = $this->newFileName($name, $ava_dir, $sufix); 
               // addMess($name, 'ava $name 2');
                // public load url
                // $url = $upload_dir['baseurlpath'] . '/' . $file_dir . '/' . $name;

                // copy pathes
                $from = $files['tmp_name'][$key];
                $to = $ava_dir . '/' . $name;
                move_uploaded_file($from, $to);
               // addMess($from, 'ava $from');
               // addMess($to, 'ava $to');
               // addMess(var_export(file_exists($from),1), 'ava $from exists');
               // addMess(var_export(file_exists($to),1), 'ava $to exists');
                
                // $url = $upload_dir['baseurlpath'] . '/avatar';
                $path = $upload_dir['baseurl'];
                $path = parse_url($path, PHP_URL_PATH);
                $url = $path . '/avatar';
                $link = $url . '/' . basename($name);
                $this->ava_url = $link;
                // addMess($link, 'ava $link');


                update_post_meta( $pid,  'ava', $this->ava_url );
                update_post_meta( $pid,  'ava-path', $to );
                update_post_meta( $pid,  'ava_src', $this->ava_url );
                update_post_meta( $pid,  'ava_src-path', $to );

                //================
                if(0){

                    $from = $uploaded_file['tmp_name'];
                    $name = $uploaded_file['name'];
                    $name = basename($name);
                    $dir = $upload_dir['basedir'] . '/avatar';

                    $name = $this->pidFileName($name,$dir,$pid);
                    $to = $dir . '/' . $name;

                    $rem_file = get_post_meta($pid, 'ava-file', true);
                    if($to != $rem_file && file_exists($rem_file) ){ unlink($rem_file); }

                    $to = $dir . '/' . $name;
                    move_uploaded_file($from, $to);
                    
                    $url = $upload_dir['baseurlpath'] . '/avatar';
                    $path = $upload_dir['baseurl'];
                    $path = parse_url($path, PHP_URL_PATH);
                    $url = $path . '/avatar';
                    $link = $url . '/' . basename($name);
                    $this->ava_url = $link;


                    update_post_meta( $pid,  'ava', $this->ava_url );
                    update_post_meta( $pid,  'ava-path', $to );
                    // update_post_meta( $pid,  'ava-file', $to );

                    //--
                    // save text form fields
                    $fields1 = [
                        'ava' => $this->ava_url,
                    ];
                    // addMess($this->ava_url,'$this->ava_url');
                    // addMess($pid,'$pid');

                    foreach($fields1 as $k=>$v){
                        update_post_meta( $pid, $k, $v );
                    }

                    //================

                    $x = 0;
                    $y = 0;
                    $w = 100;
                    $h = 100;
                    $image = wp_get_image_editor($to);
                    if (!is_wp_error($image)) {
                        // $image->resize(400, 400, true);
                        $image->crop($x, $y, $w, $h );
                        $image->save($to_pre);
                    }
                    //================
                    $item = [];
                    $item['title'] = $loaded_name;
                    $item['name'] = $name;
                    $item['path'] = $to;
                    $item['url'] = $url;
                    $item['path_ava'] = $to_pre;
                    $item['url_ava'] = $url_pre;
                }

            }else{

                $k = 'info-ava-file';
                $limit = $file_limit[$k];
                // addMess($error, 'ava $error');
                if(
                    $error
                    || !$val
                    || $size > $limit['size']
                ){
                    $errs=[];
                    $errs[]=$k;
                    $errs[]='pid '.$pid;
                    $errs[]='req '.$v;
                    if($size > $limit['size']) $errs[]='size '.$size.' over'.$limit['size'];
                    if($error) $errs[]='errno '.$error;
                    if(!$val) $errs[]='no value';
                    // addMess($errs,'saveAva err');
                    // $this->error_cou ++;
                    // $this->errors[$k] = $errs;
                    // $err = true;
                }
            }
        }
        else if($_FILES['info-ava-file']['error']){
            $ferr = $_FILES['info-ava-file']['error'];
            // switch($ferr){
            //     case UPLOAD_ERROR_OK: addMess('value 0, means no error occurred.','ava file error'); break;
            //     case UPLOAD_ERR_INI_SIZE: addMess('value 1, means that the size of the uploaded file exceeds the maximum value specified in your php.ini file with the upload_max_filesize directive.','ava file error'); break;
            //     case UPLOAD_ERR_FORM_SIZE: addMess('value 2, means that the size of the uploaded file exceeds the maximum value specified in the HTML form in the MAX_FILE_SIZE element.','ava file error'); break;
            //     case UPLOAD_ERR_PARTIAL: addMess('value 3, means that the file was only partially uploaded.','ava file error'); break;
            //     case UPLOAD_ERR_NO_FILE: addMess('value 4, means that no file was uploaded.','ava file error'); break;
            //     case UPLOAD_ERR_NO_TMP_DIR: addMess('value 6, means that no temporary directory is specified in the php.ini.','ava file error'); break;
            //     case UPLOAD_ERR_CANT_WRITE: addMess('value 7, means that writing the file to disk failed.','ava file error'); break;
            //     case UPLOAD_ERR_EXTENSION: addMess('value 8, means that a PHP extension stopped the file upload process.','ava file error'); break;
            // }
        }
        $this->cropAva($pid);
    }


    public int $file_sufix = 0;
    function pidFileName($name, $path, $suf='', $sufnum=0){
        $parts = explode('.', $name);
        $ext = array_pop($parts);
        array_push($parts, '__'.$suf.'', $ext );
        return implode('.', $parts);
    }


    public int $overload = 0;
    function newFileName($name, $path, $suf='', $sufnum=0){
        // $fs = $this->file_sufix;
        // $file = $path .'/'. $name;

        // echo $sufnum;
        // addMess($sufnum,'$sufnum');
        // addMess($name,'$name');
        // if($this->overload++ > 10) return $name;
        $parts = explode('.', $name);
        $ext = array_pop($parts);
        array_push($parts, $suf, $sufnum );
        $name_ = implode('__', $parts);
        $name_ = implode('.', [$name_, $ext]);
        $file = $path .'/'. $name_;
        // addMess($file,'$file'.':'.__LINE__);

        if(file_exists($file)){
            $name = $this->newFileName($name, $path, $suf, ++$sufnum);
        }else{
            $name = $name_;
        }
        return $name;
    }

    /**
     * Удаление элемента из массива.
     * Из галереии фото, удалять по ссылке превью.
     * Ссылки превью, относительные.
     * arr fieldNameToDelete checkArrItemField counterClassField
     */
    function remItem(&$arr, $field2delete, $checkField = 'url_pre', $classCounter = 'photos_cou'){
        $fd = $field2delete;

        // remove file
        // $rem = filter_input(INPUT_POST, $fd, FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);
        $rem = filter_input(INPUT_POST, $fd, FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY);
        // addMess($rem, 'remItem '.$fd);
        if(!$rem) $rem =[];
        $new_arr = [];

        if($arr && $rem){
            
            $pathes = [];
            switch($this->meta_type){
                case 'photo':$pathes=['path', 'path_pre'];break;
                case 'audio':$pathes=['path'];break;
                case 'video':$pathes=['path'];break;
                break;
            case 'reserve':
                break;
        }
            foreach($arr as $k=>$v){
                // addMess([$checkField, $v], 'remove');
                if(isset($v[$checkField])){
                    if(!in_array($v[$checkField],$rem)){ // по адресу превью
                    // if(!in_array(($k+1),$rem)){ // по индексу в массиве файлов
                        $new_arr[]=$v;
                        if( $classCounter ) --$this->$classCounter;
                    }
                    else
                    {
                        // addMess($v, 'remove');

                        foreach($pathes as $p){
                            unset($v[$p]);
                            // unset($v['path']);
                            // unset($v['path_pre']);
                        }
                    }
                }
            }
            $arr = $new_arr;
        }
        return $rem;
    }


    function removePhotoFile($pid,
    $f_ = 'photo', $fd = 'photo-file-remove', $checkBy = 'url_pre', $counter = 'photos_cou'){
        if(!$pid) return;
        // addMess([$f_, $fd, $checkBy, $counter], 'removePhotoFile '.$pid);

        
        // $f_ = 'photo';
        $f = 'load-file';
        // $fd = 'photo-file-remove';
        $arr = get_post_meta( $pid, $f_, 1 );
        
        if(!$arr || !is_array($arr)) $arr =[];

        // remove file
        // addMess($_POST, 'savePhoto '.$pid);
        // $rem = $this->remItem($arr, 'photo-file-remove', 'url_pre', 'photos_cou');
        $rem = $this->remItem($arr, $fd, $checkBy, $counter);
        if(!$arr || !is_array($arr)) $arr =[];
        update_post_meta( $pid, $f_, $arr );
        
        $rem = filter_input(INPUT_POST, $fd, FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY);
        // addMess($rem, 'removePhotoFile '.$pid);
        if(is_array($rem) && count($rem))$rem = array_shift($rem);
        else $rem = false;
        return $rem;
    }


    function addPhoto($pid){
    }


    function bindToCopyPhotoFile($pid, $type, $files, $key, &$arr=[]){
        $item = false;
        // $classCounter = 'photos_cou';
        // if(--$this->$classCounter <0) return false;
        // $file = [];
        // $file[] = ;

        // echo "\n=====".__LINE__."\n";
        // print_r($files);

        $file_dir = '';
        $preview_dir = '';
        $size = 10;
        switch($type){
            case 'photo':
                $file_dir = 'photos';
                $preview_dir = 'photos-thumbnails';
                $size = 10;
                break;
            case 'audio':
                $file_dir = 'audio';
                $preview_dir = '';
                $size = 50;
                break;
            case 'video':
                $file_dir = 'videos';
                $preview_dir = '';
                $size = 200;
                break;
        }


        if ($files['error'][$key] === UPLOAD_ERR_OK) {


            $video_file = $files;
            $video_size = $video_file['size'][$key];
            $video_type = $video_file['type'][$key];
            
            // Проверка формата и размера файла
            if ($type == 'video' && $video_type !== 'video/mp4') {
                return false;
            }
            
            if ($video_size > $size * 1024 * 1024) { // 200 МБ
                return false;
            }

            $upload_dir = wp_upload_dir();
            $video_dir = $upload_dir['basedir'] . '/' . $file_dir;

            $name = ($files['name'][$key]);
            $loaded_name = $name;

            $prefix = 'pm_'.$pid.'__';
            $sufix = 'p'.$pid;
            $sufix = '';
            $name = $prefix.basename($name);  // page memory PM_

            // relplace to free name
            $name = $this->newFileName($name, $video_dir, $sufix); 
            // public load url
            $url = $upload_dir['baseurlpath'] . '/' . $file_dir . '/' . $name;

            // copy pathes
            $from = $files['tmp_name'][$key];
            $to = $video_dir . '/' . $name;
            move_uploaded_file($from, $to);


            if(0){
                // simple copy to common upload dir
                $gallery_file = $files;
                $gallery_path = $upload_dir['basedir'] . '/' . basename($gallery_file['name'][$key]);
                move_uploaded_file($gallery_file['tmp_name'][$key], $gallery_path);

                // $gallery_url = $upload_dir['baseurlpath'] . '/' . basename($gallery_file['name'][$key]);
                // $gallery_urls[] = $gallery_url;
            }
            

            if($preview_dir){
                $video_dir_pre = $upload_dir['basedir'] . '/' . $preview_dir;
                $to_pre = $video_dir_pre . '/' . $name;
                $url_pre = $upload_dir['baseurlpath'] . '/' . $preview_dir . '/' . $name;

                // Создание миниатюр
                // $image = wp_get_image_editor($gallery_path);
                $image = wp_get_image_editor($to);
                if (!is_wp_error($image)) {
                    // $image->resize(150, 150, true);
                    $image->resize(400, 400, true);
                    $image->save($to_pre);
                    // $thumbnail_path = $upload_dir['basedir'] . '/photos-thumbnails/' . basename($gallery_file['name'][$key]);
                    // $image->save($thumbnail_path);
                    // $thumb = $upload_dir['baseurl'] . '/photos-thumbnails/' . basename($gallery_file['name'][$key]);
                    // $this->gallery_thumbnail_urls[] = $thumb;

                    // $gallery_imgs[] = ['thumb'=>$thumb, 'img'=>$gallery_url];
                }
            }

            $item = [];
            $item['title'] = $loaded_name;
            $item['name'] = $name;
            $item['path'] = $to;
            $item['url'] = $url;

            if($preview_dir){
                $item['path_pre'] = $to_pre;
                $item['url_pre'] = $url_pre;
            }

            $arr[] = $item;
        }
        return $item; // last append field
    }


    function copyPhotoFile($pid, $meta_name = 'photo', $type = 'photo',
        $input_field_name = 'load-file', $checkBy= 'url_pre'){
        $f = 'load-file';
        $f = $input_field_name;
        $files = [];
        $prev = false;
        // addMess([$pid, $meta_name, $type,
        // $input_field_name, $checkBy], 'copyPhotoFile');
        // addMess($_FILES, '$_FILES');
        if(isset($_FILES[$f])){
            $files = $_FILES[$f];
            if (!empty($files)) {
                // $file_keys = array_keys($files);
                // foreach ($file_keys as $key) {
                //     $file[$i][$key] = $files[$key][$i];
                // }
                if(!is_array($files['name'])){
                    foreach ($files as &$v) {
                        $v = [$v];
                    }
                }
                $upload_dir = wp_upload_dir();
                // echo "\n=====".__LINE__."\n";
                // print_r($_FILES);
                // echo "\n=====".__LINE__."\n";
                // print_r($files);
                foreach ($files['name'] as $key => $value) {
                // foreach ($files['name'] as $key => $value) {
    
                    $arr = get_post_meta( $pid, $meta_name, 1 );
                    if( $this->photos_cou <= count($arr) )break;
    
                    
                    // $item = $this->bindToCopyPhotoFile($key, $arr);
                    // if($item) $arr[] = $item;
                    
                    $item = $this->bindToCopyPhotoFile($pid, $type, $files, $key);
                    if($item){
                        $this->photos_cou--;
                        $arr = get_post_meta( $pid, $meta_name, 1 );
                        if( $this->photos_cou <= count($arr) )break;
                        if(!$arr || !is_array($arr)) $arr =[];
                        $arr[] = $item;
                        update_post_meta( $pid, $meta_name, $arr );
                        $prev = $item;
                        // $prev = $item[$checkBy];
                    }
                }
            }
        }
        return $prev;
    }


    private Array $gallery_thumbnail_urls = [];
    /**
     * Сохранение фото галереи
     * Summary of savePhoto
     * @param mixed $pid
     * @return void
     */
    function savePhoto($pid){
        // addMess([$pid], 'savePhoto '.$pid);
        if(!$pid) return;

        
        $upload_dir = wp_upload_dir();
        // Создание папки для видео, если она не существует
        $video_dir = $upload_dir['basedir'] . '/photos';
        if (!file_exists($video_dir)) {
            mkdir($video_dir, 0755, true);
        }
        $video_dir_pre = $upload_dir['basedir'] . '/photos-thumbnails';
        if (!file_exists($video_dir)) {
            mkdir($video_dir, 0755, true);
        }
        $type = 'photo'; // loaded data type
        $f_ = 'photo';
        $f = 'info-photo-file';
        $fd = 'photo-file-remove';
        $arr = get_post_meta( $pid, $f_, 1 );
        
        if(!$arr || !is_array($arr)) $arr =[];

        // remove file
        // addMess($_POST, 'savePhoto '.$pid);
        $this->remItem($arr, 'photo-file-remove', 'url_pre', 'photos_cou');


        $files = [];
        if(isset($_FILES[$f])){
            $files = $_FILES[$f];
        }
        // addMess($files, 'photo files');
        
        // Обработка фотографий для галереи
        $gallery_imgs = [];
        $gallery_urls = [];
        $this->gallery_thumbnail_urls = [];
        if (!empty($files)) {
            $upload_dir = wp_upload_dir();
            foreach ($files['name'] as $key => $value) {

                if( --$this->photos_cou < 0 )break;

                // load as group of multiple
                $item = $this->bindToCopyPhotoFile($pid, $type, $files, $key, $arr);
                // if($item) $arr[] = $item;
            }
        }
        //--
        $fields1 = [
            // 'photo' => serialize($gallery_imgs),
            'photo' => $arr,
        ];
        // addMess($arr, 'photo files $arr');

        foreach($fields1 as $k=>$v){
            update_post_meta( $pid, $k, $v ); 
        }
    }


    /**
     * Сохранение видео галереи
     * Summary of saveVideo
     * @param mixed $pid
     * @return void
     */
    function saveVideo($pid){
        if(!$pid) return;
        
        $upload_dir = wp_upload_dir();
        // Создание папки для видео, если она не существует
        $video_dir = $upload_dir['basedir'] . '/videos';
        if (!file_exists($video_dir)) {
            mkdir($video_dir, 0755, true);
        }
        $f_ = 'video';
        $f = 'video-video-file';
        $fd = 'video-file-remove';
        $arr = get_post_meta( $pid, $f_, 1 );
        
        if(!$arr || !is_array($arr)) $arr =[];

        // remove file
        $rem = filter_input(INPUT_POST, $fd, FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);
        if(!$rem) $rem =[];
        $new_arr = [];
        if($arr && $rem){
            foreach($arr as $k=>$v){
                if(!in_array(($k+1),$rem)){
                    $new_arr[]=$v;
                    --$this->videos_cou;
                }
                else{
                    unset($v['path']);
                }
            }
            $arr = $new_arr;
        }


        $files = [];
        if(isset($_FILES[$f])){
            $files = $_FILES[$f];
        }
        // addMess($files, 'video files');
    
        // Обработка загруженных видео
        $video_urls = [];
        if (!empty($files)) {
            foreach ($files['name'] as $key => $value) {
                if( --$this->videos_cou < 0 )break;
                if ($files['error'][$key] === UPLOAD_ERR_OK) {
                    $video_file = $files;
                    $video_size = $video_file['size'][$key];
                    $video_type = $video_file['type'][$key];
                    
                    // Проверка формата и размера файла
                    if ($video_type !== 'video/mp4') {
                        continue;
                    }
                    
                    if ($video_size > 200 * 1024 * 1024) { // 200 МБ
                        continue;
                    }

                    $from = $video_file['tmp_name'][$key];
                    $name = 'pm_'.$pid.'__'.basename($video_file['name'][$key]); // page memory PM_
                    $newname = $name; // relplace to free name
                    $name = $newname;
                    $name = $this->newFileName($name, $video_dir, $pid);
                    $to = $video_dir . '/' . $name;
                    move_uploaded_file($from, $to);
                    $url = $upload_dir['baseurlpath'] . '/videos/' . $name;
                    
                    // $video_path = $video_dir . '/' . basename($video_file['name'][$key]);
                    // move_uploaded_file($video_file['tmp_name'][$key], $video_path);
                    // $video_url = $upload_dir['baseurl'] . '/videos/' . basename($video_file['name'][$key]);
                    // $video_urls[] = $video_url;

                    $item = [];
                    $item['title'] = $name;
                    $item['name'] = $name;
                    $item['path'] = $to;
                    $item['url'] = $url;
                    $arr[] = $item;
                }
            }
    
            // // Ограничение на количество видео
            // if (count($video_urls) > 5) {
            //     $video_urls = array_slice($video_urls, 0, 5); // Обрезаем до 5 видео
            // }
        }
        //--
        $fields1 = [
            // 'video' => implode('||',$video_urls),
            // 'videos' => implode('||',$video_urls),
            'video' => $arr,
        ];
        // addMess($arr, 'video files $arr');

        foreach($fields1 as $k=>$v){
            update_post_meta( $pid, $k, $v ); 
        }
    }

    /**
     * Сохранение аудио галереи
     * Summary of saveAudio
     * @param mixed $pid
     * @return void
     */
    function saveAudio($pid){
        if(!$pid) return;
        
        $upload_dir = wp_upload_dir();
        // Создание папки для видео, если она не существует
        $video_dir = $upload_dir['basedir'] . '/audio';
        if (!file_exists($video_dir)) {
            mkdir($video_dir, 0755, true);
        }
        $f_ = 'audio';
        $f = 'audio-audio-file';
        $fd = 'audio-file-remove';
        $arr = get_post_meta( $pid, $f_, 1 );
        // addMess($_FILES[$f],$f);
        
        if(!$arr || !is_array($arr)) $arr =[];

        // remove file
        $rem = filter_input(INPUT_POST, $fd, FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);
        if(!$rem) $rem =[];
        $new_arr = [];
        if($arr && $rem){
            foreach($arr as $k=>$v){
                if(!in_array(($k+1),$rem)){
                    $new_arr[]=$v;
                    --$this->audio_cou;
                }
                else{
                    unset($v['path']);
                }
            }
            $arr = $new_arr;
        }


        $files = [];
        if(isset($_FILES[$f])){
            $files = $_FILES[$f];
        }
        // addMess($files, 'audio files');
    
        // Обработка загруженных видео
        $video_urls = [];
        if ($files) {
            // addMess($this->audio_cou, "audio_cou");
            foreach ($files['name'] as $key => $value) {

                if( --$this->audio_cou < 0 )break;
                if ($files['error'][$key] === UPLOAD_ERR_OK) {
                    $video_file = $files;
                    $video_size = $video_file['size'][$key];
                    $video_type = $video_file['type'][$key];
                    
                    // Проверка формата и размера файла
                    if ($video_type !== 'audio/mpeg') {
                        continue;
                    }
                    else{
                        // addMess($video_type, "!== 'audio/mpeg'");
                    }
                    
                    // restriction for audio is 20M
                    if ($video_size > 50 * 1024 * 1024) { // 200 МБ
                        continue;
                    }
                    else{
                        // addMess($video_size/1024/1024, "> 20");
                    }
                    $from = $video_file['tmp_name'][$key];
                    $name = basename($video_file['name'][$key]);
                    $newname = $name; // relplace to free name
                    $name = $newname;
                    $name = $this->newFileName($name, $video_dir, $pid);
                    $to = $video_dir . '/' . $name;
                    move_uploaded_file($from, $to);
                    // $url = $upload_dir['baseurlpath'] . '/audio/' . $name;
                    $path = $upload_dir['baseurl'];
                    $path = parse_url($path, PHP_URL_PATH);
                    $url = $path . '/audio/' . $name;
                    
                    // $video_path = $video_dir . '/' . basename($video_file['name'][$key]);
                    // move_uploaded_file($video_file['tmp_name'][$key], $video_path);
                    // $video_url = $upload_dir['baseurl'] . '/audio/' . basename($video_file['name'][$key]);

                    $item = [];
                    $item['title'] = $name;
                    $item['name'] = $name;
                    $item['path'] = $to;
                    $item['url'] = $url;
                    $arr[] = $item;
                }else{
                    // addMess($files['error'][$key], "!== UPLOAD_ERR_OK");
                }
            }
    
            // Ограничение на количество видео
            // if (count($video_urls) > 5) {
            //     $video_urls = array_slice($video_urls, 0, 5); // Обрезаем до 5 видео
            // }
        }else{
            // addMess(count($files), '!$files');
        }
        // addMess($arr, 'audio files $arr');
        //--
        $fields1 = [
            // 'audio-audio-file' => implode('||',$video_urls),
            // 'audio-audio-file' => $arr,
            'audio' => $arr,
        ];

        foreach($fields1 as $k=>$v){
            update_post_meta( $pid, $k, $v ); 
        }
    }

}