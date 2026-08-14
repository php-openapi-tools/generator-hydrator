<?php

declare(strict_types=1);

namespace OpenAPITools\Tests\Generator\Hydrator\DataTests;

use OpenAPITools\Utils\File;
use PHPUnit\Framework\Assert;

final class MultiVariantWebHooks implements GeneratedFilesAssertion
{
    /** @param array<string, File> $files */
    public static function assertGeneratedFiles(array $files): void
    {
        Assert::assertCount(2, $files);

        Assert::assertArrayHasKey('Internal\Hydrators', $files);
        Assert::assertArrayHasKey('Internal\Hydrator\WebHook\Notify', $files);

        Assert::assertStringNotContainsString('namespace \\', GeneratedFiles::contents($files, 'Internal\Hydrators'));
        Assert::assertStringContainsString('public function hydrateObject(string $className, array $payload): object', GeneratedFiles::contents($files, 'Internal\Hydrators'));
        Assert::assertStringContainsString('public function getObjectMapperWebHook🪝Notify(): \ApiClients\Client\GitHub\MultiVariantWebHooks\Internal\Hydrator\WebHook\Notify', GeneratedFiles::contents($files, 'Internal\Hydrators'));
        Assert::assertStringContainsString('\ApiClients\Client\GitHub\MultiVariantWebHooks\Schema\Alpha', GeneratedFiles::contents($files, 'Internal\Hydrators'));
        Assert::assertStringContainsString('\ApiClients\Client\GitHub\MultiVariantWebHooks\Schema\Beta', GeneratedFiles::contents($files, 'Internal\Hydrators'));
        Assert::assertStringContainsString('final class Hydrators implements \EventSauce\ObjectHydrator\ObjectMapper', GeneratedFiles::contents($files, 'Internal\Hydrators'));

        Assert::assertStringNotContainsString('namespace \\', GeneratedFiles::contents($files, 'Internal\Hydrator\WebHook\Notify'));
        Assert::assertStringContainsString('class Notify implements ObjectMapper', GeneratedFiles::contents($files, 'Internal\Hydrator\WebHook\Notify'));
    }
}
