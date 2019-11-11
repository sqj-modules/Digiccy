<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-09-06
 * Time: 21:07
 */
namespace SQJ\Modules\Digiccy\Support\Api;

use Illuminate\Support\Facades\Log;

class EthereumCallback
{
    private $throwable;
    private $error = '';
    private $result;

    public function result()
    {
        return $this->result;
    }

    /**
     * 是否有错
     *
     * @return bool
     */
    public function hasError()
    {
        return $this->error !== '';
    }

    public function error()
    {
        return $this->error;
    }

    public function __construct($throwable = true)
    {
        $this->throwable = $throwable;
    }

    public function __invoke($error, $result)
    {
        // TODO: Implement __invoke() method.
        if ($error !== null)
        {
            // 记录错误信息
            Log::error($error->getMessage());

            if ($this->throwable)
            {
                // 抛出错误提醒
                throw_user(___('智能合约请求失败！！！'));
            }
            else
            {
                $this->error = $error->getMessage();
            }
        }

        $this->result = $result;
    }
}
