<?php

namespace Scorpion\Currency\Console;

use Illuminate\Console\Command;

class Cleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currency:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Очистка кэша валют';

    /**
     * Currency instance
     *
     * @var \Scorpion\Currency\Currency
     */
    protected $currency;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
		$this->currency = app('currency');
		
        parent::__construct();
    }

    /**
     * Execute the console command for Laravel 5.4 and below
     *
     * @return void
     */
    public function fire()
    {
		$this->handle();
    }
	
	/**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // Clear cache
        $this->currency->clearCache();
        $this->comment('Очистка кэша выполнена.');

        // Force the system to rebuild cache
        $this->currency->getCurrencies();
        $this->comment('Обновления кэша валют выполнена.');
    }
}
