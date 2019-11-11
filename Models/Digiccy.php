<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-09-10
 * Time: 10:00
 */
namespace SQJ\Modules\Digiccy\Models;

use App\Models\Base;
use App\Models\UserCredit;
use Illuminate\Support\Str;

class Digiccy extends Base
{
    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table ?? 'digiccy_' . Str::snake(Str::pluralStudly(class_basename($this)));
    }

    /**
     * 可提币的钱包
     *
     * @return \Illuminate\Config\Repository|mixed
     */
    public static function withdrawalCredits()
    {
        return config('digiccy.withdrawal_credits');
    }

    /**
     * 可充币的钱包
     *
     * @return \Illuminate\Config\Repository|mixed
     */
    public static function rechargeCredits()
    {
        return config('digiccy.recharge_credits');
    }

    /**
     * 启用的钱包
     *
     * @return array
     */
    public static function enabledCredits()
    {
        // 充币钱包
        $rechargeList = [];

        $rechargeCredits = Digiccy::rechargeCredits();

        foreach ($rechargeCredits as $credit)
        {
            $rechargeList[] = [
                'label' => UserCredit::creditName($credit),
                'value' => $credit
            ];
        }

        // 提币钱包
        $withdrawalList = [];

        $withdrawalCredits = Digiccy::withdrawalCredits();

        foreach ($withdrawalCredits as $credit)
        {
            $withdrawalList[] = [
                'label' => UserCredit::creditName($credit),
                'value' => $credit
            ];
        }

        return [
            'recharge' => $rechargeList,
            'withdrawal' => $withdrawalList
        ];
    }
}
