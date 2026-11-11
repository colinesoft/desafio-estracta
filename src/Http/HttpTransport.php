<?php

declare(strict_types=1);

namespace ProvaSpider\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use ProvaSpider\Exception\FalhaTransporteException;

final class HttpTransport implements Transport
{
    private Client $cliente;

    public function __construct(?Client $cliente = null)
    {
        $this->cliente = $cliente ?? new Client();
    }

    public function enviarFormulario(string $url, array $campos): RespostaHttp
    {
        try {
            $resposta = $this->cliente->post($url, [
                'form_params' => $campos,
                'connect_timeout' => 5,
                'timeout' => 15,
                'http_errors' => false,
                'allow_redirects' => ['max' => 3],
            ]);
        } catch (GuzzleException $exception) {
            throw new FalhaTransporteException($exception->getMessage(), 0, $exception);
        }

        return new RespostaHttp($resposta->getStatusCode(), (string) $resposta->getBody());
    }
}
