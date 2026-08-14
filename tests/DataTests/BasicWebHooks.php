<?php

declare(strict_types=1);

namespace OpenAPITools\Tests\Generator\Hydrator\DataTests;

use OpenAPITools\Utils\File;
use PHPUnit\Framework\Assert;

final class BasicWebHooks implements GeneratedFilesAssertion
{
    /** @param array<string, File> $files */
    public static function assertGeneratedFiles(array $files): void
    {
        Assert::assertCount(3, $files);

        Assert::assertArrayHasKey('Internal\Hydrators', $files);
        Assert::assertArrayHasKey('Internal\Hydrator\WebHook\Ping', $files);
        Assert::assertArrayHasKey('Internal\Hydrator\WebHook\Push', $files);

        Assert::assertStringNotContainsString('namespace \\', GeneratedFiles::contents($files, 'Internal\Hydrators'));
        Assert::assertStringContainsString('public function hydrateObject(string $className, array $payload): object', GeneratedFiles::contents($files, 'Internal\Hydrators'));
        Assert::assertStringContainsString('public function getObjectMapperWebHook🪝Ping(): \ApiClients\Client\GitHub\BasicWebHooks\Internal\Hydrator\WebHook\Ping', GeneratedFiles::contents($files, 'Internal\Hydrators'));
        Assert::assertStringContainsString('public function getObjectMapperWebHook🪝Push(): \ApiClients\Client\GitHub\BasicWebHooks\Internal\Hydrator\WebHook\Push', GeneratedFiles::contents($files, 'Internal\Hydrators'));
        Assert::assertStringContainsString('\ApiClients\Client\GitHub\BasicWebHooks\Schema\Ping', GeneratedFiles::contents($files, 'Internal\Hydrators'));
        Assert::assertStringContainsString('\ApiClients\Client\GitHub\BasicWebHooks\Schema\Push', GeneratedFiles::contents($files, 'Internal\Hydrators'));
        Assert::assertStringContainsString('final class Hydrators implements \EventSauce\ObjectHydrator\ObjectMapper', GeneratedFiles::contents($files, 'Internal\Hydrators'));

        Assert::assertStringNotContainsString('namespace \\', GeneratedFiles::contents($files, 'Internal\Hydrator\WebHook\Ping'));
        Assert::assertStringContainsString('class Ping implements ObjectMapper', GeneratedFiles::contents($files, 'Internal\Hydrator\WebHook\Ping'));

        Assert::assertStringNotContainsString('namespace \\', GeneratedFiles::contents($files, 'Internal\Hydrator\WebHook\Push'));
        Assert::assertStringContainsString('class Push implements ObjectMapper', GeneratedFiles::contents($files, 'Internal\Hydrator\WebHook\Push'));
    }
}
