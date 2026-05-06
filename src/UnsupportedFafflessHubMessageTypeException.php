<?php

namespace Faffless\CommunicationChannel;

use InvalidArgumentException;

class UnsupportedFafflessHubMessageTypeException extends InvalidArgumentException
{
    public function __construct(string $type)
    {
        parent::__construct("Unsupported Faffless Hub channel message type [{$type}].");
    }
}
