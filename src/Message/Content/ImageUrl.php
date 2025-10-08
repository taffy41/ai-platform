<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Message\Content;

/**
 * @author Christopher Hertel <mail@christopher-hertel.de>
 */
final readonly class ImageUrl implements ContentInterface
{
    public function __construct(
        private string $url,
    ) {
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
