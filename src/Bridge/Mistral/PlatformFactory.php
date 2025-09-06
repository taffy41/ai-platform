<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\Mistral;

use Symfony\AI\Platform\Bridge\Mistral\Contract\DocumentNormalizer;
use Symfony\AI\Platform\Bridge\Mistral\Contract\DocumentUrlNormalizer;
use Symfony\AI\Platform\Bridge\Mistral\Contract\ToolNormalizer;
use Symfony\AI\Platform\Contract;
use Symfony\AI\Platform\Platform;
use Symfony\Component\HttpClient\EventSourceHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Christopher Hertel <mail@christopher-hertel.de>
 */
final class PlatformFactory
{
    public static function create(
        #[\SensitiveParameter]
        string $apiKey,
        ?HttpClientInterface $httpClient = null,
        ?Contract $contract = null,
    ): Platform {
        $httpClient = $httpClient instanceof EventSourceHttpClient ? $httpClient : new EventSourceHttpClient($httpClient);

        return new Platform(
            [new Embeddings\ModelClient($httpClient, $apiKey), new Llm\ModelClient($httpClient, $apiKey)],
            [new Embeddings\ResultConverter(), new Llm\ResultConverter()],
            $contract ?? Contract::create(
                new ToolNormalizer(),
                new DocumentNormalizer(),
                new DocumentUrlNormalizer(),
            ),
        );
    }
}
