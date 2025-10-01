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

use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Message\AssistantMessage;
use Symfony\AI\Platform\Message\Content\ImageUrl;
use Symfony\AI\Platform\Message\Content\Text;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Message\SystemMessage;
use Symfony\AI\Platform\Message\UserMessage;
use Symfony\AI\Platform\Result\ToolCall;

final class MessageBagTest extends TestCase
{
    public function testGetSystemMessage()
    {
        $messageBag = new MessageBag(
            Message::forSystem('My amazing system prompt.'),
            Message::ofAssistant('It is time to sleep.'),
            Message::ofUser('Hello, world!'),
            Message::ofToolCall(new ToolCall('tool', 'tool_name', ['param' => 'value']), 'Yes, go sleeping.'),
        );

        $systemMessage = $messageBag->getSystemMessage();

        $this->assertSame('My amazing system prompt.', $systemMessage->content);
    }

    public function testGetSystemMessageWithoutSystemMessage()
    {
        $messageBag = new MessageBag(
            Message::ofAssistant('It is time to sleep.'),
            Message::ofUser('Hello, world!'),
            Message::ofToolCall(new ToolCall('tool', 'tool_name', ['param' => 'value']), 'Yes, go sleeping.'),
        );

        $this->assertNull($messageBag->getSystemMessage());
    }

    public function testWith()
    {
        $messageBag = new MessageBag(
            Message::forSystem('My amazing system prompt.'),
            Message::ofAssistant('It is time to sleep.'),
            Message::ofUser('Hello, world!'),
        );

        $newMessage = Message::ofAssistant('It is time to wake up.');
        $newMessageBag = $messageBag->with($newMessage);

        $this->assertCount(3, $messageBag);
        $this->assertCount(4, $newMessageBag);

        $newMessageFromBag = $newMessageBag->getMessages()[3];

        $this->assertInstanceOf(AssistantMessage::class, $newMessageFromBag);
        $this->assertSame('It is time to wake up.', $newMessageFromBag->content);
    }

    public function testMerge()
    {
        $messageBag = new MessageBag(
            Message::forSystem('My amazing system prompt.'),
            Message::ofAssistant('It is time to sleep.'),
            Message::ofUser('Hello, world!'),
        );

        $messageBag = $messageBag->merge(new MessageBag(
            Message::ofAssistant('It is time to wake up.')
        ));

        $this->assertCount(4, $messageBag);

        $messageFromBag = $messageBag->getMessages()[3];

        $this->assertInstanceOf(AssistantMessage::class, $messageFromBag);
        $this->assertSame('It is time to wake up.', $messageFromBag->content);
    }

    public function testWithoutSystemMessage()
    {
        $messageBag = new MessageBag(
            Message::forSystem('My amazing system prompt.'),
            Message::ofAssistant('It is time to sleep.'),
            Message::forSystem('A system prompt in the middle.'),
            Message::ofUser('Hello, world!'),
            Message::forSystem('Another system prompt at the end'),
        );

        $newMessageBag = $messageBag->withoutSystemMessage();

        $this->assertCount(5, $messageBag);
        $this->assertCount(2, $newMessageBag);

        $assistantMessage = $newMessageBag->getMessages()[0];
        $this->assertInstanceOf(AssistantMessage::class, $assistantMessage);
        $this->assertSame('It is time to sleep.', $assistantMessage->content);

        $userMessage = $newMessageBag->getMessages()[1];
        $this->assertInstanceOf(UserMessage::class, $userMessage);
        $this->assertInstanceOf(Text::class, $userMessage->content[0]);
        $this->assertSame('Hello, world!', $userMessage->content[0]->text);
    }

    public function testPrepend()
    {
        $messageBag = new MessageBag(
            Message::ofAssistant('It is time to sleep.'),
            Message::ofUser('Hello, world!'),
        );

        $newMessage = Message::forSystem('My amazing system prompt.');
        $newMessageBag = $messageBag->prepend($newMessage);

        $this->assertCount(2, $messageBag);
        $this->assertCount(3, $newMessageBag);

        $newMessageBagMessage = $newMessageBag->getMessages()[0];

        $this->assertInstanceOf(SystemMessage::class, $newMessageBagMessage);
        $this->assertSame('My amazing system prompt.', $newMessageBagMessage->content);
    }

