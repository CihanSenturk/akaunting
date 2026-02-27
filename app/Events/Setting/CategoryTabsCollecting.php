<?php

namespace App\Events\Setting;

use Illuminate\Queue\SerializesModels;

class CategoryTabsCollecting
{
    use SerializesModels;

    public array $tabs;

    /**
     * Create a new event instance.
     *
     * @param array $tabs
     */
    public function __construct(array &$tabs)
    {
        $this->tabs = &$tabs;
    }
}
