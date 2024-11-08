<?php

declare(strict_types=1);

namespace OpenAPITools\Tests\Generator\Hydrator\DataTests;

use OpenAPITools\Utils\File;
use WyriHaximus\TestUtilities\TestCase;

final class Basic extends TestCase
{
    public static function assert(File ...$files): void
    {
        self::assertCount(2, $files);

        self::assertArrayHasKey('Internal\Hydrators', $files);
        self::assertArrayHasKey('Internal\Hydrator\Operation\Root', $files);

        self::assertStringContainsString('public function hydrateObject(string $className, array $payload) : object', $files['Internal\Hydrators']->contents);
        self::assertStringContainsString('public function hydrateObjects(string $className, iterable $payloads) : \EventSauce\ObjectHydrator\IterableList', $files['Internal\Hydrators']->contents);
        self::assertStringContainsString('public function serializeObject(object $object) : mixed', $files['Internal\Hydrators']->contents);
        self::assertStringContainsString('public function serializeObjects(iterable $payloads) : \EventSauce\ObjectHydrator\IterableList', $files['Internal\Hydrators']->contents);
        self::assertStringContainsString('public function getObjectMapperOperation🌀Root() : \ApiClients\Client\GitHub\Internal\Hydrator\Operation\Root', $files['Internal\Hydrators']->contents);
        self::assertStringContainsString('final class Hydrators implements \EventSauce\ObjectHydrator\ObjectMapper', $files['Internal\Hydrators']->contents);

        self::assertStringContainsString('public function hydrateObject(string $className, array $payload): object', $files['Internal\Hydrator\Operation\Root']->contents);
        self::assertStringContainsString('public function hydrateObjects(string $className, iterable $payloads): IterableList', $files['Internal\Hydrator\Operation\Root']->contents);
        self::assertStringContainsString('public function serializeObject(object $object): mixed', $files['Internal\Hydrator\Operation\Root']->contents);
        self::assertStringContainsString('public function serializeObjects(iterable $payloads): IterableList', $files['Internal\Hydrator\Operation\Root']->contents);
        self::assertStringContainsString('class Root implements ObjectMapper', $files['Internal\Hydrator\Operation\Root']->contents);
        self::assertStringContainsString('\'Ramsey\Uuid\UuidInterface\' => $this->serializeValueRamsey⚡️Uuid⚡️UuidInterface($object),', $files['Internal\Hydrator\Operation\Root']->contents);
    }
}
