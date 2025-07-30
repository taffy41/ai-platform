<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\InMemoryPlatform;
use Symfony\AI\Platform\Model;

#[CoversClass(InMemoryPlatform::class)]
class InMemoryPlatformTest extends TestCase
{
    public function testPlatformInvokeWithFixedResult()
    {
        $platform = new InMemoryPlatform('Mocked result');
        $result = $platform->invoke(new Model('test'), 'input');

        $this->assertSame('Mocked result', $result->asText());
        $this->assertSame('Mocked result', $result->getResult()->getContent());
        $this->assertSame(['text' => 'Mocked result'], $result->getRawResult()->getData());
    }

    public function testPlatformInvokeWithCallableResult()
    {
        $platform = new InMemoryPlatform(function (Model $model, $input) {
            return strtoupper((string) $input);
        });

        $result = $platform->invoke(new Model('test'), 'dynamic text');

        $this->assertSame('DYNAMIC TEXT', $result->asText());
    }
}
