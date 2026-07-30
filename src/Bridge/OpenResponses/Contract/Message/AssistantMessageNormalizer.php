<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\OpenResponses\Contract\Message;

use Symfony\AI\Platform\Bridge\OpenResponses\ResponsesModel;
use Symfony\AI\Platform\Contract\Normalizer\ModelContractNormalizer;
use Symfony\AI\Platform\Message\AssistantMessage;
use Symfony\AI\Platform\Message\Content\Text;
use Symfony\AI\Platform\Message\Content\Thinking;
use Symfony\AI\Platform\Model;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

/**
 * @author Guillermo Lengemann <guillermo.lengemann@gmail.com>
 */
final class AssistantMessageNormalizer extends ModelContractNormalizer implements NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    /**
     * @param AssistantMessage $data
     *
     * @return list<array<string, mixed>>
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        $reasoningItems = $this->extractReasoningItems($data);

        if ($data->hasToolCalls()) {
            /** @var list<array<string, mixed>> $toolCalls */
            $toolCalls = $this->normalizer->normalize($data->getToolCalls(), $format, $context);

            return array_merge($reasoningItems, $toolCalls);
        }

        $text = '';
        foreach ($data->getContent() as $part) {
            if ($part instanceof Text) {
                $text .= $part->getText();
            }
        }

        return [
            ...$reasoningItems,
            [
                'role' => $data->getRole()->value,
                'type' => 'message',
                'content' => '' === $text ? null : $text,
            ],
        ];
    }

    protected function supportedDataClass(): string
    {
        return AssistantMessage::class;
    }

    protected function supportsModel(Model $model): bool
    {
        return $model instanceof ResponsesModel;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function extractReasoningItems(AssistantMessage $data): array
    {
        $items = [];

        foreach ($data->getContent() as $part) {
            if (!$part instanceof Thinking || null === $part->getSignature()) {
                continue;
            }

            try {
                $item = json_decode($part->getSignature(), true, flags: \JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                // Signatures from other providers may be opaque strings
                continue;
            }

            if (\is_array($item) && 'reasoning' === ($item['type'] ?? null)) {
                $items[] = $item;
            }
        }

        return $items;
    }
}
