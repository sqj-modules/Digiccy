<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-08-22
 * Time: 00:21
 */
use Illuminate\Support\Facades\Broadcast;
use SQJ\Modules\Digiccy\Broadcasting\MarketTickerChannel;

Broadcast::channel('DigiccyMarketTicker', MarketTickerChannel::class, ['guards' => [SQJ_API_CLIENT_PORTAL]]);
