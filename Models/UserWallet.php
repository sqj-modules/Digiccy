<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-09-06
 * Time: 15:04
 */
namespace SQJ\Modules\Digiccy\Models;

use App\Models\User;
use SQJ\Modules\Digiccy\Support\Api\Ethereum;
use App\Traits\Cache;
use App\Utils\QrCode;

class UserWallet extends Digiccy
{
    use Cache;

    /**
     * 初始化会员钱包信息
     *
     * @param User $user
     * @return void
     * @throws \App\Exceptions\DeveloperException
     */
    public static function init(User $user)
    {
        // 创建钱包账号
        $account = Ethereum::createAccount();

        // 创建钱包
        $wallet = new UserWallet();

        // 钱包所属会员
        $wallet['userId'] = $user['id'];
        // 钱包地址
        $wallet['address'] = $account['address'];
        // 私钥
        $wallet['privateKey'] = $account['privateKey'];
        // 钱包地址二维码
        $wallet['qrCode'] = QrCode::generate($account['address'], 500);

        $wallet->save();

        // 清除缓存
        self::flushCache();
    }

    /**
     * 关联用户表
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 通过钱包地址获取用户
     *
     * @param $address
     * @return mixed
     */
    public static function getUserByAddress($address)
    {
        $wallet = self::query()
            ->where('address', $address)
            ->first();

        return $wallet['user'];
    }

    /**
     * 获取所有用户的钱包地址
     *
     * @return mixed
     */
    public static function addressDictionary()
    {
        return self::cache()->rememberForever('user_addresses', function () {
            return self::query()
                ->pluck('private_key', 'address');
        });
    }

    /**
     * 通过用户获取钱包
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     * @throws \App\Exceptions\DeveloperException
     */
    public static function getByUser(User $user)
    {
        $wallet = self::query()
            ->where('user_id', $user['id'])
            ->first();

        if (empty($wallet))
        {
            throw_developer('尚未初始化会员钱包');
        }

        return $wallet;
    }
}
