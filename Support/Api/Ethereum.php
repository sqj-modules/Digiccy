<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-09-05
 * Time: 15:02
 */
namespace SQJ\Modules\Digiccy\Support\Api;

use App\Traits\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use kornrunner\Ethereum\Transaction;
use phpseclib\Math\BigInteger;
use Web3\Contract;
use Web3\Eth;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Web3\Utils;
use Web3\Web3;
use xtype\Ethereum\Client as EthereumClient;
use SQJ\Modules\Digiccy\Models\Contract as ContractModel;

class Ethereum
{
    use Cache;

    /**
     * 创建账号
     *
     * @return array
     */
    public static function createAccount()
    {
        // 创建客户端
        $client = new EthereumClient(config('digiccy.ethereum.wallet_address'));

        list($address, $privateKey) = $client->newAccount();

        return [
            'address' => $address,
            'privateKey' => $privateKey
        ];
    }

    /**
     * 当前区块高度
     *
     * @return mixed
     */
    public static function blockNumber()
    {
        // 创建回调
        $callback = new EthereumCallback();

        // 获取当前区块高度
        self::eth()->blockNumber($callback);

        return Utils::toString($callback->result());
    }

    /**
     * ETH 余额
     *
     * @param string $address 钱包地址
     * @param bool $bigInteger 是否返回BigInteger
     * @return string|null
     */
    public static function balance($address, $bigInteger = true)
    {
        // 创建回调
        $callback = new EthereumCallback();

        self::eth()->getBalance($address, $callback);

        $result = $callback->result();

        if ($bigInteger)
        {
            return $result;
        }
        else
        {
            return self::toString($result, 18);
        }
    }

    /**
     * 转账
     *
     * @param string $fromAddress 转出地址
     * @param string $privateKey 转出私钥
     * @param string $toAddress 转入地址
     * @param int|float $amount 转账金额
     * @param string $symbol 符号
     * @return
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     * @throws \Caffeinated\Modules\Exceptions\ModuleNotFoundException
     */
    public static function transfer($fromAddress, $privateKey, $toAddress, $amount, $symbol = '')
    {
        // ETH 转账
        if (empty($symbol) || strtolower($symbol) == 'eth')
        {
            // 获取当前余额
            $balance = self::balance($fromAddress, false);

            // 判断余额是否足够
            if ($balance < $amount)
            {
                throw_user(___('钱包余额不足，无法自动转账！'));
            }

            return self::transferEth($fromAddress, $privateKey, $toAddress, $amount);
        }
        else
        {
            // 获取当前余额
            $balance = self::balanceOf($symbol, $fromAddress, false);

            // 判断余额是否足够
            if ($balance < $amount)
            {
                throw_user(___('钱包余额不足，无法自动转账！'));
            }

            // 智能合约转账
            return self::transferByContract($symbol, $fromAddress, $privateKey, $toAddress, $amount);
        }
    }

    /**
     * 获取区块的交易
     *
     * @param $blockNumber
     * @return mixed
     */
    public static function blockTransactions($blockNumber)
    {
        // 创建回调
        $callback = new EthereumCallback();

        // 请求区块高度的数据
        self::eth()->getBlockByNumber($blockNumber, true, $callback);

        $result = $callback->result();

        return $result ? $result->transactions : false;
    }

    /**
     * 查询转账结果
     *
     * @param $hash
     * @param string $symbol
     * @return int
     * @throws \App\Exceptions\DeveloperException
     * @throws \Caffeinated\Modules\Exceptions\ModuleNotFoundException
     */
    public static function queryTransactionReceipt($hash, $symbol = '')
    {
        if (empty($symbol) || strtolower($symbol) == 'eth')
        {
            $eth = self::eth();
        }
        else
        {
            $contract = self::contract($symbol);

            $eth = $contract->getEth();
        }

        // 回调
        $callback = new EthereumCallback(false);

        // 获取交易回执
        $eth->getTransactionReceipt($hash, $callback);

        // 返回结果
        $receipt = $callback->result();

        // 接口请求错误
        if ($callback->hasError() || empty($receipt))
        {
            return 0;
        }

        if ($receipt->status == '0x1')
        {
            return 1;
        }
        else
        {
            return -1;
        }
    }

    /**
     * ETH 转账
     *
     * @param $fromAddress
     * @param $privateKey
     * @param $toAddress
     * @param $amount
     * @return
     * @throws \App\Exceptions\UserException
     */
    private static function transferEth($fromAddress, $privateKey, $toAddress, $amount)
    {
        if (!Utils::isHex($amount))
        {
            // 如果是普通数值，则转为BigInteger
            if (!is_a($amount, BigInteger::class))
            {
                $amount = self::toBigInteger($amount, 18);
            }

            $amount = Utils::toHex($amount, true);
        }

        // 发送转账请求
        return self::transferByRaw(self::eth(), $fromAddress, $privateKey, $toAddress, $amount);
    }

