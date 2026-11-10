<?php

declare(strict_types=1);

namespace ProvaSpider\Http;

use ProvaSpider\Exception\FalhaTransporteException;

interface Transport
{
    /**
     * Envia os campos de um formulário via POST e devolve a resposta HTTP tal
     * como recebida, independentemente do status (2xx, 4xx ou 5xx) — cabe ao
     * chamador decidir o que cada status significa.
     *
     * @param array<string, string> $campos
     *
     * @throws FalhaTransporteException Quando a requisição não obtém resposta
     *     (timeout, falha de conexão/DNS, erro de TLS).
     */
    public function enviarFormulario(string $url, array $campos): RespostaHttp;
}
