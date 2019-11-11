<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-08-21
 * Time: 16:08
 */
namespace SQJ\Modules\Digiccy\Support\Api;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;

class HuoBi
{
    /**
     * 获取所有的交易对
     *
     * @return mixed
     * @throws \App\Exceptions\DeveloperException
     */
    public static function allSymbols()
    {
        $result = self::get('/v1/common/symbols');

        return $result ? $result['data'] : [];
    }

    public static function syncExchangeRate()
    {
        $result = self::get('/v1/stable_coin/exchange_rate', [], true);

        return $result ?: [];
    }

    /**
     * 获取所有的币种
     *
     * @return mixed
     * @throws \App\Exceptions\DeveloperException
     */
    public static function allCurrencies()
    {
        $result = self::get('/v1/common/currencys');

        return $result ? $result['data'] : [];
    }

    /**
     * 获取指定交易对的聚合行情
     *
     * @param $symbol
     * @return mixed
     * @throws \App\Exceptions\DeveloperException
     */
    public static function mergedTicker($symbol)
    {
        $result = self::get('/market/detail/merged', [
            'symbol' => $symbol
        ]);

        return $result ? $result['tick'] : [];
    }

    /**
     * 进行get请求
     *
     * @param $api
     * @param array $params
     * @param bool $needSign
     * @return mixed
     * @throws \App\Exceptions\DeveloperException
     */
    private static function get($api, $params = [], $needSign = false)
    {
        // 创建请求客户端
        $client = new Client();

        // 是否需要需要签名
        if ($needSign)
        {
            self::sign('get', $api, $params);
        }

        // 请求的URI
        $uri = config('digiccy.markets.huoBi.domain') . $api . '?' . http_build_query($params);

        // 网络请求代理
        $httpProxy = config('app.http_proxy');

        if (!$httpProxy)
        {
            // 使用代理进行请求
            $response = $client->get($uri);
        }
        else
        {
            // 使用代理进行请求
            $response = $client->get($uri, [
                'proxy' => $httpProxy
            ]);
        }

        return self::parseResponse($response);
    }

    private static function post($api, $params = [], $needSign = false)
    {

    }

    /**
     * 解析回执报文
     *
     * @param ResponseInterface $response
     * @return mixed
     * @throws \App\Exceptions\DeveloperException
     */
    private static function parseResponse($response)
    {
        // 获取结果数据
        $contents = $response->getBody()->getContents();

        // 反JSON化数据
        $result = json_decode($contents, true);

        // 判断状态
        if ($result['status'] == 'ok')
        {
            return $result;
        }
        else
        {
            Log::error($result['err-msg']);

            throw_developer(___('火币API请求失败'));
        }
    }

    /**
     * 对请求数据进行签名
     *
     * @param string $method 请求方法
     * @param $api
     * @param $params
     * @return string
     */
    private static function sign($method, $api, &$params)
    {
        // 代价密的字符串
        $plaintext = '';

        // 添加请求方法
        $plaintext .= strtoupper($method) . "\n";

        // 处理域名
        $domain = str_replace(['http://', 'https://'], '', config('digiccy.markets.huoBi.domain'));

        // 添加访问域名
        $plaintext .= strtolower($domain) . "\n";

        // 访问的URL
        $plaintext .= $api . "\n";

        // AccessKey
        $params['AccessKeyId'] = config('digiccy.markets.huoBi.accessKey');
        // 签名方法
        $params['SignatureMethod'] = 'HmacSHA256';
        // 签名版本
        $params['SignatureVersion'] = '2';
        // 时间戳
        $params['Timestamp'] = now('UTC')->toDateTimeLocalString();

        // 参数排序
        ksort($params);

        // 拼接请求数据
        $plaintext .= http_build_query($params);

        // 数据进行hash加密
        $params['Signature'] = base64_encode(hash_hmac('sha256', $plaintext, config('digiccy.markets.huoBi.secretKey'), true));
    }
}
