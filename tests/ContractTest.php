<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Large;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Bridge\OpenAI\Embeddings;
use Symfony\AI\Platform\Bridge\OpenAI\GPT;
use Symfony\AI\Platform\Bridge\OpenAI\Whisper;
use Symfony\AI\Platform\Bridge\OpenAI\Whisper\AudioNormalizer;
use Symfony\AI\Platform\Contract;
use Symfony\AI\Platform\Contract\Normalizer\Message\AssistantMessageNormalizer;
use Symfony\AI\Platform\Contract\Normalizer\Message\Content\ImageNormalizer;
use Symfony\AI\Platform\Contract\Normalizer\Message\Content\ImageUrlNormalizer;
use Symfony\AI\Platform\Contract\Normalizer\Message\Content\TextNormalizer;
use Symfony\AI\Platform\Contract\Normalizer\Message\MessageBagNormalizer;
use Symfony\AI\Platform\Contract\Normalizer\Message\SystemMessageNormalizer;
use Symfony\AI\Platform\Contract\Normalizer\Message\ToolCallMessageNormalizer;
use Symfony\AI\Platform\Contract\Normalizer\Message\UserMessageNormalizer;
use Symfony\AI\Platform\Contract\Normalizer\Result\ToolCallNormalizer;
use Symfony\AI\Platform\Message\AssistantMessage;
use Symfony\AI\Platform\Message\Content\Audio;
use Symfony\AI\Platform\Message\Content\Image;
use Symfony\AI\Platform\Message\Content\ImageUrl;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Message\MessageInterface;
use Symfony\AI\Platform\Message\Role;
use Symfony\AI\Platform\Message\SystemMessage;
use Symfony\AI\Platform\Message\UserMessage;
use Symfony\AI\Platform\Model;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Uid\TimeBasedUidInterface;
use Symfony\Component\Uid\Uuid;

