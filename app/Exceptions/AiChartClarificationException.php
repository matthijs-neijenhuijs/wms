<?php

namespace App\Exceptions;

use DomainException;

class AiChartClarificationException extends DomainException
{
    public function __construct(
        public readonly string $clarificationQuestion,
        string $message = 'More information is needed to generate a chart.'
    ) {
        parent::__construct($message);
    }
}
