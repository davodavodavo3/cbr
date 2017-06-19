<?php

namespace Scorpion\Cbr\Console;

use Illuminate\Support\Arr;
use Scorpion\Cbr\Cbr;
use Illuminate\Console\Command;

class Manage extends Command
{
    /**
 * The name and signature of the console command.
 *
 * @var string
 */
    protected $signature = 'cbr:manage
                                {action : Action to perform (add, update, or delete)}
                                {currency : Code or comma separated list of codes for currencies}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Управление валютными значениями';

    /**
     * Currency storage instance
     *
     * @var \Scorpion\Cbr\Contracts\DriverInterface
     */
    protected $storage;

    /**
     * All installable currencies.
     *
     * @var array
     */
    protected $currencies;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        $this->storage = app('cbr')->getDriver();
        $this->currencies = include(__DIR__ . '/../Resources/currencies.php');

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $action = $this->getActionArgument(['add', 'update', 'delete']);

        foreach ($this->getCurrencyArgument() as $currency) {
            $this->$action(strtoupper($currency));
        }
    }

    /**
     * Add currency to storage.
     *
     * @param string $currency
     *
     * @return void
     */
    protected function add($currency)
    {
        if (($data = $this->getCurrency($currency)) === null) {
            return $this->error("Валюта \"{$currency}\" не найдена");
        }

        $this->output->write("Добавляем {$currency} валюту...");

        $data['code'] = $currency;

        if (is_string($result = $this->storage->create($data))) {
            $this->output->writeln('<error>' . ($result ?: 'Failed') . '</error>');
        } else {
            $this->output->writeln("<info>success</info>");
        }
    }

    /**
     * Update currency in storage.
     *
     * @param string $currency
     *
     * @return void
     */
    protected function update($currency)
    {
        if (($data = $this->getCurrency($currency)) === null) {
            return $this->error("Валюта \"{$currency}\" не найдена");
        }

        $this->output->write("Обновляем {$currency} валюту...");

        if (is_string($result = $this->storage->update($currency, $data))) {
            $this->output->writeln('<error>' . ($result ?: 'Failed') . '</error>');
        } else {
            $this->output->writeln("<info>success</info>");
        }
    }

    /**
     * Delete currency from storage.
     *
     * @param string $currency
     *
     * @return void
     */
    protected function delete($currency)
    {
        $this->output->write("Удаление {$currency} валюты...");

        if (is_string($result = $this->storage->delete($currency))) {
            $this->output->writeln('<error>' . ($result ?: 'Failed') . '</error>');
        } else {
            $this->output->writeln("<info>success</info>");
        }
    }

    /**
     * Get currency argument.
     *
     * @return array
     */
    protected function getCurrencyArgument()
    {
        return explode(',', preg_replace('/\s+/', '', $this->argument('currency')));
    }

    /**
     * Get action argument.
     *
     * @param  array $validActions
     *
     * @return array
     */
    protected function getActionArgument($validActions = [])
    {
        $action = strtolower($this->argument('action'));

        if (in_array($action, $validActions) === false) {
            throw new \RuntimeException("Параметр \"{$action}\" не существует.");
        }

        return $action;
    }

    /**
     * Get currency data.
     *
     * @param string $currency
     *
     * @return array
     */
    protected function getCurrency($currency)
    {
        return Arr::get($this->currencies, $currency);
    }
}