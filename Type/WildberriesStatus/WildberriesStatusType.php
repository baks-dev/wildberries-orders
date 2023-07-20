<?php

namespace BaksDev\Wildberries\Orders\Type\WildberriesStatus;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use InvalidArgumentException;

final class WildberriesStatusType extends StringType
{

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {

        return $value instanceof WildberriesStatus ? $value->getStatusValue() : (new WildberriesStatus($value))->getStatusValue();

    }

    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {

        /** @var WildberriesStatus $status */
        foreach(WildberriesStatus::cases() as $status)
        {
            if($status->getStatus()::equals($value))
            {
                return $status;
            }
        }

        throw new InvalidArgumentException(sprintf('Not found Wildberries Status %s', $value));

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