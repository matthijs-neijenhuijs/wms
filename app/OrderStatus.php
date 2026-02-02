<?php

namespace App;

enum OrderStatus: string
{
    case Concept = 'concept';
    case Confirmed = 'confirmed';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
     case Cancelled = 'cancelled';
}
