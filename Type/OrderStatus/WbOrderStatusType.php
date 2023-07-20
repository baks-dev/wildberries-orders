<?php

namespace BaksDev\Wildberries\Orders\Type\OrderStatus;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use InvalidArgumentException;

final class WbOrderStatusType extends StringType
{

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        return $value instanceof WbOrderStatus ? $value->getStatusValue() : (new WbOrderStatus($value))->getStatusValue();
    }


    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        /** @var WbOrderStatus $status */
        foreach(WbOrderStatus::cases() as $status)
        {
            if($status->getStatus()::equals($value))
            {
                return $status;
            }
        }

        throw new InvalidArgumentException(sprintf('Not found Wildberries Order Status %s', $value));
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