<?php

declare(strict_types=1);

namespace OpenAPITools\Tests\Generator\Hydrator\DataTests;

use OpenAPITools\Utils\File;
use PHPUnit\Framework\Assert;

final class DiscriminatedWebHooks implements GeneratedFilesAssertion
{
    /** @param array<string, File> $files */
    public static function assertGeneratedFiles(array $files): void
    {
        Assert::assertCount(2, $files);

        Assert::assertArrayHasKey('Internal\Hydrators', $files);
        Assert::assertArrayHasKey('Internal\Hydrator\WebHook\Delivery', $files);

        Assert::assertStringNotContainsString('namespace \\', GeneratedFiles::contents($files, 'Internal\Hydrators'));
        Assert::assertStringContainsString('public function hydrateObject(string $className, array $payload): object', GeneratedFiles::contents($files, 'Internal\Hydrators'));
        Assert::assertStringContainsString('public function getObjectMapperWebHook🪝Delivery(): \ApiClients\Client\GitHub\DiscriminatedWebHooks\Internal\Hydrator\WebHook\Delivery', GeneratedFiles::contents($files, 'Internal\Hydrators'));
        Assert::assertStringContainsString('\ApiClients\Client\GitHub\DiscriminatedWebHooks\Schema\WebHook\Delivery\Received\Request\ApplicationJson', GeneratedFiles::contents($files, 'Internal\Hydrators'));
        Assert::assertStringContainsString('final class Hydrators implements \EventSauce\ObjectHydrator\ObjectMapper', GeneratedFiles::contents($files, 'Internal\Hydrators'));

        Assert::assertStringNotContainsString('namespace \\', GeneratedFiles::contents($files, 'Internal\Hydrator\WebHook\Delivery'));
        Assert::assertStringContainsString('class Delivery implements ObjectMapper', GeneratedFiles::contents($files, 'Internal\Hydrator\WebHook\Delivery'));
    }
}
