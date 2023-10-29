<?php

namespace BaksDev\Wildberries\Orders\Type\OrderStatus;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use InvalidArgumentException;

final class WbOrderStatusType extends StringType
{

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        return (string) $value;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
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

}