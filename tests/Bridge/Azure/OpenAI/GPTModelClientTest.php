<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Tests\Bridge\Azure\OpenAI;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Bridge\Azure\OpenAI\GPTModelClient;
use Symfony\AI\Platform\Bridge\OpenAI\GPT;
use Symfony\AI\Platform\Exception\InvalidArgumentException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(GPTModelClient::class)]
#[UsesClass(GPT::class)]
#[Small]
/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class GPTModelClientTest extends TestCase
{
    #[TestWith(['http://test.azure.com', 'The base URL must not contain the protocol.'])]
    #[TestWith(['https://test.azure.com', 'The base URL must not contain the protocol.'])]
    #[TestWith(['http://test.azure.com/openai', 'The base URL must not contain the protocol.'])]
    #[TestWith(['https://test.azure.com:443', 'The base URL must not contain the protocol.'])]
    public function testItThrowsExceptionWhenBaseUrlContainsProtocol(string $invalidUrl, string $expectedMessage)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        new GPTModelClient(new MockHttpClient(), $invalidUrl, 'deployment', 'api-version', 'api-key');
    }

    public function testItThrowsExceptionWhenDeploymentIsEmpty()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The deployment must not be empty.');

        new GPTModelClient(new MockHttpClient(), 'test.azure.com', '', 'api-version', 'api-key');
    }

    public function testItThrowsExceptionWhenApiVersionIsEmpty()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The API version must not be empty.');

        new GPTModelClient(new MockHttpClient(), 'test.azure.com', 'deployment', '', 'api-key');
    }

    public function testItThrowsExceptionWhenApiKeyIsEmpty()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The API key must not be empty.');

        new GPTModelClient(new MockHttpClient(), 'test.azure.com', 'deployment', 'api-version', '');
    }

    public function testItAcceptsValidParameters()
    {
        $client = new GPTModelClient(new MockHttpClient(), 'test.azure.com', 'gpt-35-turbo', '2023-12-01-preview', 'valid-api-key');

        $this->assertInstanceOf(GPTModelClient::class, $client);
    }

    public function testItIsSupportingTheCorrectModel()
    {
        $client = new GPTModelClient(new MockHttpClient(), 'test.azure.com', 'deployment', '2023-12-01', 'api-key');

        $this->assertTrue($client->supports(new GPT()));
    }

    public function testItIsExecutingTheCorrectRequest()
    {
        $resultCallback = static function (string $method, string $url, array $options): MockResponse {
            self::assertSame('POST', $method);
            self::assertSame('https://test.azure.com/openai/deployments/gpt-deployment/chat/completions?api-version=2023-12-01', $url);
            self::assertSame(['api-key: test-api-key'], $options['normalized_headers']['api-key']);
            self::assertSame('{"messages":[{"role":"user","content":"Hello"}]}', $options['body']);

            return new MockResponse();
        };

        $httpClient = new MockHttpClient([$resultCallback]);
        $client = new GPTModelClient($httpClient, 'test.azure.com', 'gpt-deployment', '2023-12-01', 'test-api-key');
        $client->request(new GPT(), ['messages' => [['role' => 'user', 'content' => 'Hello']]]);
    }
}
