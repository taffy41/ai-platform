<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Contract\JsonSchema\Attribute;

use Symfony\AI\Platform\Exception\InvalidArgumentException;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
final readonly class With
{
    /**
     * @param list<int|string>|null    $enum
     * @param string|int|string[]|null $const
     */
    public function __construct(
        // can be used by many types
        public ?array $enum = null,
        public string|int|array|null $const = null,

        // string
        public ?string $pattern = null,
        public ?int $minLength = null,
        public ?int $maxLength = null,

        // integer
        public ?int $minimum = null,
        public ?int $maximum = null,
        public ?int $multipleOf = null,
        public ?int $exclusiveMinimum = null,
        public ?int $exclusiveMaximum = null,

        // array
        public ?int $minItems = null,
        public ?int $maxItems = null,
        public ?bool $uniqueItems = null,
        public ?int $minContains = null,
        public ?int $maxContains = null,

        // object
        public ?int $minProperties = null,
        public ?int $maxProperties = null,
        public ?bool $dependentRequired = null,
    ) {
        if (\is_array($enum)) {
            array_filter($enum, fn ($item) => \is_string($item)) === $enum || throw new InvalidArgumentException('All enum values must be strings.');
        }

        if (\is_string($const)) {
            '' !== trim($const) || throw new InvalidArgumentException('Const string must not be empty.');
        }

        if (\is_string($pattern)) {
            '' !== trim($pattern) || throw new InvalidArgumentException('Pattern string must not be empty.');
        }

        if (\is_int($minLength)) {
            $minLength >= 0 || throw new InvalidArgumentException('MinLength must be greater than or equal to 0.');

            if (\is_int($maxLength)) {
                $maxLength >= $minLength || throw new InvalidArgumentException('MaxLength must be greater than or equal to minLength.');
            }
        }

        if (\is_int($maxLength)) {
            $maxLength >= 0 || throw new InvalidArgumentException('MaxLength must be greater than or equal to 0.');
        }

        if (\is_int($minimum)) {
            $minimum >= 0 || throw new InvalidArgumentException('Minimum must be greater than or equal to 0.');

            if (\is_int($maximum)) {
                $maximum >= $minimum || throw new InvalidArgumentException('Maximum must be greater than or equal to minimum.');
            }
        }

        if (\is_int($maximum)) {
            $maximum >= 0 || throw new InvalidArgumentException('Maximum must be greater than or equal to 0.');
        }

        if (\is_int($multipleOf)) {
            $multipleOf >= 0 || throw new InvalidArgumentException('MultipleOf must be greater than or equal to 0.');
        }

        if (\is_int($exclusiveMinimum)) {
            $exclusiveMinimum >= 0 || throw new InvalidArgumentException('ExclusiveMinimum must be greater than or equal to 0.');

            if (\is_int($exclusiveMaximum)) {
                $exclusiveMaximum >= $exclusiveMinimum || throw new InvalidArgumentException('ExclusiveMaximum must be greater than or equal to exclusiveMinimum.');
            }
        }

        if (\is_int($exclusiveMaximum)) {
            $exclusiveMaximum >= 0 || throw new InvalidArgumentException('ExclusiveMaximum must be greater than or equal to 0.');
        }

        if (\is_int($minItems)) {
            $minItems >= 0 || throw new InvalidArgumentException('MinItems must be greater than or equal to 0.');

            if (\is_int($maxItems)) {
                $maxItems >= $minItems || throw new InvalidArgumentException('MaxItems must be greater than or equal to minItems.');
            }
        }

        if (\is_int($maxItems)) {
            $maxItems >= 0 || throw new InvalidArgumentException('MaxItems must be greater than or equal to 0.');
        }

        if (\is_bool($uniqueItems)) {
            true === $uniqueItems || throw new InvalidArgumentException('UniqueItems must be true when specified.');
        }

        if (\is_int($minContains)) {
            $minContains >= 0 || throw new InvalidArgumentException('MinContains must be greater than or equal to 0.');

            if (\is_int($maxContains)) {
                $maxContains >= $minContains || throw new InvalidArgumentException('MaxContains must be greater than or equal to minContains.');
            }
        }

        if (\is_int($maxContains)) {
            $maxContains >= 0 || throw new InvalidArgumentException('MaxContains must be greater than or equal to 0.');
        }

        if (\is_int($minProperties)) {
            $minProperties >= 0 || throw new InvalidArgumentException('MinProperties must be greater than or equal to 0.');

            if (\is_int($maxProperties)) {
                $maxProperties >= $minProperties || throw new InvalidArgumentException('MaxProperties must be greater than or equal to minProperties.');
            }
        }

        if (\is_int($maxProperties)) {
            $maxProperties >= 0 || throw new InvalidArgumentException('MaxProperties must be greater than or equal to 0.');
        }
    }
}
