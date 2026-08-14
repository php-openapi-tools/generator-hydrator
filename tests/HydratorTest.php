<?php

declare(strict_types=1);

namespace OpenAPITools\Tests\Generator\Hydrator;

use cebe\openapi\Reader;
use OpenAPITools\Configuration\Gathering;
use OpenAPITools\Configuration\Package;
use OpenAPITools\Gatherer\Gatherer;
use OpenAPITools\Generator\Hydrator\Hydrator;
use OpenAPITools\Generator\Schema\Schema;
use OpenAPITools\Representation\Representation;
use OpenAPITools\TestData\DataSet;
use OpenAPITools\TestData\Provider;
use OpenAPITools\Tests\Generator\Hydrator\DataTests\GeneratedFilesAssertion;
use OpenAPITools\Utils\File;
use OpenAPITools\Utils\Namespace_;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\TestUtilities\TestCase;

use function array_key_exists;
use function class_exists;
use function is_string;
use function is_subclass_of;

final class HydratorTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(Provider::class, 'sets')]
    public function gather(DataSet $dataSet): void
    {
        $representation = $this->loadSpec($dataSet->fileName);

        /** @var class-string<GeneratedFilesAssertion> $testClassName */
        $testClassName = '\OpenAPITools\Tests\Generator\Hydrator\DataTests\\' . $dataSet->name;
        self::assertTrue(class_exists($testClassName));
        self::assertTrue(is_subclass_of($testClassName, GeneratedFilesAssertion::class));

        $package = new Package(
            new Package\Metadata(
                'GitHub',
                'Fully type safe generated GitHub REST API client',
                [],
            ),
            'api-clients',
            'github',
            'git@github.com:php-api-clients/github.git',
            'v0.2.x',
            null,
            new Package\Templates(
                __DIR__ . '/templates',
                [],
            ),
            new Package\Destination(
                'github',
                'src',
                'tests',
            ),
            new Namespace_(
                'ApiClients\Client\GitHub\\' . $dataSet->name,
                'ApiClients\Tests\Client\GitHub\\' . $dataSet->name,
            ),
            new Package\QA(
                phpcs: new Package\QA\Tool(true, null),
                phpstan: new Package\QA\Tool(
                    true,
                    'etc/phpstan-extension.neon',
                ),
                psalm: new Package\QA\Tool(false, null),
            ),
            new Package\State(
                [
                    'composer.json',
                    'composer.lock',
                ],
            ),
            [],
        );

        $files         = [];
        $buildFactory  = new BuilderFactory();
        $loadedSchemas = [];
        foreach (new Schema($buildFactory)->generate($package, $representation->namespace($package->namespace)) as $file) {
            self::assertInstanceOf(Node::class, $file->contents);
            if (array_key_exists($file->fqcn, $loadedSchemas)) {
                continue;
            }

            $loadedSchemas[$file->fqcn] = true;
            /** @phpstan-ignore ergebnis.noEval */
            eval(new Standard()->prettyPrint([
                new Node\Stmt\Declare_([
                    new Node\Stmt\DeclareDeclare('strict_types', new Node\Scalar\LNumber(1)),
                ]),
                $file->contents,
            ]));
        }

        $generatedFiles = new Hydrator($buildFactory, true)->generate($package, $representation->namespace($package->namespace));

        foreach ($generatedFiles as $generatedFile) {
            if (is_string($generatedFile->contents)) {
                $contents = $generatedFile->contents;
            } else {
                $contents = new Standard()->prettyPrint([
                    new Node\Stmt\Declare_([
                        new Node\Stmt\DeclareDeclare('strict_types', new Node\Scalar\LNumber(1)),
                    ]),
                    $generatedFile->contents,
                ]);
            }

            $files[$generatedFile->fqcn] = new File(
                $generatedFile->pathPrefix,
                $generatedFile->fqcn,
                $contents,
                File::DO_LOAD_ON_WRITE,
            );
        }

        $testClassName::assertGeneratedFiles($files);
    }

    private function loadSpec(string $dataSetName): Representation
    {
        return Gatherer::gather(
            Reader::readFromYamlFile($dataSetName),
            new Gathering(
                $dataSetName,
                null,
                new Gathering\Schemas(
                    true,
                    true,
                ),
            ),
        );
    }
}