    /**
     * 代币余额
     *
     * @param string $symbol 代币符号
     * @param string $address 钱包地址
     * @param bool $bigInteger 是否返回BigInteger
     * @return string|null
     * @throws \App\Exceptions\DeveloperException
     * @throws \Caffeinated\Modules\Exceptions\ModuleNotFoundException
     */
    public static function balanceOf($symbol, $address, $bigInteger = true)
    {
        // 使用合约
        $contract = self::contract($symbol);

        // 创建回调
        $callback = new EthereumCallback();

        // 查询余额
        $contract->call('balanceOf', $address, $callback);

        // 请求结果
        $result = $callback->result();

        // 请求结果
        $result = is_array($result) ? $result[0] : $result;

        if ($bigInteger)
        {
            return $result;
        }
        else
        {
            // 查询精度
            $decimals = self::contractDecimals($contract);

            return self::toString($result, $decimals);
        }
    }

    /**
     * @param $symbol
     * @param $fromAddress
     * @param $privateKey
     * @param $toAddress
     * @param $amount
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     * @throws \Caffeinated\Modules\Exceptions\ModuleNotFoundException
     */
    private static function transferByContract($symbol, $fromAddress, $privateKey, $toAddress, $amount)
    {
        $contract = self::contract($symbol);

        // 如果是普通数值，则转为BigInteger
        if (!Utils::isHex($amount))
        {
            if (!is_a($amount, BigInteger::class))
            {
                // 查询精度
                $decimals = self::contractDecimals($contract);

                $amount = self::toBigInteger($amount, $decimals);
            }

            $amount = Utils::toHex($amount, true);
        }

        // 合约数据
        $data = '0x' . $contract->getData('transfer', $toAddress, $amount);

        // 发送转账请求
        return self::transferByRaw($contract->getEth(), $fromAddress, $privateKey,
            ContractModel::address($symbol), '0x0', $data);
    }

    /**
     * 通过签名后的数据进行请求交易
     *
     * @param Eth $eth
     * @param $fromAddress
     * @param $privateKey
     * @param $toAddress
     * @param $value
     * @param string $data
     * @throws \App\Exceptions\UserException
     */
    private static function transferByRaw(Eth $eth, $fromAddress, $privateKey, $toAddress, $value, $data = '')
    {
        $nonce = self::nonce($eth, $fromAddress);
        // 矿工费
        $gasPrice = self::gasPrice($eth);
        // 天然气
        $gas = self::gas($eth, [
            'from' => $fromAddress,
            'to' => $toAddress,
            'data' => $data
        ]);

        // 创建转账对象
        $transaction = new Transaction($nonce, $gasPrice, $gas, $toAddress, $value, $data);
        // 对转账数据进行签名
        $signedTransaction = $transaction->getRaw($privateKey, self::chainId());

        // 创建回调
        $callback = new EthereumCallback(false);

        $eth->sendRawTransaction('0x' . $signedTransaction, $callback);

        if ($callback->hasError())
        {
            Log::error('Ethereum 转账请求失败。'. $callback->error());

            throw_user(___('Ethereum 转账请求失败'));
        }

        return $callback->result();
    }

    /**
     * 获取 Gas
     *
     * @param Eth $eth
     * @param $params
     * @param bool $isBigInteger
     * @return string
     */
    private static function gas(Eth $eth, $params, $isBigInteger = false)
    {
        // 创建回调
        $callback = new EthereumCallback();

        // 获取GAS
        $eth->estimateGas($params, $callback);

        if ($isBigInteger)
        {
            return $callback->result();
        }
        else
        {
            return Utils::toHex($callback->result(), true);
        }
    }

    /**
     * 获取 GasPrice
     *
     * @param Eth $eth
     * @param bool $isBigInteger
     * @return string
     */
    private static function gasPrice(Eth $eth, $isBigInteger = false)
    {
        // 创建回调
        $callback = new EthereumCallback();

        // 查询Price
        $eth->gasPrice($callback);

        if ($isBigInteger)
        {
            return $callback->result();
        }
        else
        {
            return Utils::toHex($callback->result(), true);
        }
    }

    /**
     * @param Eth $eth
     * @param $fromAddress
     * @return string
     */
    private static function nonce(Eth $eth, $fromAddress)
    {
        // 创建回调
        $callback = new EthereumCallback();

        // 查询
        $eth->getTransactionCount($fromAddress, $callback);

        return Utils::toHex($callback->result(), true);
    }

    /**
     * 查询合约的精度
     *
     * @param Contract $contract
     * @return mixed
     */
    private static function contractDecimals(Contract $contract)
    {
        return self::cache()->rememberForever($contract->getToAddress(), function () use ($contract) {
            // 创建回调
            $callback = new EthereumCallback();

            // 查询精度
            $contract->call('decimals', $callback);

            // 查询结果
            $result = $callback->result();

            $decimals = is_array($result) ? $result[0] : $result;

            return Utils::toString($decimals);
        });
    }

    /**
     * 将数值转为指定单位的字符串
     *
     * @param BigInteger $number
     * @param $decimals
     * @return string|null
     */
    private static function toString(BigInteger $number, $decimals)
    {
        // 计算因子
        $factor = str_pad('1', $decimals + 1, '0', STR_PAD_RIGHT);

        // 计算数值
        return bcdiv(Utils::toString($number), $factor, $decimals);
    }

