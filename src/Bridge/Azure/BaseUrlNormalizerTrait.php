<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\Azure;

/**
 * Normalizes the base URL of an Azure resource.
 *
 * Accepts a full base URL with scheme (e.g. "https://my-resource.openai.azure.com") as well as a
 * bare host (e.g. "my-resource.openai.azure.com"), in which case the "https" scheme is assumed. A
 * trailing slash is tolerated and stripped in both cases.
 *
 * @author Christopher Hertel <mail@christopher-hertel.de>
 */
trait BaseUrlNormalizerTrait
{
    private function normalizeBaseUrl(string $baseUrl): string
    {
        $baseUrl = rtrim($baseUrl, '/');

        if (str_starts_with($baseUrl, 'http://') || str_starts_with($baseUrl, 'https://')) {
            return $baseUrl;
        }

        return 'https://'.$baseUrl;
    }
}
