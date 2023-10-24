<?php

namespace App\Services;

use Cake\Http\Client;

class GetAddressDataService
{
    public static function handle(string $postalCode): ?array
    {
        // cep.la não funcionou, não achei nem nada sobre
        return (new Client())->get(
            'https://viacep.com.br/ws/' . $postalCode . '/json/'
        )->getJson();
    }
}
