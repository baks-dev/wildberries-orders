<?php

namespace BaksDev\Wildberries\Orders\Type\WildberriesStatus;

use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\Collection\WbOrderStatusInterface;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\Collection\WildberriesStatusInterface;
use InvalidArgumentException;

/**
 * Статусы заказов Wildberries
 */
final class WildberriesStatus
{
    public const TYPE = 'wb_status';


    private ?WildberriesStatusInterface $status = null;


    public function __construct(self|string|WildberriesStatusInterface $status)
    {

        if($status instanceof WildberriesStatusInterface)
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
            /** @var WildberriesStatusInterface $class */
            foreach(self::getDeclaredWildberriesStatus() as $class)
            {
                if($class::equals($status))
                {
                    $this->status = new $class;
                    break;
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

        foreach(self::getDeclaredWildberriesStatus() as $status)
        {
            /** @var WildberriesStatusInterface $class */
            $class = new $status;
            $case[$class::sort()] = new self($class);
        }

        return $case;
    }


    public static function getDeclaredWildberriesStatus(): array
    {
        return array_filter(get_declared_classes(), static function($className) {
            return in_array(WbOrderStatusInterface::class, class_implements($className), true);
        });
    }



    //	/**
    //	 * @var WbClientStatusEnum
    //	 */
    //	private WbClientStatusEnum $status;
    //
    //
    //	public function __construct(int|self|WbClientStatusEnum $status)
    //	{
    //		if($status instanceof self)
    //		{
    //			$this->status = WbClientStatusEnum::from($status->getValue());
    //		}
    //		else if($status instanceof WbClientStatusEnum)
    //		{
    //			$this->status = $status;
    //		}
    //		else
    //		{
    //			$this->status = WbClientStatusEnum::from($status);
    //		}
    //	}
    //
    //
    //	/**
    //	 * @return string
    //	 */
    //	public function __toString() : string
    //	{
    //		return $this->status->value;
    //	}
    //
    //
    //	public function getValue() : int
    //	{
    //		return $this->status->value;
    //	}
    //
    //
    //	/**
    //	 * @return string
    //	 */
    //	public function getName() : string
    //	{
    //		return $this->status->name;
    //	}
    //
    //
    //	/**
    //	 * @return array
    //	 */
    //	public static function cases() : array
    //	{
    //		$case = null;
    //
    //		foreach(WbClientStatusEnum::cases() as $status)
    //		{
    //			$case[] = new self($status);
    //		}
    //
    //		return $case;
    //	}
    //
    //
    //	public function equals(WbClientStatusEnum $status) : bool
    //	{
    //		return $this->status === $status;
    //	}

}