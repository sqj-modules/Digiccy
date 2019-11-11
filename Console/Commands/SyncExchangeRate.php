<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-08-21
 * Time: 15:57
 */
namespace SQJ\Modules\Digiccy\Console\Commands;

use Illuminate\Console\Command;

class SyncExchangeRate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'digiccy:sync-exchange-rate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步币种兑换率';

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

    }
}
