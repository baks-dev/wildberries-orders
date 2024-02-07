<?php

namespace BaksDev\Wildberries\Orders\Type\OrderStatus;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;

final class WbOrderStatusType extends Type
{

    public function convertToDatabaseValue($value, AbstractPlatform $platform): string
    {
        return (string) $value;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?WbOrderStatus
    {
        return !empty($value) ? new WbOrderStatus($value) : null;
    }

    public function getName(): string
    {
        return WbOrderStatus::TYPE;
    }


    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL($column);
    }
}