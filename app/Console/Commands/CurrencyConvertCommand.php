<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Currency;

class CurrencyConvertCommand extends Command 
{
    protected $signature = 'currency:conv {from=CHF : From currency code} {to=GBP : To currency code} {amount=1}';
    protected $description = 'Perform a currency conversion';

    public function handle()
    {
        try {
            $converted = app(Currency::class)->convert($this->argument('from'), $this->argument('to'), $this->argument('amount'));
            $this->line($this->argument("from") . " " . $this->argument("amount") . " = " . $this->argument("to") . " " . $converted);
        } catch (\Exception $ex) {
            $this->error($converted->getMessage());
        }
    }
}
