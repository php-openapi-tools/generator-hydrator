<?php

declare(strict_types=1);

namespace OpenAPITools\Tests\Generator\Hydrator\DataTests;

use OpenAPITools\Utils\File;
use PHPUnit\Framework\Assert;

final class DoubleUseOfTypes implements GeneratedFilesAssertion
{
    /** @param array<string, File> $files */
    public static function assertGeneratedFiles(array $files): void
    {
        Assert::assertCount(2, $files);

        Assert::assertArrayHasKey('Internal\Hydrators', $files);
        Assert::assertArrayHasKey('Internal\Hydrator\Operation\Root', $files);

        Assert::assertStringNotContainsString('namespace \\', GeneratedFiles::contents($files, 'Internal\Hydrators'));
        Assert::assertStringContainsString('public function hydrateObject(string $className, array $payload): object', GeneratedFiles::contents($files, 'Internal\Hydrators'));
        Assert::assertStringContainsString('public function getObjectMapperOperation🌀Root(): \ApiClients\Client\GitHub\DoubleUseOfTypes\Internal\Hydrator\Operation\Root', GeneratedFiles::contents($files, 'Internal\Hydrators'));
        Assert::assertStringContainsString('final class Hydrators implements \EventSauce\ObjectHydrator\ObjectMapper', GeneratedFiles::contents($files, 'Internal\Hydrators'));

        Assert::assertStringNotContainsString('namespace \\', GeneratedFiles::contents($files, 'Internal\Hydrator\Operation\Root'));
        Assert::assertStringContainsString('class Root implements ObjectMapper', GeneratedFiles::contents($files, 'Internal\Hydrator\Operation\Root'));
    }
}
