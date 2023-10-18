<?php

namespace BaksDev\Wildberries\Orders\Type\WildberriesStatus;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use InvalidArgumentException;

final class WildberriesStatusType extends StringType
{

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        return (string) $value;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        return new WildberriesStatus($value);
    }

    public function getName(): string
    {
        return WildberriesStatus::TYPE;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

}