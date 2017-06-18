<?php

namespace Scorpion\Cbr\Console;

use Illuminate\Console\Command;

class Cleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cbr:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Очистка кэша валют';

    /**
     * Currency instance
     *
     * @var \Scorpion\Cbr\Currency
     */
    protected $currency;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->currency = app('currency');
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        // Clear cache
        $this->currency->clearCache();
        $this->comment('Очистка кэша выполнена.');

        // Force the system to rebuild cache
        $this->currency->getCurrencies();
        $this->comment('Обновления кэша валют выполнена.');
    }
}