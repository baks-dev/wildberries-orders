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

    private WbOrderStatusInterface $status;


    public function __construct(WbOrderStatusInterface|self|string $status)
    {

        if(is_string($status) && class_exists($status))
        {
            $instance = new $status();

            if($instance instanceof WbOrderStatusInterface)
            {
                $this->status = $instance;
                return;
            }
        }

        if($status instanceof WbOrderStatusInterface)
        {
            $this->status = $status;
            return;
        }

        if($status instanceof self)
        {
            $this->status = $status->getWbOrderStatus();
            return;
        }

        /** @var WbOrderStatusInterface $declare */
        foreach(self::getDeclared() as $declare)
        {
            if($declare::equals($status))
            {
                $this->status = new $declare;
                return;
            }
        }

        throw new InvalidArgumentException(sprintf('Not found WbOrderStatus %s', $status));

    }


    public function __toString(): string
    {
        return $this->status->getvalue();
    }

    public function getWbOrderStatus(): WbOrderStatusInterface
    {
        return $this->status;
    }

    public function getWbOrderStatusValue(): string
    {
        return $this->status->getValue();
    }


    public static function cases(): array
    {
        $case = [];

        foreach(self::getDeclared() as $status)
        {
            /** @var WbOrderStatusInterface $status */
            $class = new $status;
            $case[$class::sort()] = new self($class);
        }
        
        return $case;
    }

    public static function getDeclared(): array
    {
        return array_filter(
            get_declared_classes(),
            static function($className) {
                return in_array(WbOrderStatusInterface::class, class_implements($className), true);
            }
        );
    }

    public function equals(mixed $status): bool
    {
        $status = new self($status);

        return $this->getWbOrderStatusValue() === $status->getWbOrderStatusValue();
    }


}