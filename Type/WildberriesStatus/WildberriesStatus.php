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

    private WildberriesStatusInterface $status;

    public function __construct(WildberriesStatusInterface|self|string $status)
    {
        if(is_string($status) && class_exists($status))
        {
            $instance = new $status();

            if($instance instanceof WildberriesStatusInterface)
            {
                $this->status = $instance;
                return;
            }
        }

        if($status instanceof WildberriesStatusInterface)
        {
            $this->status = $status;
            return;
        }

        if($status instanceof self)
        {
            $this->status = $status->getWildberriesStatus();
            return;
        }

        /** @var WildberriesStatusInterface $declare */
        foreach(self::getDeclared() as $declare)
        {
            if($declare::equals($status))
            {
                $this->status = new $declare;
                return;
            }
        }

        throw new InvalidArgumentException(sprintf('Not found WildberriesStatus %s', $status));

    }


    public function __toString(): string
    {
        return $this->status->getvalue();
    }


    public function getWildberriesStatus(): WildberriesStatusInterface
    {
        return $this->status;
    }

    public function getWildberriesStatusValue(): string
    {
        return $this->status->getValue();
    }


    public static function cases(): array
    {
        $case = [];

        foreach(self::getDeclared() as $status)
        {
            /** @var WildberriesStatusInterface $class */
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
                return in_array(WildberriesStatusInterface::class, class_implements($className), true);
            }
        );
    }

    public function equals(mixed $status): bool
    {
        $status = new self($status);

        return $this->getWildberriesStatusValue() === $status->getWildberriesStatusValue();
    }



}