<?php

declare(strict_types=1);

namespace OpenAPITools\Tests\Generator\Hydrator\DataTests;

use OpenAPITools\Utils\File;
use PHPUnit\Framework\Assert;

final class Basic implements GeneratedFilesAssertion
{
    /** @param array<string, File> $files */
    public static function assertGeneratedFiles(array $files): void
    {
        Assert::assertCount(2, $files);

        Assert::assertArrayHasKey('Internal\Hydrators', $files);
        Assert::assertArrayHasKey('Internal\Hydrator\Operation\Root', $files);

        Assert::assertStringNotContainsString('namespace \\', GeneratedFiles::contents($files, 'Internal\Hydrators'));
        Assert::assertStringContainsString('public function hydrateObject(string $className, array $payload): object', GeneratedFiles::contents($files, 'Internal\Hydrators'));
        Assert::assertStringContainsString('public function hydrateObjects(string $className, iterable $payloads): \EventSauce\ObjectHydrator\IterableList', GeneratedFiles::contents($files, 'Internal\Hydrators'));
        Assert::assertStringContainsString('public function serializeObject(object $object): mixed', GeneratedFiles::contents($files, 'Internal\Hydrators'));
        Assert::assertStringContainsString('public function serializeObjects(iterable $payloads): \EventSauce\ObjectHydrator\IterableList', GeneratedFiles::contents($files, 'Internal\Hydrators'));
        Assert::assertStringContainsString('public function getObjectMapperOperation🌀Root(): \ApiClients\Client\GitHub\Basic\Internal\Hydrator\Operation\Root', GeneratedFiles::contents($files, 'Internal\Hydrators'));
        Assert::assertStringContainsString('final class Hydrators implements \EventSauce\ObjectHydrator\ObjectMapper', GeneratedFiles::contents($files, 'Internal\Hydrators'));

        Assert::assertStringNotContainsString('namespace \\', GeneratedFiles::contents($files, 'Internal\Hydrator\Operation\Root'));
        Assert::assertStringContainsString('public function hydrateObject(string $className, array $payload): object', GeneratedFiles::contents($files, 'Internal\Hydrator\Operation\Root'));
        Assert::assertStringContainsString('public function hydrateObjects(string $className, iterable $payloads): IterableList', GeneratedFiles::contents($files, 'Internal\Hydrator\Operation\Root'));
        Assert::assertStringContainsString('public function serializeObject(object $object): mixed', GeneratedFiles::contents($files, 'Internal\Hydrator\Operation\Root'));
        Assert::assertStringContainsString('public function serializeObjects(iterable $payloads): IterableList', GeneratedFiles::contents($files, 'Internal\Hydrator\Operation\Root'));
        Assert::assertStringContainsString('class Root implements ObjectMapper', GeneratedFiles::contents($files, 'Internal\Hydrator\Operation\Root'));
        Assert::assertStringContainsString('\'Ramsey\Uuid\UuidInterface\' => $this->serializeValueRamsey⚡️Uuid⚡️UuidInterface($object),', GeneratedFiles::contents($files, 'Internal\Hydrator\Operation\Root'));
    }
}
