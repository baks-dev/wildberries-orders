<?php

namespace BaksDev\Wildberries\Orders\Type\WildberriesStatus;


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
            $this->status = $status->getStatus();
            return;
        }

        if(is_string($status))
        {
            if(class_exists($status))
            {
                $this->status = new $status();
                return;
            }

            /** @var WildberriesStatusInterface $class */
            foreach(self::getDeclaredWildberriesStatus() as $class)
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
    public function getStatus(): WildberriesStatusInterface
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
            return in_array(WildberriesStatusInterface::class, class_implements($className), true);
        });
    }

}