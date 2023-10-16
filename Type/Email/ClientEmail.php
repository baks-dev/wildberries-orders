<?php

namespace BaksDev\Wildberries\Orders\Type\Email;

final class ClientEmail
{
    public const TYPE = 'client_email';
    
    private ?string $value;
    
    public function __construct(?string $value = null)
    {
        $this->value = empty($value) ? null : mb_strtolower($value);
    }
    
    public function __toString(): string
    {
        return $this->value ?: '';
    }
    
    public function isEqual(self $other) : bool
    {
        return $this->getValue() === $other->getValue();
    }
    
    public function getValue() : ?string
    {
        return $this->value;
    }
    
    public function getUserName() : ?string
    {
        return $this->value ? substr($this->value, 0, strrpos($this->value, '@')) : null;
    }
    
    public function getHostName() : ?string
    {
        return $this->value ? substr($this->value, strrpos($this->value, '@') + 1) : null;
    }
    
}