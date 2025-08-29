<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\OpenAi\Contract;

use Symfony\AI\Platform\Bridge\OpenAi\Gpt;
use Symfony\AI\Platform\Contract\Normalizer\ModelContractNormalizer;
use Symfony\AI\Platform\Message\Content\File;
use Symfony\AI\Platform\Model;

/**
 * @author Guillermo Lengemann <guillermo.lengemann@gmail.com>
 */
class FileNormalizer extends ModelContractNormalizer
{
    /**
     * @param File $data
     *
     * @return array{type: 'file', file: array{filename: string, file_data: 'base64'}}
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        return [
            'type' => 'file',
            'file' => [
                'filename' => $data->getFilename(),
                'file_data' => $data->asDataUrl(),
            ],
        ];
    }

    protected function supportedDataClass(): string
    {
        return File::class;
    }

    protected function supportsModel(Model $model): bool
    {
        return $model instanceof Gpt;
    }
}
