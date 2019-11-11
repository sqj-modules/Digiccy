<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-08-21
 * Time: 16:16
 */

use App\Models\UserCredit;

return [

    /**
     * 货币的总位数
     */
    'currency_total' => env('DIGICCY_CURRENCY_TOTAL', 38),

    /**
     * 货币的小数位
     */
    'currency_place' => env('DIGICCY_CURRENCY_PLACE', 18),

    /**
     * 默认市场
     */
    'default' => env('DIGICCY_DEFAULT_MARKET', 'huoBi'),

    /**
     * 报价币种
     */
    'quoteCurrency' => array_filter(explode(',', env('DIGICCY_QUOTE_CURRENCY', 'usdt'))),

    'ethereum' => [
        'wallet_address' => env('DIGICCY_ETHEREUM_WALLET_ADDRESS', 'http://localhost:8545')
    ],

    /**
     * 自动归总时手续费的倍数，防止转来后手续费仍旧不足的问题
     */
    'random_gather_fee' => env('DIGICCY_RANDOM_GATHER_FEE', 5),

    /**
     * 是否启用自动充值
     *
     * 开启自动充值时，每个用户自动生成相应的钱包地址，会员通过前端自己的钱包地址进行充值。
     *
     * 禁用自动充值时，用户线下向平台的钱包地址打款，提交申请，后台审核通过后，充值到账。
     */
    'auto_recharge' => env('DIGICCY_AUTO_RECHARGE', true),

    /**
     * 自动充币的钱包
     */
    'auto_recharge_credit' => env('DIGICCY_AUTO_RECHARGE_CREDITS', UserCredit::W_BALANCE),

    /**
     * 允许充币的钱包字段
     */
    'recharge_credits' => explode(',', env('DIGICCY_RECHARGE_CREDITS', UserCredit::W_BALANCE)),

    /**
     * 是否开启自动提现
     *
     * 开启自动提现时，后台通过提币申请则自动到账
     */
    'auto_withdrawal' => env('DIGICCY_AUTO_WITHDRAWAL', true),

    /**
     * 允许提币的钱包字段
     */
    'withdrawal_credits' => explode(',', env('DIGICCY_WITHDRAWAL_CREDITS', UserCredit::W_BALANCE)),

    /**
     * 默认的符号
     */
    'symbol' => env('DIGICCY_SYMBOL', 'usdt'),

    'markets' => [
        'huoBi' => [
            'market' => 'huoBi',
            'domain' => env('DIGICCY_HUOBI_DOMAIN', ''),
            'accessKey' => env('DIGICCY_HUOBI_ACCESS_KEY', ''),
            'secretKey' => env('DIGICCY_HUOBI_SECRET_KEY', '')
        ]
    ]
];
