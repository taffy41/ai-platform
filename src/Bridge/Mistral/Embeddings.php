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

use Symfony\AI\Platform\Capability;
use Symfony\AI\Platform\Model;

/**
 * @author Christopher Hertel <mail@christopher-hertel.de>
 */
final class Embeddings extends Model
{
    public const MISTRAL_EMBED = 'mistral-embed';

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        string $name,
        array $options = [],
    ) {
        parent::__construct($name, [Capability::INPUT_MULTIPLE], $options);
    }
}
