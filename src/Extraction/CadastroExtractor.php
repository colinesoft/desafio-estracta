<?php

declare(strict_types=1);

namespace ProvaSpider\Extraction;

use DOMDocument;
use ProvaSpider\Exception\RespostaInesperadaException;

final class CadastroExtractor
{
    /**
     * Mapa de rótulo (como aparece na página, em minúsculas e sem ":") para
     * a chave correspondente em {@see \ProvaSpider\ValueObject\CadastroContribuinte}.
     */
    private const MAPA_CAMPOS = [
        'inscrição estadual' => 'inscricaoEstadual',
        'cgc/cnpj' => 'cnpj',
        'nome/razão social' => 'razaoSocial',
        'nome fantasia' => 'nomeFantasia',
        'situação cadastral vigente' => 'situacaoCadastral',
        'endereço' => 'endereco',
        'atividades econômicas' => 'atividadeEconomica',
        'forma de pagamento' => 'regimeApuracao',
    ];

    /**
     * @return array<string, string|null>
     *
     * @throws RespostaInesperadaException Quando o HTML não tem a estrutura
     *     de tabela esperada (layout mudou ou página corrompida).
     */
    public function extrair(string $html): array
    {
        $documento = new DOMDocument();
        $erroAnterior = libxml_use_internal_errors(true);
        $documento->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($erroAnterior);

        $linhas = $documento->getElementsByTagName('tr');
        if ($linhas->length === 0) {
            throw new RespostaInesperadaException('HTML sem estrutura de tabela reconhecível.');
        }

        $campos = array_fill_keys(array_values(self::MAPA_CAMPOS), null);

        foreach ($linhas as $linha) {
            $celulas = $linha->getElementsByTagName('td');
            if ($celulas->length < 2) {
                continue;
            }

            $rotulo = $this->normalizar($celulas->item(0)->textContent);
            $valor = $this->normalizar($celulas->item(1)->textContent);
            $chaveRotulo = rtrim(mb_strtolower($rotulo), ':');

            if ($valor !== '' && isset(self::MAPA_CAMPOS[$chaveRotulo])) {
                $campos[self::MAPA_CAMPOS[$chaveRotulo]] = $valor;
            }
        }

        return $campos;
    }

    private function normalizar(string $texto): string
    {
        $texto = str_replace("\u{00A0}", ' ', $texto);
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;

        return trim($texto);
    }
}
