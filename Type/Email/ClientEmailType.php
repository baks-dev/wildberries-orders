<?php

namespace BaksDev\Wildberries\Orders\Type\Email;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class ClientEmailType extends StringType
{

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        return $value instanceof ClientEmail ? $value->getValue() : $value;
    }
    
    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
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
    
}