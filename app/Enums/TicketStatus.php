<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Waiting = 'waiting';
    case Serving = 'serving';
    case Done = 'done';
    case Skipped = 'skipped';
}
