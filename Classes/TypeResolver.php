<?php
declare(strict_types=1);

namespace Wwwision\GraphQL;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use InvalidArgumentException;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\Flow\Annotations as Flow;
use Psr\Log\LoggerInterface;
use Ttree\Headless\Types\TypeResolverBasedInterface;

/**
 * A type resolver (aka factory) for GraphQL type definitions.
 * This class is required in order to prevent multiple instantiation of the same type and to allow types to reference themselves
 *
 * Usage:
 *
 * Type::nonNull($typeResolver->get(SomeClass::class))
 *
 * @Flow\Scope("singleton")
 */
class TypeResolver
{
    /**
     * @Flow\Inject(name="Neos.Flow:SystemLogger")
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Type[]
     */
    private static array $types = [];

    /**
     * @param string|array $typeClassConfiguration
     * @param NodeType|null $nodeType
     * @return ObjectType
     */
    public function get($typeClassConfiguration, NodeType $nodeType = null)
    {
        $beginAt = microtime(true);
        $elapsed = function () use ($beginAt) {
            return (microtime(true) - $beginAt) * 1000;
        };
        $originalTypeClassConfiguration = $typeClassConfiguration;
        $typeClassName = $this->getTypeClassName($typeClassConfiguration);

        if ($nodeType instanceof NodeType) {
            $hash = md5(json_encode([$typeClassConfiguration, $nodeType->getName()]));
        } else {
            $hash = md5(json_encode([$typeClassConfiguration]));
        }


        $this->assertValidTypeClassName($typeClassName, $typeClassConfiguration);
        $loggerDetails = function () use ($typeClassName, $hash, $elapsed): array {
            return [$typeClassName, $hash, self::$types[$hash]->name, $elapsed()];
        };
        if (!(self::$types[$hash] ?? null) instanceof Type) {
            if (in_array(TypeResolverBasedInterface::class, class_implements($typeClassName))) {
                self::$types[$hash] = new $typeClassName($this, $nodeType);
            } else {
                self::$types[$hash] = new $typeClassName(is_array($originalTypeClassConfiguration) ? $originalTypeClassConfiguration : [], $nodeType);
            }
            $this->logger->info(vsprintf('TypeResolver: cache miss for %s with hash %s (type: %s) in %f ms.', $loggerDetails()));
        } else {
            $this->logger->info(vsprintf('TypeResolver: cache hit for %s with hash %s (type: %s) in %f ms.', $loggerDetails()));
        }
        $this->logger->info(json_encode(self::$types[$hash]->config));
        return self::$types[$hash];
    }

    protected function getTypeClassName(&$typeClassConfiguration) {
        if (is_array($typeClassConfiguration)) {
            $typeClassName = reset($typeClassConfiguration);
            ksort($typeClassConfiguration);
        } elseif (is_string($typeClassConfiguration)) {
            $typeClassName = $typeClassConfiguration;
        } else {
            throw new InvalidArgumentException('The Type classname can be of type string or an array of string');
        }
        return $typeClassName;
    }

    protected function assertValidTypeClassName(string $typeClassName, $typeClassConfiguration)
    {
        if (!is_subclass_of($typeClassName, Type::class)) {
            throw new InvalidArgumentException(sprintf('The TypeResolver can only resolve types extending "GraphQL\Type\Definition\Type", got "%s"', $typeClassConfiguration), 1461436398);
        }
    }
}