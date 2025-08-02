<?php

declare(strict_types=1);

namespace Awalhadi\Addressable\Events;

use Awalhadi\Addressable\Models\Address;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AddressCreated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Address $address
    ) {}
}
