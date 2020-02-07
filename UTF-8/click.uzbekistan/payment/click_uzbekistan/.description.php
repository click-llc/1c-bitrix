<?php

if ( ! defined( 'B_PROLOG_INCLUDED' ) || B_PROLOG_INCLUDED !== true ) {
    die();
}

use \Bitrix\Main\Localization\Loc;

Loc::loadMessages( __FILE__ );
if( ! function_exists('cur_url') ) {
    function cur_url(){
        if(isset($_SERVER['HTTPS'])){
            $protocol = ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? "https" : "http";
        }
        else{
            $protocol = 'http';
        }
        return $protocol . "://" . $_SERVER['SERVER_NAME'];
    }
}


$data = [
    'NAME'         => Loc::getMessage( 'CLICK_UZ_NAME' ),
    'SORT'         => 750,
    'IS_AVAILABLE' => function_exists( 'curl_version' ),
    'CODES'        => [
        'CLICK_UZ_MERCHANT_ID'      => [
            'NAME'        => Loc::getMessage( 'CLICK_UZ_MERCHANT_ID' ),
            'SORT'        => 100,
            'DESCRIPTION' => Loc::getMessage( 'CLICK_UZ_MERCHANT_ID_DESC' ),
        ],
        'CLICK_UZ_MERCHANT_USER_ID' => [
            'NAME'        => Loc::getMessage( 'CLICK_UZ_MERCHANT_USER_ID' ),
            'SORT'        => 110,
            'DESCRIPTION' => Loc::getMessage( 'CLICK_UZ_MERCHANT_USER_ID_DESC' ),
        ],
        'CLICK_UZ_SERVICE_ID'       => [
            'NAME'        => Loc::getMessage( 'CLICK_UZ_SERVICE_ID' ),
            'SORT'        => 120,
            'DESCRIPTION' => Loc::getMessage( 'CLICK_UZ_SERVICE_ID_DESC' ),
        ],

        'CLICK_UZ_SECRET_KEY' => [
            'NAME'        => Loc::getMessage( 'CLICK_UZ_SECRET_KEY' ),
            'SORT'        => 130,
            'DESCRIPTION' => Loc::getMessage( 'CLICK_UZ_SECRET_KEY_DESC' ),
        ],
        'CLICK_UZ_PREPARE_URL'     => [
            'NAME'        => Loc::getMessage('CLICK_UZ_PREPARE_URL'),
            'SORT'        => 131,
            'DESCRIPTION' => Loc::getMessage('CLICK_UZ_NOTIFY_URL_DESC'),
            'DEFAULT'     => [
                'PROVIDER_VALUE' =>  cur_url() . '/bitrix/tools/sale_ps_result.php?click_uz=prepare',
                'PROVIDER_KEY'   => 'VALUE',
            ],
            'INPUT'       => [
                'TYPE'     => 'STRING',
                'VALUE'    => cur_url() . '/bitrix/tools/sale_ps_result.php?click_uz=prepare',
                'DISABLED' => 'Y',
            ],
        ],
        'CLICK_UZ_COMPLETE_URL'     => [
            'NAME'        => Loc::getMessage('CLICK_UZ_COMPLETE_URL'),
            'SORT'        => 132,
            'DESCRIPTION' => Loc::getMessage('CLICK_UZ_COMPLETE_URL_DESC'),
            'DEFAULT'     => [
                'PROVIDER_VALUE' => cur_url() . '/bitrix/tools/sale_ps_result.php?click_uz=complete',
                'PROVIDER_KEY'   => 'VALUE',
            ],
            'INPUT'       => [
                'TYPE'     => 'STRING',
                'VALUE'    => cur_url() . '/bitrix/tools/sale_ps_result.php?click_uz=complete',
                'DISABLED' => 'Y',
            ],
        ],
        'CLICK_UZ_USE_POPUP'  => [
            'NAME'    => Loc::getMessage( 'CLICK_UZ_USE_POPUP' ),
            'SORT'    => 140,
            'INPUT'   => [
                'TYPE' => 'Y/N',
            ],
            'DEFAULT' => [
                'PROVIDER_VALUE' => 'N',
                'PROVIDER_KEY'   => 'INPUT',
            ],
        ],
    ],
];
