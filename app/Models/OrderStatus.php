<?php

namespace App\Models;

enum OrderStatus: string
{
    case Expected = 'expected';
    case Concept = 'concept';
    case OnHold = 'on_hold';
    case Validated = 'validated';
    case Confirmed = 'confirmed';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case Picking = 'picking';
    case Picked = 'picked';

}
