<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\Mistral\Tests\Llm;

use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Bridge\Mistral\Llm\ResultConverter;
use Symfony\AI\Platform\Bridge\Mistral\Mistral;
use Symfony\AI\Platform\Exception\ExceedContextSizeException;
use Symfony\AI\Platform\Exception\ServerException;
use Symfony\AI\Platform\Result\RawHttpResult;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;

final class ResultConverterTest extends TestCase
{
    public function testItSupportsMistralModel()
    {
        $converter = new ResultConverter();

        $this->assertTrue($converter->supports(new Mistral('mistral-large-latest')));
    }

    /**
     * Not a cassette: provoking this for real means overflowing the smallest available Mistral
     * context window, so the recorded request body would be a ~640 KB prompt of filler text.
     */
    public function testConvertThrowsExceedContextSizeExceptionOnContextOverflow()
    {
        $this->expectException(ExceedContextSizeException::class);
        $this->expectExceptionMessage('maximum context length');

        $httpClient = new MockHttpClient(new JsonMockResponse([
            'message' => 'Prompt contains 300019 tokens and 0 draft tokens, too large for model with 262144 maximum context length',
        ], ['http_code' => 400]));

        $httpResponse = $httpClient->request('POST', 'https://api.mistral.ai/v1/chat/completions');
        $converter = new ResultConverter();

        $converter->convert(new RawHttpResult($httpResponse));
    }

    /**
     * Not a cassette: a provider cannot be asked for a 500 on demand. The assertion is on our own
     * status handling anyway - the body is irrelevant - so a mock is the honest tool here.
     */
    public function testThrowsServerExceptionOnServerErrorStatusBeforeStreaming()
    {
        $httpClient = new MockHttpClient(new JsonMockResponse(['error' => 'Service Unavailable'], ['http_code' => 500]));
        $httpResponse = $httpClient->request('POST', 'https://example.com');
        $converter = new ResultConverter();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Server error (HTTP 500');

        $converter->convert(new RawHttpResult($httpResponse), ['stream' => true]);
    }
}
