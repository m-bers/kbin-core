<?php

declare(strict_types=1);

namespace App\Event\Entry;

use App\Entity\Entry;

class EntryPinEvent
{
    public function __construct(public Entry $entry)
    {
    }
}
