<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Tests\Bridge\OpenAI\GPT;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Bridge\OpenAI\GPT\ResultConverter;
use Symfony\AI\Platform\Exception\ContentFilterException;
use Symfony\AI\Platform\Exception\RuntimeException;
use Symfony\AI\Platform\Result\Choice;
use Symfony\AI\Platform\Result\ChoiceResult;
use Symfony\AI\Platform\Result\RawHttpResult;
use Symfony\AI\Platform\Result\TextResult;
use Symfony\AI\Platform\Result\ToolCall;
use Symfony\AI\Platform\Result\ToolCallResult;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[CoversClass(ResultConverter::class)]
#[Small]
#[UsesClass(Choice::class)]
#[UsesClass(ChoiceResult::class)]
#[UsesClass(TextResult::class)]
#[UsesClass(ToolCall::class)]
#[UsesClass(ToolCallResult::class)]
class ResultConverterTest extends TestCase
{
    public function testConvertTextResult()
    {
        $converter = new ResultConverter();
        $httpResponse = self::createMock(ResponseInterface::class);
        $httpResponse->method('toArray')->willReturn([
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Hello world',
                    ],
                    'finish_reason' => 'stop',
                ],
            ],
        ]);

        $result = $converter->convert(new RawHttpResult($httpResponse));

        $this->assertInstanceOf(TextResult::class, $result);
        $this->assertSame('Hello world', $result->getContent());
    }

    public function testConvertToolCallResult()
    {
        $converter = new ResultConverter();
        $httpResponse = self::createMock(ResponseInterface::class);
        $httpResponse->method('toArray')->willReturn([
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => null,
                        'tool_calls' => [
                            [
                                'id' => 'call_123',
                                'type' => 'function',
                                'function' => [
                                    'name' => 'test_function',
                                    'arguments' => '{"arg1": "value1"}',
                                ],
                            ],
                        ],
                    ],
                    'finish_reason' => 'tool_calls',
                ],
            ],
        ]);

        $result = $converter->convert(new RawHttpResult($httpResponse));

        $this->assertInstanceOf(ToolCallResult::class, $result);
        $toolCalls = $result->getContent();
        $this->assertCount(1, $toolCalls);
        $this->assertSame('call_123', $toolCalls[0]->id);
        $this->assertSame('test_function', $toolCalls[0]->name);
        $this->assertSame(['arg1' => 'value1'], $toolCalls[0]->arguments);
    }

    public function testConvertMultipleChoices()
    {
        $converter = new ResultConverter();
        $httpResponse = self::createMock(ResponseInterface::class);
        $httpResponse->method('toArray')->willReturn([
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Choice 1',
                    ],
                    'finish_reason' => 'stop',
                ],
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Choice 2',
                    ],
                    'finish_reason' => 'stop',
                ],
            ],
        ]);

        $result = $converter->convert(new RawHttpResult($httpResponse));

        $this->assertInstanceOf(ChoiceResult::class, $result);
        $choices = $result->getContent();
        $this->assertCount(2, $choices);
        $this->assertSame('Choice 1', $choices[0]->getContent());
        $this->assertSame('Choice 2', $choices[1]->getContent());
    }

    public function testContentFilterException()
    {
        $converter = new ResultConverter();
        $httpResponse = self::createMock(ResponseInterface::class);

        $httpResponse->expects($this->exactly(1))
            ->method('toArray')
            ->willReturnCallback(function ($throw = true) {
                if ($throw) {
                    throw new class extends \Exception implements ClientExceptionInterface {
                        public function getResponse(): ResponseInterface
                        {
                            throw new RuntimeException('Not implemented');
                        }
                    };
                }

                return [
                    'error' => [
                        'code' => 'content_filter',
                        'message' => 'Content was filtered',
                    ],
                ];
            });

        self::expectException(ContentFilterException::class);
        self::expectExceptionMessage('Content was filtered');

        $converter->convert(new RawHttpResult($httpResponse));
    }

    public function testThrowsExceptionWhenNoChoices()
    {
        $converter = new ResultConverter();
        $httpResponse = self::createMock(ResponseInterface::class);
        $httpResponse->method('toArray')->willReturn([]);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('Response does not contain choices');

        $converter->convert(new RawHttpResult($httpResponse));
    }

    public function testThrowsExceptionForUnsupportedFinishReason()
    {
        $converter = new ResultConverter();
        $httpResponse = self::createMock(ResponseInterface::class);
        $httpResponse->method('toArray')->willReturn([
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Test content',
                    ],
                    'finish_reason' => 'unsupported_reason',
                ],
            ],
        ]);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('Unsupported finish reason "unsupported_reason"');

        $converter->convert(new RawHttpResult($httpResponse));
    }
}
