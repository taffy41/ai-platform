<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\Gemini\Gemini;

use Symfony\AI\Platform\Bridge\Gemini\Gemini;
use Symfony\AI\Platform\Exception\RuntimeException;
use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\Result\ChoiceResult;
use Symfony\AI\Platform\Result\RawHttpResult;
use Symfony\AI\Platform\Result\RawResultInterface;
use Symfony\AI\Platform\Result\ResultInterface;
use Symfony\AI\Platform\Result\StreamResult;
use Symfony\AI\Platform\Result\TextResult;
use Symfony\AI\Platform\Result\ToolCall;
use Symfony\AI\Platform\Result\ToolCallResult;
use Symfony\AI\Platform\ResultConverterInterface;
use Symfony\Component\HttpClient\EventSourceHttpClient;
use Symfony\Contracts\HttpClient\ResponseInterface as HttpResponse;

/**
 * @author Roy Garrido
 */
final readonly class ResultConverter implements ResultConverterInterface
{
    public function supports(Model $model): bool
    {
        return $model instanceof Gemini;
    }

    public function convert(RawResultInterface|RawHttpResult $result, array $options = []): ResultInterface
    {
        if ($options['stream'] ?? false) {
            return new StreamResult($this->convertStream($result->getObject()));
        }

        $data = $result->getData();

        if (!isset($data['candidates'][0]['content']['parts'][0])) {
            throw new RuntimeException('Response does not contain any content.');
        }

        $choices = array_map($this->convertChoice(...), $data['candidates']);

        return 1 === \count($choices) ? $choices[0] : new ChoiceResult(...$choices);
    }

    private function convertStream(HttpResponse $result): \Generator
    {
        foreach ((new EventSourceHttpClient())->stream($result) as $chunk) {
            if ($chunk->isFirst() || $chunk->isLast()) {
                continue;
            }

            $jsonDelta = trim($chunk->getContent());

            // Remove leading/trailing brackets
            if (str_starts_with($jsonDelta, '[') || str_starts_with($jsonDelta, ',')) {
                $jsonDelta = substr($jsonDelta, 1);
            }
            if (str_ends_with($jsonDelta, ']')) {
                $jsonDelta = substr($jsonDelta, 0, -1);
            }

            // Split in case of multiple JSON objects
            $deltas = explode(",\r\n", $jsonDelta);

            foreach ($deltas as $delta) {
                if ('' === $delta) {
                    continue;
                }

                try {
                    $data = json_decode($delta, true, 512, \JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    throw new RuntimeException('Failed to decode JSON response.', 0, $e);
                }

                $choices = array_map($this->convertChoice(...), $data['candidates'] ?? []);

                if (!$choices) {
                    continue;
                }

                if (1 !== \count($choices)) {
                    yield new ChoiceResult(...$choices);
                    continue;
                }

                yield $choices[0]->getContent();
            }
        }
    }

    /**
     * @param array{
     *     finishReason?: string,
     *     content: array{
     *         parts: array{
     *             functionCall?: array{
     *                 id: string,
     *                 name: string,
     *                 args: mixed[]
     *             },
     *             text?: string
     *         }[]
     *     }
     * } $choice
     */
    private function convertChoice(array $choice): ToolCallResult|TextResult
    {
        $contentPart = $choice['content']['parts'][0] ?? [];

        if (isset($contentPart['functionCall'])) {
            return new ToolCallResult($this->convertToolCall($contentPart['functionCall']));
        }

        if (isset($contentPart['text'])) {
            return new TextResult($contentPart['text']);
        }

        throw new RuntimeException(\sprintf('Unsupported finish reason "%s".', $choice['finishReason']));
    }

    /**
     * @param array{
     *     id?: string,
     *     name: string,
     *     args: mixed[]
     * } $toolCall
     */
    private function convertToolCall(array $toolCall): ToolCall
    {
        return new ToolCall($toolCall['id'] ?? '', $toolCall['name'], $toolCall['args']);
    }
}
