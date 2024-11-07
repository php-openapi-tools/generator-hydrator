<?php

declare(strict_types=1);

namespace OpenAPITools\Generator\Hydrator;

use EventSauce\ObjectHydrator\IterableList;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\ObjectMapperCodeGenerator;
use Generator;
use OpenAPITools\Contract\FileGenerator;
use OpenAPITools\Contract\Package;
use OpenAPITools\Representation;
use OpenAPITools\Utils\ClassString;
use OpenAPITools\Utils\File;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use ReflectionMethod;

use function array_filter;
use function array_key_exists;
use function array_map;
use function array_unique;
use function count;
use function trim;
use function ucfirst;

final readonly class Hydrator implements FileGenerator
{
    public function __construct(private BuilderFactory $builderFactory)
    {
    }

    /** @return iterable<File> */
    public function generate(Package $package, Representation\Namespaced\Representation $representation): iterable
    {
        $hydratorClassName = ClassString::factory($package->namespace, 'Internal\\Hydrators');
        $knownSchemas      = [];
        $stmt              = $this->builderFactory->namespace($hydratorClassName->namespace->source);
        $hydrators         = [];
        foreach ($representation->client->paths as $path) {
            $hydrators[] = $path->hydrator;
        }

        $class = $this->builderFactory->class($hydratorClassName->className)->makeFinal()->implement('\\' . ObjectMapper::class);

        $usefullHydrators = [];
        foreach ($hydrators as $hydrator) {
            $usefullHydrators[$hydrator->className->relative] = array_filter($hydrator->schemas, static function (Representation\Namespaced\Schema $schema) use (&$knownSchemas): bool {
                $className = $schema->className->fullyQualified->source;
                if (array_key_exists($className, $knownSchemas)) {
                    return false;
                }

                $knownSchemas[$className] = $className;

                return true;
            });
        }

        $matchHydrators = array_filter($hydrators, static fn (Representation\Namespaced\Hydrator $hydrator): bool => count($usefullHydrators[$hydrator->className->relative]) > 0);

        $schemaClasses = [];
        foreach ($hydrators as $hydrator) {
            foreach ($hydrator->schemas as $schema) {
                $schemaClasses[] = trim($schema->className->fullyQualified->source, '\\');
            }

            yield new File(
                $package->destination->source,
                $hydrator->className->relative,
                (new ObjectMapperCodeGenerator())->dump(
                    array_unique(
                        array_filter(
                            $schemaClasses,
                            static fn (string $className): bool => count((new ReflectionMethod($className, '__construct'))->getParameters()) > 0,
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
                (new Param('className'))->setType('string'),
                (new Param('payload'))->setType('array'),
            ])->addStmt(
                new Node\Stmt\Return_(
                    new Node\Expr\Match_(
                        new Node\Expr\Variable('className'),
                        array_map(static fn (Representation\Namespaced\Hydrator $hydrator): Node\MatchArm => new Node\MatchArm(
                            array_map(static fn (Representation\Namespaced\Schema $schema): Node\Scalar\String_ => new Node\Scalar\String_(
                                $schema->className->fullyQualified->source,
                            ), $usefullHydrators[$hydrator->className->relative]),
                            new Node\Expr\MethodCall(
                                new Node\Expr\MethodCall(
                                    new Node\Expr\Variable('this'),
                                    'getObjectMapper' . ucfirst($hydrator->methodName),
                                ),
                                'hydrateObject',
                                [
                                    new Node\Arg(
                                        new Node\Expr\Variable('className'),
                                    ),
                                    new Node\Arg(
                                        new Node\Expr\Variable('payload'),
                                    ),
                                ],
                            ),
                        ), $matchHydrators),
                    ),
                ),
            ),
        );

        $class->addStmt(
            $this->builderFactory->method('hydrateObjects')->makePublic()->setReturnType('\\' . IterableList::class)->addParams([
                (new Param('className'))->setType('string'),
                (new Param('payloads'))->setType('iterable'),
            ])->addStmt(
                new Node\Stmt\Return_(
                    new Node\Expr\New_(
                        new Node\Name('\\' . IterableList::class),
                        [
                            new Node\Arg(
                                new Node\Expr\MethodCall(
                                    new Node\Expr\Variable('this'),
                                    'doHydrateObjects',
                                    [
                                        new Node\Arg(
                                            new Node\Expr\Variable('className'),
                                        ),
                                        new Node\Arg(
                                            new Node\Expr\Variable('payloads'),
                                        ),
                                    ],
                                ),
                            ),
                        ],
                    ),
                ),
            ),
        );

        $class->addStmt(
            $this->builderFactory->method('doHydrateObjects')->makePrivate()->setReturnType('\\' . Generator::class)->addParams([
                (new Param('className'))->setType('string'),
                (new Param('payloads'))->setType('iterable'),
            ])->addStmt(
                new Node\Stmt\Foreach_(
                    new Node\Expr\Variable('payloads'),
                    new Node\Expr\Variable('payload'),
                    [
                        'keyVar' => new Node\Expr\Variable('index'),
                        'stmts' => [
                            new Node\Stmt\Expression(
                                new Node\Expr\Yield_(
                                    new Node\Expr\MethodCall(
                                        new Node\Expr\Variable('this'),
                                        'hydrateObject',
                                        [
                                            new Node\Arg(
                                                new Node\Expr\Variable('className'),
                                            ),
                                            new Node\Arg(
                                                new Node\Expr\Variable('payload'),
                                            ),
                                        ],
                                    ),
                                    new Node\Expr\Variable('index'),
                                ),
                            ),
                        ],
                    ],
                ),
            ),
        );

        $class->addStmt(
            $this->builderFactory->method('serializeObject')->makePublic()->setReturnType('mixed')->addParams([
                (new Param('object'))->setType('object'),
            ])->addStmt(
                new Node\Stmt\Return_(
                    new Node\Expr\MethodCall(
                        new Node\Expr\Variable('this'),
                        'serializeObjectOfType',
                        [
                            new Node\Arg(
                                new Node\Expr\Variable('object'),
                            ),
                            new Node\Arg(
                                new Node\Expr\ClassConstFetch(
                                    new Node\Expr\Variable('object'),
                                    'class',
                                ),
                            ),
                        ],
                    ),
                ),
            ),
        );

        $class->addStmt(
            $this->builderFactory->method('serializeObjectOfType')->makePublic()->setReturnType('mixed')->addParams([
                (new Param('object'))->setType('object'),
                (new Param('className'))->setType('string'),
            ])->addStmt(
                new Node\Stmt\Return_(
                    new Node\Expr\Match_(
                        new Node\Expr\Variable('className'),
                        array_map(static fn (Representation\Namespaced\Hydrator $hydrator): Node\MatchArm => new Node\MatchArm(
                            array_map(static fn (Representation\Namespaced\Schema $schema): Node\Scalar\String_ => new Node\Scalar\String_(
                                $schema->className->fullyQualified->source,
                            ), $usefullHydrators[$hydrator->className->relative]),
                            new Node\Expr\MethodCall(
                                new Node\Expr\MethodCall(
                                    new Node\Expr\Variable('this'),
                                    'getObjectMapper' . ucfirst($hydrator->methodName),
                                ),
                                'serializeObject',
                                [
                                    new Node\Arg(
                                        new Node\Expr\Variable('object'),
                                    ),
                                ],
                            ),
                        ), $matchHydrators),
                    ),
                ),
            ),
        );

        $class->addStmt(
            $this->builderFactory->method('serializeObjects')->makePublic()->setReturnType('\\' . IterableList::class)->addParams([
                (new Param('payloads'))->setType('iterable'),
            ])->addStmt(
                new Node\Stmt\Return_(
                    new Node\Expr\New_(
                        new Node\Name('\\' . IterableList::class),
                        [
                            new Node\Arg(
                                new Node\Expr\MethodCall(
                                    new Node\Expr\Variable('this'),
                                    'doSerializeObjects',
                                    [
                                        new Node\Arg(
                                            new Node\Expr\Variable('payloads'),
                                        ),
                                    ],
                                ),
                            ),
                        ],
                    ),
                ),
            ),
        );

        $class->addStmt(
            $this->builderFactory->method('doSerializeObjects')->makePrivate()->setReturnType('\\' . Generator::class)->addParams([
                (new Param('objects'))->setType('iterable'),
            ])->addStmt(
                new Node\Stmt\Foreach_(
                    new Node\Expr\Variable('objects'),
                    new Node\Expr\Variable('object'),
                    [
                        'keyVar' => new Node\Expr\Variable('index'),
                        'stmts' => [
                            new Node\Stmt\Expression(
                                new Node\Expr\Yield_(
                                    new Node\Expr\MethodCall(
                                        new Node\Expr\Variable('this'),
                                        'serializeObject',
                                        [
                                            new Node\Arg(
                                                new Node\Expr\Variable('object'),
                                            ),
                                        ],
                                    ),
                                    new Node\Expr\Variable('index'),
                                ),
                            ),
                        ],
                    ],
                ),
            ),
        );

        foreach ($hydrators as $hydrator) {
            $class->addStmt(
                $this->builderFactory->method('getObjectMapper' . ucfirst($hydrator->methodName))->makePublic()->setReturnType(trim($hydrator->className->fullyQualified->source, '\\'))->addStmts([
                    new Node\Stmt\If_(
                        new Node\Expr\BinaryOp\Identical(
                            new Node\Expr\Instanceof_(
                                new Node\Expr\PropertyFetch(
                                    new Node\Expr\Variable('this'),
                                    $hydrator->methodName,
                                ),
                                new Node\Name($hydrator->className->fullyQualified->source),
                            ),
                            new Node\Expr\ConstFetch(new Node\Name('false')),
                        ),
                        [
                            'stmts' => [
                                new Node\Stmt\Expression(
                                    new Node\Expr\Assign(
                                        new Node\Expr\PropertyFetch(
                                            new Node\Expr\Variable('this'),
                                            $hydrator->methodName,
                                        ),
                                        new Node\Expr\New_(
                                            new Node\Name($hydrator->className->fullyQualified->source),
                                        ),
                                    ),
                                ),
                            ],
                        ],
                    ),
                    new Node\Stmt\Return_(
                        new Node\Expr\PropertyFetch(
                            new Node\Expr\Variable('this'),
                            $hydrator->methodName,
                        ),
                    ),
                ]),
            );
        }

        yield new File($package->destination->source, $hydratorClassName->relative, $stmt->addStmt($class)->getNode(), File::DO_LOAD_ON_WRITE);
    }
}
