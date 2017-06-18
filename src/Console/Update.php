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
    protected $signature = 'cbr:update
                                {--o|openexchangerates : Get rates from OpenExchangeRates.org}
								{--cbr : Get rates from OpenExchangeRates.org}';

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
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        // Get Settings
        $defaultCurrency = $this->currency->config('default');

        if ($this->input->getOption('openexchangerates')) {
            if (!$api = $this->currency->config('api_key')) {
                $this->error('An API key is needed from OpenExchangeRates.org to continue.');

                return;
            }

            // Get rates
            $this->updateFromOpenExchangeRates($defaultCurrency, $api);
        } elseif ($this->input->getOption('cbr')) {
            // Get rates of CBR
            $this->updateFromCBRRates($defaultCurrency);
        } elseif ($this->input->getOption('nbrb')) {
            // Get rates of NBRB
            $this->updateFromNBRBRates($defaultCurrency);
        } else {
            // Get rates
            $this->updateFromYahoo($defaultCurrency);
        }
    }

    private function updateFromYahoo($defaultCurrency)
    {
        $this->comment('Updating currency exchange rates from Finance Yahoo...');

        $data = [];

        // Get all currencies
        foreach ($this->currency->getDriver()->all() as $code => $value) {
            $data[] = "{$defaultCurrency}{$code}=X";
        }

        // Ask Yahoo for exchange rate
        if ($data) {
            $content = $this->request('http://download.finance.yahoo.com/d/quotes.csv?s=' . implode(',', $data) . '&f=sl1&e=.csv');

            $lines = explode("\n", trim($content));

            // Update each rate
            foreach ($lines as $line) {
                $code = substr($line, 4, 3);
                $value = substr($line, 11, 6) * 1.00;

                if ($value) {
                    $this->currency->getDriver()->update($code, [
                        'exchange_rate' => $value,
                    ]);
                }
            }

            // Clear old cache
            $this->call('currency:cleanup');
        }

        $this->info('Complete');
    }

    private function updateFromOpenExchangeRates($defaultCurrency, $api)
    {
        $this->info('Updating currency exchange rates from OpenExchangeRates.org...');

        // Make request
        $content = json_decode($this->request("http://openexchangerates.org/api/latest.json?base={$defaultCurrency}&app_id={$api}"));

        // Error getting content?
        if (isset($content->error)) {
            $this->error($content->description);

            return;
        }

        // Parse timestamp for DB
        $timestamp = new DateTime(strtotime($content->timestamp));

        // Update each rate
        foreach ($content->rates as $code => $value) {
            $this->currency->getDriver()->update($code, [
                'exchange_rate' => $value,
                'updated_at' => $timestamp,
            ]);
        }

        $this->currency->clearCache();

        $this->info('Update!');
    }

    private function processXml($defaultCurrency, $resource = 'cbr')
    {
        $config = $this->app['config']['currency.' . $resource];
        $this->info($config['description']);
        $xml = $this->request($config['url'] . date($config['date_format']));
        $currencyRates = new \SimpleXMLElement($xml);
        $default = 1;
        $rates = array();
        $needed = $this->app['config']['currency.needed'];
        foreach ($currencyRates->$config['currency'] as $data) {
            if (in_array($data->CharCode, $needed)) {
                if ($data->CharCode == $defaultCurrency) {
                    $default = str_replace(",", ".", $data->$config['value']) / (float)$data->$config['nominal'];
                    $rates[] = array(
                        'code' => $defaultCurrency,
                        'value' => 1
                    );
                } else {
                    $rates[] = array(
                        'code' => $data->CharCode,
                        'value' => (str_replace(",", ".", $data->$config['value']) / (float)$data->$config['nominal'])
                    );
                }
            }
        }
        $rates[] = array(
            'code' => $config['code'],
            'value' => $default
        );
        foreach ($rates as $rate) {
            $this->app['db']->table($this->table_name)
                ->where('code', $rate['code'])
                ->update([
                    'value' => in_array($rate['code'], [$config['code'], $defaultCurrency]) ? $rate['value'] : $default / $rate['value'],
                    'updated_at' => new DateTime('now')
                ]);
        }
    }

    private function updateFromCBRRates($defaultCurrency)
    {
        $this->processXml($defaultCurrency);
        Cache::forget('currency');
        $this->info('Update!');
    }

    private function updateFromNBRBRates($defaultCurrency)
    {
        $this->processXml($defaultCurrency, 'nbrb');
        Cache::forget('currency');
        $this->info('Update!');
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