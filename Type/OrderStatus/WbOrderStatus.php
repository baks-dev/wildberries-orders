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
            $this->status = $status->getValue();
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











    //    /**
    //     * @var WbOrderStatusEnum
    //     */
    //    private WbOrderStatusEnum $status;
    //
    //
    //    public function __construct(int|self|WbOrderStatusEnum $status)
    //    {
    //        if($status instanceof self)
    //		{
    //			$this->status = WbOrderStatusEnum::from($status->getValue());
    //		}
    //        else if($status instanceof WbOrderStatusEnum)
    //        {
    //            $this->status = $status;
    //        }
    //        else
    //        {
    //            $this->status = WbOrderStatusEnum::from($status);
    //        }
    //    }
    //
    //    /**
    //     * @return string
    //     */
    //    public function __toString() : string
    //    {
    //        return $this->status->value;
    //    }
    //
    //
    //    public function getValue() : int
    //    {
    //        return $this->status->value;
    //    }
    //
    //    /**
    //     * @return string
    //     */
    //    public function getName() : string
    //    {
    //        return $this->status->name;
    //    }
    //
    //    /**
    //     * @return array
    //     */
    //    public static function cases() : array
    //    {
    //        $case = null;
    //
    //        foreach(WbOrderStatusEnum::cases() as $status)
    //        {
    //            $case[] = new self($status);
    //        }
    //
    //        return $case;
    //    }
    //
    //    public function equals(WbOrderStatusEnum $status) : bool
    //    {
    //        return $this->status === $status;
    //    }
    //
}