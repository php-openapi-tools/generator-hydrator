<?php

declare(strict_types=1);

namespace OpenAPITools\Generator\Hydrator;

use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\ObjectMapperCodeGenerator;
use Generator;
use OpenAPITools\Contract\FileGenerator;
use OpenAPITools\Contract\Package;
use OpenAPITools\Generator\Utils\Builder\ExpressionBuilder;
use OpenAPITools\Generator\Utils\Builder\MatchBuilder;
use OpenAPITools\Generator\Utils\Builder\StatementBuilder;
use OpenAPITools\Representation;
use OpenAPITools\Utils\ClassString;
use OpenAPITools\Utils\File;
use OpenAPITools\Utils\Namespace_;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Stmt\Return_;
use ReflectionMethod;

use function array_filter;
use function array_key_exists;
use function array_map;
use function array_unique;
use function array_values;
use function count;
use function trim;
use function ucfirst;

/** @api */
final readonly class Hydrator implements FileGenerator
{
    private const string ITERABLE_LIST_CLASS = '\\EventSauce\\ObjectHydrator\\IterableList';

    public function __construct(
        private BuilderFactory $builderFactory,
        private bool $includeWebHookHydrators,
    ) {
    }

    /** @return iterable<File> */
    public function generate(Package $package, Representation\Namespaced\Representation $representation): iterable
    {
        /** @var Package&object{namespace: Namespace_, destination: object{source: string}} $typedPackage */
        $typedPackage = $package;

        $hydratorClassName = ClassString::factory($typedPackage->namespace, 'Internal\\Hydrators');
        $knownSchemas      = [];
        $stmt              = $this->builderFactory->namespace($hydratorClassName->namespace->source);
        $hydrators         = [];
        foreach ($representation->client->paths as $path) {
            $hydrators[] = $path->hydrator;
        }

        foreach ($representation->webHooks as $webHookEvent) {
            if (! $this->includeWebHookHydrators) {
                continue;
            }

            $hydrators[] = $webHookEvent->hydrator;
        }

        $class = $this->builderFactory->class($hydratorClassName->className)->makeFinal()->implement('\\' . ObjectMapper::class);

        /**
         * Both of these stay reindexed on purpose. They end up as match arms and
         * as the conditions within them, and the pretty printer walks those by
         * numeric index — a gap left behind by filtering makes it read past the
         * end of the array.
         */
        $usefullHydrators = [];
        foreach ($hydrators as $hydrator) {
            $usefullHydrators[$hydrator->className->relative] = array_values(array_filter($hydrator->schemas, static function (Representation\Namespaced\Schema $schema) use (&$knownSchemas): bool {
                $className = $schema->className->fullyQualified->source;
                if (array_key_exists($className, $knownSchemas)) {
                    return false;
                }

                $knownSchemas[$className] = $className;

                return true;
            }));
        }

        $matchHydrators = array_values(array_filter($hydrators, static fn (Representation\Namespaced\Hydrator $hydrator): bool => count($usefullHydrators[$hydrator->className->relative]) > 0));

        $schemaClasses = [];
        foreach ($hydrators as $hydrator) {
            foreach ($hydrator->schemas as $schema) {
                $schemaClasses[] = trim($schema->className->fullyQualified->source, '\\');
            }

            yield new File(
                $typedPackage->destination->source,
                $hydrator->className->relative,
                new ObjectMapperCodeGenerator()->dump(
                    array_unique(
                        array_filter(
                            $schemaClasses,
                            static fn (string $className): bool => count(new ReflectionMethod($className, '__construct')->getParameters()) > 0,
                        ),
                    ),
                    trim($hydrator->className->fullyQualified->source, '\\'),
                ),
                File::DO_LOAD_ON_WRITE,
            );

            $schemaClasses = [];

            $class->addStmt($this->builderFactory->property($hydrator->methodName)->setType('?' . $hydrator->className->fullyQualified->source)->setDefault(null)->makePrivate());
        }

        $class->addStmt(
            $this->builderFactory->method('hydrateObject')->makePublic()->setReturnType('object')->addParams([
                new Param('className')->setType('string'),
                new Param('payload')->setType('array'),
            ])->addStmt(
                new Return_(
                    $this->objectMapperMatch($matchHydrators, $usefullHydrators, 'hydrateObject', ['className', 'payload']),
                ),
            ),
        );

        $class->addStmt(
            $this->builderFactory->method('hydrateObjects')->makePublic()->setReturnType(self::ITERABLE_LIST_CLASS)->addParams([
                new Param('className')->setType('string'),
                new Param('payloads')->setType('iterable'),
            ])->addStmt(
                new Return_(
                    ExpressionBuilder::newInstance(self::ITERABLE_LIST_CLASS, [
                        ExpressionBuilder::thisMethod('doHydrateObjects', ['className', 'payloads']),
                    ]),
                ),
            ),
        );

        $class->addStmt(
            $this->builderFactory->method('doHydrateObjects')->makePrivate()->setReturnType('\\' . Generator::class)->addParams([
                new Param('className')->setType('string'),
                new Param('payloads')->setType('iterable'),
            ])->addStmt(
                StatementBuilder::yieldMapOverIterable('payloads', 'payload', 'index', 'hydrateObject', ['className', 'payload']),
            ),
        );

        $class->addStmt(
            $this->builderFactory->method('serializeObject')->makePublic()->setReturnType('mixed')->addParams([
                new Param('object')->setType('object'),
            ])->addStmt(
                new Return_(
                    ExpressionBuilder::thisMethod('serializeObjectOfType', [
                        'object',
                        ExpressionBuilder::objectClassConstant('object'),
                    ]),
                ),
            ),
        );

        $class->addStmt(
            $this->builderFactory->method('serializeObjectOfType')->makePublic()->setReturnType('mixed')->addParams([
                new Param('object')->setType('object'),
                new Param('className')->setType('string'),
            ])->addStmt(
                new Return_(
                    $this->objectMapperMatch($matchHydrators, $usefullHydrators, 'serializeObject', ['object']),
                ),
            ),
        );

        $class->addStmt(
            $this->builderFactory->method('serializeObjects')->makePublic()->setReturnType(self::ITERABLE_LIST_CLASS)->addParams([
                new Param('payloads')->setType('iterable'),
            ])->addStmt(
                new Return_(
                    ExpressionBuilder::newInstance(self::ITERABLE_LIST_CLASS, [
                        ExpressionBuilder::thisMethod('doSerializeObjects', ['payloads']),
                    ]),
                ),
            ),
        );

        $class->addStmt(
            $this->builderFactory->method('doSerializeObjects')->makePrivate()->setReturnType('\\' . Generator::class)->addParams([
                new Param('objects')->setType('iterable'),
            ])->addStmt(
                StatementBuilder::yieldMapOverIterable('objects', 'object', 'index', 'serializeObject', ['object']),
            ),
        );

        foreach ($hydrators as $hydrator) {
            $class->addStmt(
                $this->builderFactory->method('getObjectMapper' . ucfirst($hydrator->methodName))->makePublic()->setReturnType($hydrator->className->fullyQualified->source)->addStmts([
                    ExpressionBuilder::lazyInitIfNotInstance($hydrator->methodName, $hydrator->className->fullyQualified->source),
                    ExpressionBuilder::returnProperty($hydrator->methodName),
                ]),
            );
        }

        yield new File($typedPackage->destination->source, $hydratorClassName->relative, $stmt->addStmt($class)->getNode(), File::DO_LOAD_ON_WRITE);
    }

    /**
     * @param list<Representation\Namespaced\Hydrator>              $matchHydrators
     * @param array<string, list<Representation\Namespaced\Schema>> $usefullHydrators
     * @param list<string>                                          $delegateArguments
     */
    private function objectMapperMatch(array $matchHydrators, array $usefullHydrators, string $delegateMethod, array $delegateArguments): Match_
    {
        return MatchBuilder::onClassNameStrings(
            'className',
            array_map(
                static fn (Representation\Namespaced\Hydrator $hydrator): array => [
                    'classNames' => array_map(
                        static fn (Representation\Namespaced\Schema $schema): string => $schema->className->fullyQualified->source,
                        $usefullHydrators[$hydrator->className->relative],
                    ),
                    'body' => ExpressionBuilder::methodCall(
                        ExpressionBuilder::thisMethod('getObjectMapper' . ucfirst($hydrator->methodName)),
                        $delegateMethod,
                        $delegateArguments,
                    ),
                ],
                $matchHydrators,
            ),
        );
    }
}
