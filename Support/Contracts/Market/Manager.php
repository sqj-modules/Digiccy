<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-08-21
 * Time: 21:57
 */
namespace SQJ\Modules\Digiccy\Support\Contracts\Market;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Events\Dispatcher;

class Manager
{
    /**
     * 当前 application 实例
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * @var array 受支持的市场
     */
    protected $markets = [];

    protected $customCreators = [];

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function market($name = null)
    {
        $name = $name ?: $this->getDefaultMarket();

        return $this->markets[$name] = $this->get($name);
    }

    protected function get($name)
    {
        return $this->markets[$name] ?? $this->resolve($name);
    }

    protected function resolve($name)
    {
        // 获取指定驱动的配置
        $config = $this->getConfig($name);

        // 如果配置为空，则返回null，且进行记录信息
        if (is_null($config))
        {
            Log::notice(___('数字货币市场【%name%】暂未支持！！！', [
                '%name%' => $name
            ]));
            return null;
        }

        if (isset($this->customCreators[$config['market']])) {
            return $this->callCustomCreator($config);
        } else {
            $driverMethod = 'create' . ucfirst($config['market']) . 'Market';

            if (method_exists($this, $driverMethod))
            {
                return $this->{$driverMethod}($config);
            }
            else
            {
                Log::notice(___('数字货币市场【%name%】暂未支持！！！', [
                    '%name%' => $config['market']
                ]));
                return null;
            }
        }
    }

    /**
     * @param $name
     * @return mixed
     */
    private function getConfig($name)
    {
        return $this->app['config']["digiccy.markets.{$name}"];
    }

    /**
     * @return mixed
     */
    private function getDefaultMarket()
    {
        return $this->app['config']['digiccy.default'];
    }

    protected function createHuoBiMarket($config)
    {
        return $this->repository(new HuoBiMarket($config));
    }

    /**
     * @param array $config
     * @return mixed
     */
    protected function callCustomCreator(array $config)
    {
        return $this->customCreators[$config['market']]($this->app, $config);
    }

    /**
     * @param Manager $market
     * @return Repository
     */
    public function repository(Market $market)
    {
        $repository = new Repository($market);

        if ($this->app->bound(Dispatcher::class)) {
            $repository->setEventDispatcher(
                $this->app[Dispatcher::class]
            );
        }

        return $repository;
    }

    /**
     * 扩展
     *
     * @param $market
     * @param Closure $callback
     * @return $this
     */
    public function extend($market, Closure $callback)
    {
        $this->customCreators[$market] = $callback->bindTo($this, $this);

        return $this;
    }

    public function __call($method, $parameters)
    {
        $market = $this->market();

        if ($market)
        {
            return $market->$method(...$parameters);
        }
        else
        {
            return false;
        }
    }
}
