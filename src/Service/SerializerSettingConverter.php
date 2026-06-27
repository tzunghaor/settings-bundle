<?php


namespace Tzunghaor\SettingsBundle\Service;


use Symfony\Component\PropertyInfo\Type as PropertyInfoType;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\TypeInfo\Type as TypeInfoType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Tzunghaor\SettingsBundle\Model\Type;

/**
 * Converts objects and arrays of objects to/from string that can be stored in DB using Symfony serializer component
 */
class SerializerSettingConverter implements SettingConverterInterface, SettingValueConverterInterface
{
    public function __construct(
        private SerializerInterface $serializer
    ) {
    }

    /**
     * @return bool true if this converter can convert to-from this type
     */
    public function supports(PropertyInfoType | TypeInfoType $type): bool
    {
        $type = Type::createFromAnyType($type);

        return $type->getTypeIdentifier() === 'object';
    }

    /**
     * @param mixed $value value used in setting section object
     *
     * @return string value persisted in DB
     */
    public function convertToString(PropertyInfoType | TypeInfoType $type, $value): string
    {
        return $this->serializer->serialize($value, 'json');
    }

    /**
     * @param string $value value persisted in DB
     *
     * @return mixed value used in setting section object
     */
    public function convertFromString(PropertyInfoType | TypeInfoType $type, string $value)
    {
        $type = Type::createFromAnyType($type);
        $className = $type->getClassName();
        $isCollection = $type->isCollection();
        $typeString = $className . ($isCollection ? '[]' : '');

        return $this->serializer->deserialize($value, $typeString, 'json');
    }
}
