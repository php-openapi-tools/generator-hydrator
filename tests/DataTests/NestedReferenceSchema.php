<?php

declare(strict_types=1);

namespace OpenAPITools\Tests\Generator\Hydrator\DataTests;

use OpenAPITools\Utils\File;
use WyriHaximus\TestUtilities\TestCase;

final class NestedReferenceSchema extends TestCase
{
    public static function assert(File ...$files): void
    {
        self::assertCount(2, $files);
    }
}
