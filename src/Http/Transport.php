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
     * @param string|null $urlOrigem Quando informada, é visitada via GET antes
     *     do POST, na mesma sessão (cookies compartilhados). Alguns sites
     *     clássicos (ASP, no caso da SEFAZ-BA) só respondem corretamente a um
     *     POST se uma sessão já foi iniciada visitando a página do formulário
     *     — sem isso, redirecionamentos de erro apontam para um caminho
     *     relativo que não existe.
     *
     * @throws FalhaTransporteException Quando a requisição não obtém resposta
     *     (timeout, falha de conexão/DNS, erro de TLS).
     */
    public function enviarFormulario(string $url, array $campos, ?string $urlOrigem = null): RespostaHttp;
}