#[Large]
#[CoversClass(Contract::class)]
#[CoversClass(AssistantMessageNormalizer::class)]
#[CoversClass(AudioNormalizer::class)]
#[CoversClass(ImageNormalizer::class)]
#[CoversClass(ImageUrlNormalizer::class)]
#[CoversClass(TextNormalizer::class)]
#[CoversClass(MessageBagNormalizer::class)]
#[CoversClass(SystemMessageNormalizer::class)]
#[CoversClass(ToolCallMessageNormalizer::class)]
#[CoversClass(UserMessageNormalizer::class)]
#[CoversClass(ToolCallNormalizer::class)]
#[UsesClass(AssistantMessage::class)]
#[UsesClass(MessageBag::class)]
#[UsesClass(SystemMessage::class)]
#[UsesClass(UserMessage::class)]
#[UsesClass(Model::class)]
final class ContractTest extends TestCase
{
    #[DataProvider('providePayloadTestCases')]
    public function testCreateRequestPayload(Model $model, array|string|object $input, array|string $expected)
    {
        $contract = Contract::create();

        $actual = $contract->createRequestPayload($model, $input);

        $this->assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{
     *     input: array|string|object,
     *     expected: array<string, mixed>|string
     * }>
     */
    public static function providePayloadTestCases(): iterable
    {
        yield 'MessageBag with GPT' => [
            'model' => new GPT(),
            'input' => new MessageBag(
                Message::forSystem('System message'),
                Message::ofUser('User message'),
                Message::ofAssistant('Assistant message'),
            ),
            'expected' => [
                'messages' => [
                    ['role' => 'system', 'content' => 'System message'],
                    ['role' => 'user', 'content' => 'User message'],
                    ['role' => 'assistant', 'content' => 'Assistant message'],
                ],
                'model' => 'gpt-4o',
            ],
        ];

        $audio = Audio::fromFile(\dirname(__DIR__, 3).'/fixtures/audio.mp3');
        yield 'Audio within MessageBag with GPT' => [
            'model' => new GPT(),
            'input' => new MessageBag(Message::ofUser('What is this recording about?', $audio)),
            'expected' => [
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => 'What is this recording about?'],
                            [
                                'type' => 'input_audio',
                                'input_audio' => [
                                    'data' => $audio->asBase64(),
                                    'format' => 'mp3',
                                ],
                            ],
                        ],
                    ],
                ],
                'model' => 'gpt-4o',
            ],
        ];

        $image = Image::fromFile(\dirname(__DIR__, 3).'/fixtures/image.jpg');
        yield 'Image within MessageBag with GPT' => [
            'model' => new GPT(),
            'input' => new MessageBag(
                Message::forSystem('You are an image analyzer bot that helps identify the content of images.'),
                Message::ofUser('Describe the image as a comedian would do it.', $image),
            ),
            'expected' => [
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an image analyzer bot that helps identify the content of images.',
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => 'Describe the image as a comedian would do it.'],
                            ['type' => 'image_url', 'image_url' => ['url' => $image->asDataUrl()]],
                        ],
                    ],
                ],
                'model' => 'gpt-4o',
            ],
        ];

        yield 'ImageUrl within MessageBag with GPT' => [
            'model' => new GPT(),
            'input' => new MessageBag(
                Message::forSystem('You are an image analyzer bot that helps identify the content of images.'),
                Message::ofUser('Describe the image as a comedian would do it.', new ImageUrl('https://example.com/image.jpg')),
            ),
            'expected' => [
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an image analyzer bot that helps identify the content of images.',
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => 'Describe the image as a comedian would do it.'],
                            ['type' => 'image_url', 'image_url' => ['url' => 'https://example.com/image.jpg']],
                        ],
                    ],
                ],
                'model' => 'gpt-4o',
            ],
        ];

        yield 'Text Input with Embeddings' => [
            'model' => new Embeddings(),
            'input' => 'This is a test input.',
            'expected' => 'This is a test input.',
        ];

        yield 'Longer Conversation with GPT' => [
            'model' => new GPT(),
            'input' => new MessageBag(
                Message::forSystem('My amazing system prompt.'),
                Message::ofAssistant('It is time to sleep.'),
                Message::ofUser('Hello, world!'),
                new AssistantMessage('Hello User!'),
                Message::ofUser('My hint for how to analyze an image.', new ImageUrl('http://image-generator.local/my-fancy-image.png')),
            ),
            'expected' => [
                'messages' => [
                    ['role' => 'system', 'content' => 'My amazing system prompt.'],
                    ['role' => 'assistant', 'content' => 'It is time to sleep.'],
                    ['role' => 'user', 'content' => 'Hello, world!'],
                    ['role' => 'assistant', 'content' => 'Hello User!'],
                    ['role' => 'user', 'content' => [
                        ['type' => 'text', 'text' => 'My hint for how to analyze an image.'],
                        ['type' => 'image_url', 'image_url' => ['url' => 'http://image-generator.local/my-fancy-image.png']],
                    ]],
                ],
                'model' => 'gpt-4o',
            ],
        ];

        $customSerializableMessage = new class implements MessageInterface, \JsonSerializable {
            public function getRole(): Role
            {
                return Role::User;
            }

            public function getId(): AbstractUid&TimeBasedUidInterface
            {
                return Uuid::v7();
            }

            public function jsonSerialize(): array
            {
                return [
                    'role' => 'user',
                    'content' => 'This is a custom serializable message.',
                ];
            }
        };

        yield 'MessageBag with custom message from GPT' => [
            'model' => new GPT(),
            'input' => new MessageBag($customSerializableMessage),
            'expected' => [
                'messages' => [
                    ['role' => 'user', 'content' => 'This is a custom serializable message.'],
                ],
                'model' => 'gpt-4o',
            ],
        ];
    }

    public function testExtendedContractHandlesWhisper()
    {
        $contract = Contract::create(new AudioNormalizer());

        $audio = Audio::fromFile(\dirname(__DIR__, 3).'/fixtures/audio.mp3');

        $actual = $contract->createRequestPayload(new Whisper(), $audio);

        $this->assertArrayHasKey('model', $actual);
        $this->assertSame('whisper-1', $actual['model']);
        $this->assertArrayHasKey('file', $actual);
        $this->assertTrue(\is_resource($actual['file']));
    }
}
