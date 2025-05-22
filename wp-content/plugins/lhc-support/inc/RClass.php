<?php
/**
 * RClass.php
 * User Role Access Class
 * Author: WSD
 * Created: 2024-10-08 00:05
 */

class RClass{
    // текущий пользователь
    public $user = null;
    // ID пользователя userId
    public $uid = null;
    // существующие роли
    public $roles = [
        'guest',
        'customer',
        'admin',
        'manager',
        'partner',
        'referer',
        'referal',
        'memoouner',
        'memoouner_pay',
        'memoouner_econ',
        'memoouner_std',
        'memoouner_max',
        'memoouner_hero',
        'memoouner_qr',
    ];
    // действия доступные ролям
    public $rolesCan = [
        'guest'=> [
            'login',
        ],
        'customer'=> [
            'logout',
            'memo_create',
        ],
        'admin'=> [
            '',
        ],
        'manager'=> [
            'view_partner_candidates',
            'view_partners',
        ],
        'partner'=> [
            'view_account',
            'view_referals',
        ],
        'referer'=> [
            '',
        ],
        'referal'=> [
            '',
        ],
        'memoouner'=> [
            'memo_edit',
            'edit',
            'edit_info',
            'edit_photo',
            'edit_text',
            'edit_cementry',
        ],
        'memoouner_pay'=> [
            'memo_pay',
        ],
        'memoouner_econ'=> [
            'memo_edit',
            'edit_relations',
        ],
        'memoouner_std'=> [
            'memo_edit',
            'edit_audio',
            'edit_video',
            'edit_relations',
        ],
        'memoouner_max'=> [
            'memo_edit',
            'edit_audio',
            'edit_video',
            'edit_relations',
        ],
        'memoouner_hero'=> [
            'memo_edit',
            'edit_audio',
            'edit_video',
            'edit_relations',
        ],
        'memoouner_qr'=> [
            'query_qr',
        ],
        ''=> [
            '',
        ],
    ];
    // текущие роли
    public $role = [
        '',
    ];
    // Существующие действия
    public $ables = [
        'login',
        'logout',
        'memo_create',
        'memo_edit',
        'memo_pay',
        'view_account',
        'view_rererals',
        'view_partner_candidates',
        'view_partners',
        'edit',
        'edit_info',
        'edit_photo',
        'edit_text',
        'edit_cementry',
        'edit_audio',
        'edit_video',
        'edit_relations',
        'query_qr',
        '',
    ];
    // Доступные действия
    public $can = [
        '',
        // ''=> '',
    ];
    // Пользователь может
    public $has = [
        '',
    ];


    public function __construct()
    {
    }


    public function getRole(){}


    public function getAbles(){}


    /**
     * Обновить возможности
     */
    public function upRole(){

    }


    /**
     * Имеет ли пользователь роль
     */
    public function useris($role){
        if(!$role) return false;
        if(in_array($role, $this->role)) return true;
        return false;
    }


    /**
     * Доступно ли пользователю действие
     */
    public function can($can='')
    {
        if(!$can) return false;
        if(in_array($can, $this->can)) return true;
        return false;
    }

}