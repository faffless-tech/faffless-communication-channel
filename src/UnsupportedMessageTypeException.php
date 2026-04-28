<?php

namespace Rpos\MessagingChannel;

use InvalidArgumentException;

class UnsupportedMessageTypeException extends InvalidArgumentException
{
    public function __construct(string $type)
    {
        parent::__construct("Unsupported messaging channel message type [{$type}].");
    }
}
