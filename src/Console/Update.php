<?php

namespace Scorpion\Cbr\Console;

use DateTime;
use Illuminate\Console\Command;

class Update extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cbr:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update exchange rates from an online source';

    /**
     * Currency instance
     *
     * @var \Scorpion\Cbr\Currency
     */
    protected $cbr;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        $this->cbr = app('cbr');

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        // Get Settings
        $defaultCurrency = $this->cbr->config('default');

        $this->updateFromCBR($defaultCurrency);

    }

    private function updateFromCBR($defaultCurrency)
    {
        $this->comment('Обновление валютных курсов с Центрального банка ...');
        $content = $this->request('http://www.cbr.ru/scripts/XML_daily.asp?date_req=' . date('d/m/Y'));

        $currencies = new \SimpleXMLElement($content);


        // Update each rate
        foreach ($currencies as $currency) {

            $this->cbr->getDriver()->update($currency->CharCode, [
                'exchange_rate' => $currency->Value,
            ]);
        }

        $this->call('currency:cleanup');
        $this->info('Курс валют обновлен!');
    }


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