<?php

declare(strict_types=1);

namespace ProvaSpider\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use ProvaSpider\Exception\FalhaTransporteException;

final class HttpTransport implements Transport
{
    private Client $cliente;

    public function __construct(?Client $cliente = null)
    {
        $this->cliente = $cliente ?? new Client();
    }

    public function enviarFormulario(string $url, array $campos, ?string $urlOrigem = null): RespostaHttp
    {
        $opcoes = [
            'connect_timeout' => 5,
            'timeout' => 15,
            'http_errors' => false,
            'allow_redirects' => ['max' => 3],
        ];

        try {
            if ($urlOrigem !== null) {
                // Sessão nova por chamada: cookies isolados entre consultas
                // diferentes, mas compartilhados entre o GET e o POST abaixo.
                $opcoes['cookies'] = new CookieJar();
                $this->cliente->get($urlOrigem, $opcoes);
            }

            $resposta = $this->cliente->post($url, ['form_params' => $campos] + $opcoes);
        } catch (GuzzleException $exception) {
            throw new FalhaTransporteException($exception->getMessage(), 0, $exception);
        }

        return new RespostaHttp($resposta->getStatusCode(), (string) $resposta->getBody());
    }
}
