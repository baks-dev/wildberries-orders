<?php

namespace BaksDev\Wildberries\Orders\Type\OrderStatus;

use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\Collection\WbOrderStatusInterface;
use InvalidArgumentException;

/**
 * Статусы заказов Wildberries
 */
final class WbOrderStatus
{

    public const TYPE = 'wb_order_status';

    private ?WbOrderStatusInterface $status = null;


    public function __construct(self|string|WbOrderStatusInterface $status)
    {

        if($status instanceof WbOrderStatusInterface)
        {
            $this->status = $status;
            return;
        }

        if($status instanceof $this)
        {
            $this->status = $status->getStatus();
            return;
        }

        if(is_string($status))
        {
            /** @var WbOrderStatusInterface $class */
            foreach(self::getDeclaredWbOrderStatus() as $class)
            {
                if($class::equals($status))
                {
                    $this->status = new $class;
                    return;
                }
            }
        }

        throw new InvalidArgumentException(sprintf('Not found Wildberries Order Status %s', $status));

    }


    public function __toString(): string
    {
        return $this->status ? $this->status->getvalue() : '';
    }


    /** Возвращает значение ColorsInterface */
    public function getStatus(): WbOrderStatusInterface
    {
        return $this->status;
    }


    /** Возвращает значение ColorsInterface */
    public function getStatusValue(): string
    {
        return $this->status->getValue();
    }


    public static function cases(): array
    {
        $case = [];

        foreach(self::getDeclaredWbOrderStatus() as $status)
        {
            /** @var WbOrderStatusInterface $status */
            $class = new $status;
            $case[$class::sort()] = new self($class);
        }
        
        return $case;
    }


    public static function getDeclaredWbOrderStatus(): array
    {
        return array_filter(
            get_declared_classes(),
            static function($className)
                {
                    return in_array(WbOrderStatusInterface::class, class_implements($className), true);
                },
        );
    }
}