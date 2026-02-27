<?php

namespace App\Events\Setting;

use Illuminate\Queue\SerializesModels;

class CategoryTypesCollecting
{
    use SerializesModels;

    public array $types;

    /**
     * Create a new event instance.
     *
     * @param array $types
     */
    public function __construct(array &$types)
    {
        $this->types = &$types;
    }
}
