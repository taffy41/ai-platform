<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Tests\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Message\AssistantMessage;
use Symfony\AI\Platform\Message\Content\ContentInterface;
use Symfony\AI\Platform\Message\Content\ImageUrl;
use Symfony\AI\Platform\Message\Content\Text;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\Role;
use Symfony\AI\Platform\Message\SystemMessage;
use Symfony\AI\Platform\Message\ToolCallMessage;
use Symfony\AI\Platform\Message\UserMessage;
use Symfony\AI\Platform\Result\ToolCall;

#[CoversClass(Message::class)]
#[UsesClass(UserMessage::class)]
#[UsesClass(SystemMessage::class)]
#[UsesClass(AssistantMessage::class)]
#[UsesClass(ToolCallMessage::class)]
#[UsesClass(Role::class)]
#[UsesClass(ToolCall::class)]
#[UsesClass(ImageUrl::class)]
#[UsesClass(Text::class)]
#[Small]
final class MessageTest extends TestCase
{
    public function testCreateSystemMessageWithString()
    {
        $message = Message::forSystem('My amazing system prompt.');

        $this->assertSame('My amazing system prompt.', $message->content);
    }

    public function testCreateSystemMessageWithStringable()
    {
        $message = Message::forSystem(new class implements \Stringable {
            public function __toString(): string
            {
                return 'My amazing system prompt.';
            }
        });

        $this->assertSame('My amazing system prompt.', $message->content);
    }

    public function testCreateAssistantMessage()
    {
        $message = Message::ofAssistant('It is time to sleep.');

        $this->assertSame('It is time to sleep.', $message->content);
    }

    public function testCreateAssistantMessageWithToolCalls()
    {
        $toolCalls = [
            new ToolCall('call_123456', 'my_tool', ['foo' => 'bar']),
            new ToolCall('call_456789', 'my_faster_tool'),
        ];
        $message = Message::ofAssistant(toolCalls: $toolCalls);

        $this->assertCount(2, $message->toolCalls);
        $this->assertTrue($message->hasToolCalls());
    }

    public function testCreateUserMessageWithString()
    {
        $message = Message::ofUser('Hi, my name is John.');

        $this->assertCount(1, $message->content);
        $this->assertInstanceOf(Text::class, $message->content[0]);
        $this->assertSame('Hi, my name is John.', $message->content[0]->text);
    }

    public function testCreateUserMessageWithStringable()
    {
        $message = Message::ofUser(new class implements \Stringable {
            public function __toString(): string
            {
                return 'Hi, my name is John.';
            }
        });

        $this->assertCount(1, $message->content);
        $this->assertInstanceOf(Text::class, $message->content[0]);
        $this->assertSame('Hi, my name is John.', $message->content[0]->text);
    }

    public function testCreateUserMessageContentInterfaceImplementingStringable()
    {
        $message = Message::ofUser(new class implements ContentInterface, \Stringable {
            public function __toString(): string
            {
                return 'I am a ContentInterface!';
            }
        });

        $this->assertCount(1, $message->content);
        $this->assertInstanceOf(ContentInterface::class, $message->content[0]);
    }

    public function testCreateUserMessageWithTextContent()
    {
        $text = new Text('Hi, my name is John.');
        $message = Message::ofUser($text);

        $this->assertSame([$text], $message->content);
    }

    public function testCreateUserMessageWithImages()
    {
        $message = Message::ofUser(
            new Text('Hi, my name is John.'),
            new ImageUrl('http://images.local/my-image.png'),
            'The following image is a joke.',
            new ImageUrl('http://images.local/my-image2.png'),
        );

        $this->assertCount(4, $message->content);
    }

    public function testCreateToolCallMessage()
    {
        $toolCall = new ToolCall('call_123456', 'my_tool', ['foo' => 'bar']);
        $message = Message::ofToolCall($toolCall, 'Foo bar.');

        $this->assertSame('Foo bar.', $message->content);
        $this->assertSame($toolCall, $message->toolCall);
    }
}
