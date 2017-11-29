<?php

namespace Scorpion\Currency\Console;

use DateTime;
use Illuminate\Console\Command;

class Update extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currency:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update exchange rates from an online source';

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
        // Get Settings
        $defaultCurrency = $this->currency->config('default');

        $this->updateFromCBR($defaultCurrency);
    }

    private function updateFromCBR($defaultCurrency)
    {
        $this->comment('Обновление валютных курсов с Центрального банка ...');
        $content = $this->request('http://www.cbr.ru/scripts/XML_daily.asp?date_req=' . date('d/m/Y'));

        $currencies = new \SimpleXMLElement($content);


        // Update each rate
        foreach ($currencies as $currency) {

			if ($currency->CharCode == $defaultCurrency) {
				$this->currency->getDriver()->update($currency->CharCode, [
					'exchange_rate' => 1,
				]);	
			} else {
				$this->currency->getDriver()->update($currency->CharCode, [
					'exchange_rate' => $currency->Value,
				]);
			}
        }

        $this->call('currency:cleanup');
        $this->info('Курс валют обновлен!');
    }

	/**
     * Make the request to the sever.
     *
     * @param $url
     *
     * @return string
     */
    private function request($url)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1");
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
        curl_setopt($ch, CURLOPT_MAXCONNECTS, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}