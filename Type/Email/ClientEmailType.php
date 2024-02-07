<?php

namespace BaksDev\Wildberries\Orders\Type\Email;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;

final class ClientEmailType extends Type
{

    public function convertToDatabaseValue($value, AbstractPlatform $platform): string
    {
        return (string) $value;
    }
    
    public function convertToPHPValue($value, AbstractPlatform $platform): ?ClientEmail
    {
        return !empty($value) ? new ClientEmail($value) : null;
    }
    
    public function getName(): string
    {
        return ClientEmail::TYPE;
    }
    
    public function requiresSQLCommentHint(AbstractPlatform $platform) : bool
    {
        return true;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL($column);
    }
}