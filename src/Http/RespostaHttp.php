<?php

declare(strict_types=1);

namespace ProvaSpider\Http;

final class RespostaHttp
{
    public function __construct(
        public readonly int $statusCode,
        public readonly string $corpo,
    ) {
    }
}