    public function testContainsImageReturnsFalseWithoutImage()
    {
        $messageBag = new MessageBag(
            Message::ofAssistant('It is time to sleep.'),
            Message::ofUser('Hello, world!'),
        );

        $this->assertFalse($messageBag->containsImage());
    }

    public function testContainsImageReturnsTrueWithImage()
    {
        $messageBag = new MessageBag(
            Message::ofAssistant('It is time to sleep.'),
            Message::ofUser('Hello, world!'),
            Message::ofUser('My hint for how to analyze an image.', new ImageUrl('http://image-generator.local/my-fancy-image.png')),
        );

        $this->assertTrue($messageBag->containsImage());
    }

    public function testItCanHandleMetadata()
    {
        $messageBag = new MessageBag();
        $metadata = $messageBag->getMetadata();

        $this->assertCount(0, $metadata);

        $metadata->add('key', 'value');
        $metadata = $messageBag->getMetadata();

        $this->assertCount(1, $metadata);
    }

    public function testGetUserMessage()
    {
        $messageBag = new MessageBag(
            Message::forSystem('My amazing system prompt.'),
            Message::ofAssistant('It is time to sleep.'),
            Message::ofUser('Hello, world!'),
            Message::ofAssistant('How can I help you?'),
        );

        $userMessage = $messageBag->getUserMessage();

        $this->assertInstanceOf(UserMessage::class, $userMessage);
        $this->assertInstanceOf(Text::class, $userMessage->content[0]);
        $this->assertSame('Hello, world!', $userMessage->content[0]->text);
    }

    public function testGetUserMessageReturnsNullWithoutUserMessage()
    {
        $messageBag = new MessageBag(
            Message::forSystem('My amazing system prompt.'),
            Message::ofAssistant('It is time to sleep.'),
        );

        $this->assertNull($messageBag->getUserMessage());
    }

    public function testGetUserMessageReturnsFirstUserMessage()
    {
        $messageBag = new MessageBag(
            Message::forSystem('My amazing system prompt.'),
            Message::ofUser('First user message'),
            Message::ofAssistant('Response'),
            Message::ofUser('Second user message'),
        );

        $userMessage = $messageBag->getUserMessage();

        $this->assertInstanceOf(UserMessage::class, $userMessage);
        $this->assertInstanceOf(Text::class, $userMessage->content[0]);
        $this->assertSame('First user message', $userMessage->content[0]->text);
    }

    public function testGetUserMessageText()
    {
        $messageBag = new MessageBag(
            Message::forSystem('My amazing system prompt.'),
            Message::ofUser('Hello, world!'),
            Message::ofAssistant('How can I help you?'),
        );

        $userMessage = $messageBag->getUserMessage();
        $userText = $userMessage?->asText();

        $this->assertSame('Hello, world!', $userText);
    }

    public function testGetUserMessageTextReturnsNullWithoutUserMessage()
    {
        $messageBag = new MessageBag(
            Message::forSystem('My amazing system prompt.'),
            Message::ofAssistant('It is time to sleep.'),
        );

        $userMessage = $messageBag->getUserMessage();

        $this->assertNull($userMessage?->asText());
    }

    public function testGetUserMessageTextWithMultipleTextParts()
    {
        $messageBag = new MessageBag(
            Message::forSystem('My amazing system prompt.'),
            Message::ofUser('Part one', 'Part two', 'Part three'),
            Message::ofAssistant('Response'),
        );

        $userMessage = $messageBag->getUserMessage();
        $userText = $userMessage?->asText();

        $this->assertSame('Part one Part two Part three', $userText);
    }

    public function testGetUserMessageTextIgnoresNonTextContent()
    {
        $messageBag = new MessageBag(
            Message::forSystem('My amazing system prompt.'),
            Message::ofUser('Text content', new ImageUrl('http://example.com/image.png')),
            Message::ofAssistant('Response'),
        );

        $userMessage = $messageBag->getUserMessage();
        $userText = $userMessage?->asText();

        // Should only return the text content, ignoring the image
        $this->assertSame('Text content', $userText);
    }
}
