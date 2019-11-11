<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-08-21
 * Time: 23:09
 */
namespace SQJ\Modules\Digiccy\Console\Commands;

use SQJ\Modules\Digiccy\Models\Currency;
use Illuminate\Console\Command;

class PollMergedTicker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'digiccy:poll-merged-ticker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '轮询查询市场行情';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Currency::queryMergedTickers();
    }
}
