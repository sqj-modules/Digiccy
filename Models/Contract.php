<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-09-11
 * Time: 16:42
 */
namespace SQJ\Modules\Digiccy\Models;

use SQJ\Modules\Digiccy\Support\Settings;
use function Clue\StreamFilter\fun;

class Contract extends Digiccy
{
    protected $casts = [
        'recharges' => 'array',
        'withdrawals' => 'array'
    ];

    /**
     * 获取指定符号的合约地址
     *
     * @param $symbol
     * @return mixed
     */
    public static function address($symbol)
    {
        return self::cache()->rememberForever("address_{$symbol}", function () use ($symbol) {

            // 获取合约
            $contract = self::query()
                ->where('symbol', strtolower($symbol))
                ->first();

            if (empty($contract))
            {
                throw_developer('尚未配置合约');
            }

            return $contract['address'];
        });
    }

    /**
     * 获取指定符号的智能合约
     *
     * @param $symbol
     * @return mixed
     */
    public static function abi($symbol)
    {
        return self::cache()->rememberForever("abi_{$symbol}", function () use ($symbol) {

            // 获取合约
            $contract = self::query()
                ->where('symbol', strtolower($symbol))
                ->first();

            if (empty($contract))
            {
                throw_developer('尚未配置合约');
            }

            return $contract['abi'];
        });
    }

    /**
     * 获取启用的钱包合约
     *
     * @return mixed
     */
    public static function enabledDictionary()
    {
        return self::cache()->rememberForever('enabled_dictionary', function () {

            // 获取所有未禁用的列表
            $list = self::query()
                ->whereNull('disabled_at')
                ->get();

            // 字典数据
            $dictionary = [];

            foreach ($list as $item)
            {
                $dictionary[] = [
                    'label' => strtoupper($item['symbol']),
                    'value' => $item['id'],
                    'recharges' => $item['recharges'],
                    'withdrawals' => $item['withdrawals']
                ];
            }

            return $dictionary;
        });
    }

    /**
     * 可允许提现的合约
     *
     * @return mixed
     */
    public static function withdrawingDictionary()
    {
        return self::cache()->rememberForever('withdrawing_dictionary', function () {

            // 获取提币参数
            $params = Settings::get(Settings::WITHDRAWAL_PARAMS);

            if (!isset($params['contractList']))
            {
                return [];
            }

            // 获取所有未禁用的列表
            $list = self::query()
                ->whereNull('disabled_at')
                ->whereIn('id', $params['contractList'])
                ->get();

            // 字典数据
            $dictionary = [];

            foreach ($list as $item)
            {
                $dictionary[] = [
                    'label' => strtoupper($item['symbol']),
                    'value' => $item['id'],
                    'withdrawals' => $item['withdrawals']
                ];
            }

            return $dictionary;
        });
    }

    /**
     * 分页获取信息
     *
     * @param $lastId
     * @param $page
     * @param array $condition
     * @return array
     */
    public static function pageList($lastId, $page, $condition = [])
    {
        // 创建查询构造器
        $builder = self::query();

        // 符号名称
        if (isset($condition['symbol']) && $condition['symbol'] !== '')
        {
            $builder->where('symbol', $condition['symbol']);
        }

        // 是否禁用
        if (isset($condition['isDisabled']) && $condition['isDisabled'] !== '')
        {
            if ($condition['isDisabled'])
            {
                $builder->whereNotNull('disabled_at');
            }
            else
            {
                $builder->whereNull('disabled_at');
            }
        }

        if (self::fromAdmin())
        {
            $builder->selectRaw('*,'. SQJ_SQL_IS_DISABLED);
        }

        return self::paginate($builder, $lastId, $page);
    }

    /**
     * 添加合约
     *
     * @param $data
     * @throws \App\Exceptions\UserException
     */
    public static function insert($data)
    {
        // 创建合约
        $contract = new Contract();

        // 修改数据
        $contract->change($data);
    }

    /**
     * 修改合约数据
     *
     * @param $data
     * @return void
     * @throws \App\Exceptions\UserException
     */
    public function change($data)
    {
        // token
        if (isset($data['token']) && $data['token'] !== '')
        {
            $this['token'] = $data['token'];
        }

        // symbol
        if (isset($data['symbol']) && $data['symbol'] !== '')
        {
            // 判断符号是否使用
            $exists = self::query()
                ->where('id', '<>', $this['id'])
                ->where('symbol', $data['symbol'])
                ->exists();

            if ($exists)
            {
                throw_user(___('合约Symbol【%symbol%】已存在！', ['%symbol%' => $data['symbol']]));
            }

            $this['symbol'] = $data['symbol'];
        }

        // address
        if (isset($data['address']) && $data['address'] !== '')
        {
            $this['address'] = $data['address'];
        }

        // abi
        if (isset($data['abi']) && $data['abi'] !== '')
        {
            $this['abi'] = $data['abi'];
        }

        // 充币比例
        if (isset($data['recharges']) && is_array($data['recharges']))
        {
            $this['recharges'] = $data['recharges'];
        }

        // 提币比例
        if (isset($data['withdrawals']) && is_array($data['withdrawals']))
        {
            $this['withdrawals'] = $data['withdrawals'];
        }

        // 是否禁用
        if (isset($data['isDisabled']) && $data['isDisabled'] !== '')
        {
            $this['disabledAt'] = $data['isDisabled'] ? now_datetime() : null;
        }

        $this->save();

        // 清除缓存
        self::flushCache();
    }

    /**
     * 删除合约
     *
     * @throws \App\Exceptions\DeveloperException
     */
    public function remove()
    {
        if ($this['isSystem'])
        {
            throw_developer(___('系统默认合约无法删除'));
        }

        $this->delete();
    }

    /**
     * 是否禁用
     *
     * @return bool
     */
    public function getIsDisabledAttribute()
    {
        return !is_null($this['disabledAt']);
    }
}
