<?php namespace App;

use Illuminate\Support\Facades\Cache;

class Currency 
{
    private $api_key = NULL;

    public function __construct($API_KEY)
    {
        $this->api_key = $API_KEY;
    }

    public function getRates($base = "GBP", $ignoreCached = FALSE) 
    {
        $url = "https://openexchangerates.org/api/latest.json?app_id=" . $this->api_key . '&base=' . $base;
        $cacheKey = 'crates_' . $base;

        if(!Cache::has($cacheKey) || $ignoreCached) {
            $rawRates = file_get_contents($url);
            $jRates = json_decode($rawRates);
            Cache::put($cacheKey, $jRates, 10);
        }

        $rates = Cache::get($cacheKey);
        return $rates;
    }

    public function convert($from, $to, $amount) 
    {
        $rates = $this->getRates($from);
        if(!isset($rates->rates->$to)) 
        {
            throw new \Exception('No rate exists for ' . $from . ' to ' . $to);
        }

        $rate = $rates->rates->$to;
        return($amount * $rate);
    }
}
