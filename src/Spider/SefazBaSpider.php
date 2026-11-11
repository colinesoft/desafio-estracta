<?php

declare(strict_types=1);

namespace ProvaSpider\Spider;

use DOMDocument;
use ProvaSpider\Contract\Spider;
use ProvaSpider\Exception\CnpjNaoEncontradoException;
use ProvaSpider\Exception\CnpjRejeitadoException;
use ProvaSpider\Exception\RespostaInesperadaException;
use ProvaSpider\Exception\ServicoIndisponivelException;
use ProvaSpider\Extraction\CadastroExtractor;
use ProvaSpider\Http\HttpTransport;
use ProvaSpider\Http\RetryingTransport;
use ProvaSpider\Http\Transport;
use ProvaSpider\ValueObject\CadastroContribuinte;
use ProvaSpider\ValueObject\Consulta;

final class SefazBaSpider implements Spider
{
    public const URL = 'https://portal.sefaz.ba.gov.br/scripts/cadastro/cadastroBa/consultaBa.asp';

    private const URL_RESULTADO = 'https://portal.sefaz.ba.gov.br/scripts/cadastro/cadastroBa/result.asp';

    public function __construct(
        private readonly Transport $transport = new RetryingTransport(new HttpTransport()),
        private readonly CadastroExtractor $extractor = new CadastroExtractor(),
    ) {
    }

    public function consultar(Consulta $consulta): CadastroContribuinte
    {
        $resposta = $this->transport->enviarFormulario(self::URL_RESULTADO, [
            'sefp' => '1',
            'estado' => 'BA',
            'CGC' => $consulta->cnpj,
            // Botão "CNPJ ->" do formulário original: sem esse campo o ASP
            // não sabe qual tipo de consulta foi solicitado e redireciona
            // para uma página de parâmetro inválido.
            'B1' => 'CNPJ  ->',
        ], urlOrigem: self::URL);

        if ($resposta->statusCode >= 500) {
            throw new ServicoIndisponivelException(
                sprintf('SEFAZ-BA respondeu com erro do servidor (status %d).', $resposta->statusCode)
            );
        }

        if ($resposta->statusCode < 200 || $resposta->statusCode >= 300) {
            throw new RespostaInesperadaException(
                sprintf('Status HTTP inesperado da SEFAZ-BA: %d.', $resposta->statusCode)
            );
        }

        $textoPlano = mb_strtolower($this->textoPlano($resposta->corpo));

        if (str_contains($textoPlano, 'nenhum registro encontrado')) {
            throw new CnpjNaoEncontradoException(
                sprintf('Nenhum cadastro encontrado para o CNPJ %s.', $consulta->cnpj)
            );
        }

        if (str_contains($textoPlano, 'dígito verificador')) {
            throw new CnpjRejeitadoException(
                sprintf('A SEFAZ-BA rejeitou o CNPJ %s (dígito verificador incorreto).', $consulta->cnpj)
            );
        }

        $campos = $this->extractor->extrair($resposta->corpo);

        return new CadastroContribuinte(
            inscricaoEstadual: $campos['inscricaoEstadual'],
            cnpj: $campos['cnpj'],
            razaoSocial: $campos['razaoSocial'],
            nomeFantasia: $campos['nomeFantasia'],
            situacaoCadastral: $campos['situacaoCadastral'],
            endereco: $campos['endereco'],
            atividadeEconomica: $campos['atividadeEconomica'],
            regimeApuracao: $campos['regimeApuracao'],
        );
    }

    private function textoPlano(string $html): string
    {
        $documento = new DOMDocument();
        $erroAnterior = libxml_use_internal_errors(true);
        $documento->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($erroAnterior);

        return $documento->textContent;
    }
}
