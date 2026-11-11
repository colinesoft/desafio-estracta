<?php

declare(strict_types=1);

namespace ProvaSpider\Extraction;

use DOMDocument;
use DOMXPath;
use ProvaSpider\Exception\RespostaInesperadaException;

final class CadastroExtractor
{
    /**
     * Mapa de rótulo (como aparece na página, em minúsculas e sem ":") para
     * a chave correspondente em {@see \ProvaSpider\ValueObject\CadastroContribuinte}.
     * Cada <td> da página é "rótulo:valor" dentro do mesmo elemento.
     */
    private const MAPA_CAMPOS = [
        'cnpj' => 'cnpj',
        'inscrição estadual' => 'inscricaoEstadual',
        'razão social' => 'razaoSocial',
        'nome fantasia' => 'nomeFantasia',
        'situação cadastral vigente' => 'situacaoCadastral',
        'forma de pagamento' => 'regimeApuracao',
    ];

    private const ROTULO_ATIVIDADE_PRINCIPAL = 'atividade econômica principal';

    /** Rótulos da seção de endereço do próprio contribuinte (não a de correspondência). */
    private const MAPA_ENDERECO = [
        'logradouro' => 'logradouro',
        'número' => 'numero',
        'complemento' => 'complemento',
        'bairro/distrito' => 'bairro',
        'cep' => 'cep',
        'município' => 'municipio',
        'uf' => 'uf',
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

        if ($documento->getElementsByTagName('tr')->length === 0) {
            throw new RespostaInesperadaException('HTML sem estrutura de tabela reconhecível.');
        }

        $campos = [
            'inscricaoEstadual' => null,
            'cnpj' => null,
            'razaoSocial' => null,
            'nomeFantasia' => null,
            'situacaoCadastral' => null,
            'endereco' => null,
            'atividadeEconomica' => null,
            'regimeApuracao' => null,
        ];

        $tds = $documento->getElementsByTagName('td');

        for ($i = 0; $i < $tds->length; $i++) {
            [$rotulo, $valor] = $this->rotuloValor($tds->item($i)->textContent);

            if ($rotulo === null) {
                continue;
            }

            if ($rotulo === self::ROTULO_ATIVIDADE_PRINCIPAL) {
                if ($valor === '' && $i + 1 < $tds->length) {
                    $valor = $this->normalizar($tds->item($i + 1)->textContent);
                }

                if ($valor !== '') {
                    $campos['atividadeEconomica'] = $valor;
                }

                continue;
            }

            if (isset(self::MAPA_CAMPOS[$rotulo]) && $valor !== '') {
                $campos[self::MAPA_CAMPOS[$rotulo]] = $valor;
            }
        }

        if ($campos['cnpj'] !== null) {
            $campos['cnpj'] = preg_replace('/\D/', '', $campos['cnpj']);
        }

        $campos['endereco'] = $this->extrairEndereco($documento);

        return $campos;
    }

    private function extrairEndereco(DOMDocument $documento): ?string
    {
        $xpath = new DOMXPath($documento);
        $nos = $xpath->query('//td[contains(., "Logradouro:")]');

        if ($nos === false || $nos->length === 0) {
            return null;
        }

        $tabela = $nos->item(0)->parentNode;
        while ($tabela !== null && $tabela->nodeName !== 'table') {
            $tabela = $tabela->parentNode;
        }

        if ($tabela === null) {
            return null;
        }

        $partes = array_fill_keys(array_values(self::MAPA_ENDERECO), null);

        foreach ($xpath->query('.//td', $tabela) as $td) {
            [$rotulo, $valor] = $this->rotuloValor($td->textContent);

            if ($rotulo !== null && isset(self::MAPA_ENDERECO[$rotulo]) && $valor !== '') {
                $partes[self::MAPA_ENDERECO[$rotulo]] = $valor;
            }
        }

        $linha1 = implode(', ', array_filter([
            $partes['logradouro'],
            $partes['numero'],
            $partes['complemento'],
            $partes['bairro'],
        ], static fn (?string $v): bool => $v !== null && $v !== ''));

        $cidadeUf = $partes['municipio'] !== null && $partes['uf'] !== null
            ? sprintf('%s/%s', $partes['municipio'], $partes['uf'])
            : ($partes['municipio'] ?? $partes['uf']);

        $cep = $partes['cep'] !== null ? sprintf('CEP %s', $partes['cep']) : null;

        $endereco = implode(', ', array_filter(
            [$linha1, $cidadeUf, $cep],
            static fn (?string $v): bool => $v !== null && $v !== ''
        ));

        return $endereco !== '' ? $endereco : null;
    }

    /**
     * @return array{0: string|null, 1: string}
     */
    private function rotuloValor(string $textoCelula): array
    {
        $texto = $this->normalizar($textoCelula);

        if ($texto === '' || !preg_match('/^([^:]{1,80}):\s*(.*)$/u', $texto, $m)) {
            return [null, ''];
        }

        return [mb_strtolower(trim($m[1])), trim($m[2])];
    }

    private function normalizar(string $texto): string
    {
        $texto = str_replace("\u{00A0}", ' ', $texto);
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;

        return trim($texto);
    }
}
