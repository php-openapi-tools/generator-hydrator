<?php

declare(strict_types=1);

namespace OpenAPITools\Tests\Generator\Hydrator\DataTests;

use OpenAPITools\Utils\File;
use PHPUnit\Framework\Assert;

final class NestedSchema implements GeneratedFilesAssertion
{
    /** @param array<string, File> $files */
    public static function assertGeneratedFiles(array $files): void
    {
        Assert::assertCount(2, $files);
    }
}