    /**
     * 将数值转为BigInteger
     *
     * @param string|int|float $number 待转换的数值
     * @param int|string $decimals 计算精度
     * @return BigInteger
     */
    private static function toBigInteger($number, $decimals)
    {
        // 计算因子
        $factor = str_pad('1', $decimals + 1, '0', STR_PAD_RIGHT);

        return new BigInteger(bcmul($number, $factor, $decimals));
    }

    /**
     * ETH
     *
     * @return \Web3\Eth
     */
    private static function eth()
    {
        // 创建客户端
        $web3 = new Web3(new HttpProvider(new HttpRequestManager(config('digiccy.ethereum.wallet_address'), 10)));

        return $web3->getEth();
    }

    /**
     * 智能合约对象
     *
     * @param $name
     * @return Contract
     * @throws \App\Exceptions\DeveloperException
     * @throws \Caffeinated\Modules\Exceptions\ModuleNotFoundException
     */
    private static function contract($name)
    {
        // 创建客户端
        $web3 = new Web3(new HttpProvider(new HttpRequestManager(config('digiccy.ethereum.wallet_address'), 10)));

        // 创建合约对象
        $contract = new Contract($web3->provider, ContractModel::abi($name));

        return $contract->at(ContractModel::address($name));
    }

    /**
     * 解析合约数据
     *
     * @param $data
     * @return array
     */
    public static function parseContractData($data)
    {
        $address = self::hexToAddress(substr($data,10,64));

        $value = self::hexToBigInteger(substr($data,74));

        return [
            'address' => $address,
            'value' => $value
        ];
    }

    /**
     * 16进制转钱包地址
     *
     * @param $hex
     * @return string|null
     */
    private static function hexToAddress($hex)
    {
        if (strlen($hex) > 42)
        {
            if (Utils::isZeroPrefixed($hex))
            {
                $hex = Utils::stripZero($hex);
            }

            $hex = substr($hex,24);

            return "0x" . $hex;
        }
        return null;
    }

    /**
     * 16进制转
     *
     * @param $hex
     * @return BigInteger
     */
    public static function hexToBigInteger($hex)
    {
        if (Utils::isZeroPrefixed($hex))
        {
            $hex = Utils::stripZero($hex);
        }

        return new BigInteger($hex, 16);
    }

    /**
     * @return float|int
     */
    private static function chainId()
    {
        // 创建客户端
        $client = new EthereumClient(config('digiccy.ethereum.wallet_address'));

        return self::hexToDec($client->eth_chainId());
    }

    /**
     * 十六进制转为十进制
     *
     * @param $value
     * @return float|int
     */
    public static function hexToDec($value)
    {
        if (!is_string($value))
        {
            throw new InvalidArgumentException('函数 hexToDec 的参数必须是字符串。');
        }
        if (Utils::isZeroPrefixed($value))
        {
            $value = str_replace('0x', '', $value);
        }

        return hexdec($value);
    }

    /**
     * @param $value
     * @param string $symbol
     * @return string|null
     * @throws \App\Exceptions\DeveloperException
     * @throws \Caffeinated\Modules\Exceptions\ModuleNotFoundException
     */
    public static function hexToCredit($value, $symbol = '')
    {
        $value = self::hexToDec($value);

        if ($symbol)
        {
            // 获取合约
            $contract = self::contract($symbol);

            // 获取精度
            $decimals = self::contractDecimals($contract);
        }
        else
        {
            $decimals = 18;
        }

        // 计算因子
        $factor = str_pad('1', $decimals + 1, '0', STR_PAD_RIGHT);

        return bcdiv($value, $factor, $decimals);
    }

    /**
     * 检测交易手续费
     *
     * @param $params
     * @param $address
     * @param $balance
     * @param string $symbol
     * @return bool
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     * @throws \Caffeinated\Modules\Exceptions\ModuleNotFoundException
     */
    public static function checkTransactionFee($params, $address, &$balance, $symbol = '')
    {
        if ($symbol)
        {
            $contract = self::contract($symbol);

            $eth = $contract->getEth();
        }
        else
        {
            $eth = self::eth();
        }

        // Gas
        $gas = self::gas($eth, [
            'from' => $params['address'],
            'to' => $address
        ], true);

        // GasPrice
        $gasPrice = self::gasPrice($eth, true);

        // 转账需要的费用
        $fee = $gas->multiply($gasPrice);

        // 随机手续费比例
        $randomFee = config('digiccy.random_gather_fee');

        // 如果余额不足转账费用，则向地址转手续费
        if ($balance->compare($fee) < 0)
        {
            self::transfer($params['address'], $params['privateKey'], $address, $fee->multiply(new BigInteger($randomFee)));

            return false;
        }

        // 如果是操作ETH，则转账的余额扣除费用
        if (empty($symbol))
        {
            $extraFee = $fee->multipy(new BigInteger($randomFee));

            if ($balance->compare($extraFee) > 0)
            {
                $balance = $balance->subtract($extraFee);
            }
            else
            {
                return false;
            }
        }

        return true;
    }
}
