<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\Ollama;

use Symfony\AI\Platform\Exception\InvalidArgumentException;
use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\ModelClientInterface;
use Symfony\AI\Platform\Result\RawHttpResult;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Christopher Hertel <mail@christopher-hertel.de>
 */
final readonly class OllamaClient implements ModelClientInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $hostUrl,
    ) {
    }

    public function supports(Model $model): bool
    {
        return $model instanceof Ollama;
    }

    public function request(Model $model, array|string $payload, array $options = []): RawHttpResult
    {
        $response = $this->httpClient->request('POST', \sprintf('%s/api/show', $this->hostUrl), [
            'json' => [
                'model' => $model->getName(),
            ],
        ]);

        $modelInformationsPayload = $response->toArray();

        return match (true) {
            \in_array('completion', $modelInformationsPayload['capabilities'], true) => $this->doCompletionRequest($payload, $options),
            \in_array('embedding', $modelInformationsPayload['capabilities'], true) => $this->doEmbeddingsRequest($model, $payload, $options),
            default => throw new InvalidArgumentException(\sprintf('Unsupported model "%s".', $model::class)),
        };
    }

    /**
     * @param array<string|int, mixed> $payload
     * @param array<string, mixed>     $options
     */
    private function doCompletionRequest(array|string $payload, array $options = []): RawHttpResult
    {
        // Revert Ollama's default streaming behavior
        $options['stream'] ??= false;

        return new RawHttpResult($this->httpClient->request('POST', \sprintf('%s/api/chat', $this->hostUrl), [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => array_merge($options, $payload),
        ]));
    }

    /**
     * @param array<string|int, mixed> $payload
     * @param array<string, mixed>     $options
     */
    public function doEmbeddingsRequest(Model $model, array|string $payload, array $options = []): RawHttpResult
    {
        return new RawHttpResult($this->httpClient->request('POST', \sprintf('%s/api/embed', $this->hostUrl), [
            'json' => array_merge($options, [
                'model' => $model->getName(),
                'input' => $payload,
            ]),
        ]));
    }
}
