<?php

declare(strict_types=1);

namespace OpenWms\FilamentDynamicAiCharts\Exceptions;

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
