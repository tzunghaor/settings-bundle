<?php

namespace Tzunghaor\SettingsBundle\Model;

use Symfony\Component\PropertyInfo\Type as PropertyInfoType;
use Symfony\Component\TypeInfo\Type as TypeInfoType;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * Abstraction layer to support both old symfony/property-info and the new symfony/type-info Type.
 *
 * @todo: remove this and use symfony/type-info directly when symfony <7.1 support is dropped
 */
class Type
{
    private ?TypeInfoType $typeInfoType = null;
    private ?PropertyInfoType $propertyInfoType = null;

    private string $typeIdentifier;
    private bool $nullable;
    private ?string $className;
    private bool $collection;


    /**
     * For arrays $collection should be set to true and $typeIdentifier / $className should be the array item type / class
     */
    public function __construct(
        string  $typeIdentifier,
        bool    $nullable = false,
        ?string $className = null,
        bool    $collection = false
    ) {
        $this->typeIdentifier = $typeIdentifier;
        $this->nullable = $nullable;
        $this->className = $className;
        $this->collection = $collection;
    }


    public static function createFromPropertyInfo(PropertyInfoType $propertyInfoType): self
    {
        $nullable = $propertyInfoType->isNullable();
        $isCollection = $propertyInfoType->isCollection();
        if ($isCollection) {
            $itemType = $propertyInfoType->getCollectionValueTypes()[0] ?? $propertyInfoType;
        } else {
            $itemType = $propertyInfoType;
        }
        $typeIdentifier = $itemType->getBuiltinType();;
        $className = $itemType->getClassName();

        $instance = new self($typeIdentifier, $nullable, $className, $isCollection);
        $instance->propertyInfoType = $propertyInfoType;

        return $instance;
    }

    public static function createFromTypeInfo(TypeInfoType $typeInfoType): self
    {
        $nullable = $typeInfoType->isNullable();
        $isCollection = $typeInfoType instanceof TypeInfoType\CollectionType;
        if ($typeInfoType instanceof TypeInfoType\CollectionType) {
            $itemType = $typeInfoType->getCollectionValueType();
        } else {
            $itemType = $typeInfoType;
        }
        $typeIdentifier = method_exists($itemType, 'getTypeIdentifier') ?
            $itemType->getTypeIdentifier()->value : '';
        $className = $itemType instanceof TypeInfoType\ObjectType ? $itemType->getClassName() : null;

        $instance = new self($typeIdentifier, $nullable, $className, $isCollection);
        $instance->typeInfoType = $typeInfoType;

        return $instance;
    }

    public static function createFromAnyType(TypeInfoType | PropertyInfoType $anyType): self
    {
        return $anyType instanceof TypeInfoType ?
            self::createFromTypeInfo($anyType) :
            self::createFromPropertyInfo($anyType);
    }

    public static function isBuiltinType(string $dataTypeString): bool
    {
        if (class_exists(TypeIdentifier::class)) {
            return TypeIdentifier::tryFrom($dataTypeString) !== null;
        }

        return in_array($dataTypeString, PropertyInfoType::$builtinTypes, true);
    }

    public function isCollection(): bool
    {
        return $this->collection;
    }

    public function getClassName(): ?string
    {
        return $this->className;
    }

    public function getTypeIdentifier(): string
    {
        return $this->typeIdentifier;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function getTypeInfoType(): TypeInfoType
    {
        if ($this->typeInfoType === null) {
            $typeIdentifier = TypeIdentifier::tryFrom($this->typeIdentifier);
            if ($typeIdentifier === TypeIdentifier::OBJECT) {
                $this->typeInfoType = TypeInfoType::object($this->className);
            } else {
                $this->typeInfoType = TypeInfoType::builtin($typeIdentifier);
            }

            if ($this->collection) {
                $this->typeInfoType = TypeInfoType::list($this->typeInfoType);
            }
            if ($this->nullable) {
                $this->typeInfoType = TypeInfoType::nullable($this->typeInfoType);
            }
        }

        return $this->typeInfoType;
    }

    public function getPropertyInfoType(): PropertyInfoType
    {
        if ($this->propertyInfoType === null) {
            if ($this->collection) {
                $itemType = new PropertyInfoType(
                    $this->typeIdentifier, false, $this->className, false);
                $this->propertyInfoType = new PropertyInfoType('array', $this->nullable, null, true, null, [$itemType]);
            } else {
                $this->propertyInfoType = new PropertyInfoType(
                    $this->typeIdentifier, $this->nullable, $this->className, $this->collection);
            }
        }

        return $this->propertyInfoType;

    }

    public function equals(Type $other): bool
    {
        return
            $this->typeIdentifier === $other->typeIdentifier &&
            $this->className === $other->className &&
            $this->collection === $other->collection &&
            $this->nullable === $other->nullable
        ;
    }

    public function __toString(): string
    {
        $array = [
            'typeIdentifier' => $this->typeIdentifier,
            'className' => $this->className,
            'collection' => $this->collection,
            'nullable' => $this->nullable,
        ];

        return json_encode($array);
    }
}
