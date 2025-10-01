<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Message;

use Symfony\AI\Platform\Message\Content\Audio;
use Symfony\AI\Platform\Message\Content\ContentInterface;
use Symfony\AI\Platform\Message\Content\Image;
use Symfony\AI\Platform\Message\Content\ImageUrl;
use Symfony\AI\Platform\Message\Content\Text;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Uid\TimeBasedUidInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @author Denis Zunke <denis.zunke@gmail.com>
 */
final readonly class UserMessage implements MessageInterface
{
    /**
     * @var list<ContentInterface>
     */
    public array $content;

    public AbstractUid&TimeBasedUidInterface $id;

    public function __construct(
        ContentInterface ...$content,
    ) {
        $this->content = $content;
        $this->id = Uuid::v7();
    }

    public function getRole(): Role
    {
        return Role::User;
    }

    public function getId(): AbstractUid&TimeBasedUidInterface
    {
        return $this->id;
    }

    public function hasAudioContent(): bool
    {
        foreach ($this->content as $content) {
            if ($content instanceof Audio) {
                return true;
            }
        }

        return false;
    }

    public function hasImageContent(): bool
    {
        foreach ($this->content as $content) {
            if ($content instanceof Image || $content instanceof ImageUrl) {
                return true;
            }
        }

        return false;
    }

    public function asText(): ?string
    {
        $textParts = [];
        foreach ($this->content as $content) {
            if ($content instanceof Text) {
                $textParts[] = $content->text;
            }
        }

        if ([] === $textParts) {
            return null;
        }

        return implode(' ', $textParts);
    }
}
