<?php

namespace App;

enum OrderStatus: string
{
    case Concept = 'concept';
    case Expected = 'expected';
    case Processing = 'processing';
    case Paused = 'paused';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
