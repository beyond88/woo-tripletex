<?php

namespace Woo_Tripletex;

use Woo_Tripletex\API\Handler\Country;
use Woo_Tripletex\API\Handler\Currency;
use Woo_Tripletex\API\Handler\Account;

class Helpers 
{
    public function tripletexCountries()
    {
        $countries = wp_cache_get('tripletex_countries');
        if($countries) {
            return $countries;
        }

        $countryHandler = new Country();
        $data = $countryHandler->index();
        $countries = json_decode($data, true)['values'];
        wp_cache_set('tripletex_countries', $countries);
        
        return $countries;
    }

    public function findCountryIdByISO($code)
    {
        $countries = $this->tripletexCountries();
        foreach ($countries as $country) {
            if ($country['isoAlpha2Code'] == $code) {
                return $country['id'];
            }
        }

        return null;
    }

    public function tripletexCurrencies()
    {
        $currencies = wp_cache_get('tripletex_currencies');
        if($currencies) {
            return $currencies;
        }

        $countryHandler = new Currency();
        $data = $countryHandler->index();
        $currencies = json_decode($data, true)['values'];
        wp_cache_set('tripletex_currencies', $currencies);
        
        return $currencies;
    }

    public function findCurrencyIdByCode($code)
    {
        $currencies = $this->tripletexCurrencies();
        foreach ($currencies as $currency) {
            if ($currency['code'] == $code) {
                return $currency['id'];
            }
        }

        return null;
    }


    public function tripletexAccounts()
    {
        $accounts = wp_cache_get('tripletex_accounts');
        if($accounts) {
            return $accounts;
        }

        $countryHandler = new Account();
        $data = $countryHandler->index();
        $accounts = json_decode($data, true)['values'];
        wp_cache_set('tripletex_accounts', $accounts);
        
        return $accounts;
    }

    public function findAccountInfoByNumber($code)
    {
        $accounts = $this->tripletexAccounts();
        foreach ($accounts as $account) {
            if ($account['number'] == $code) {
                return $account;
            }
        }

        return null;
    }

}