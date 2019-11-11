<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-08-22
 * Time: 00:27
 */
namespace SQJ\Modules\Digiccy\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class MarketTickerUpdated implements ShouldBroadcast
{
    use SerializesModels;

    public $tickerList = [];

    public function __construct($tickerList)
    {
        $this->tickerList = $tickerList;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|\Illuminate\Broadcasting\Channel[]
     */
    public function broadcastOn()
    {
        return [
            new PresenceChannel('DigiccyMarketTicker')
        ];
    }
}
