<?php

namespace App\Events\Setting;

use App\Abstracts\Event;

class CategoryFieldHided extends Event
{
    public $hideCategory;

    /**
     * Hide category field.
     *
     * @param $hideCategory
     */
    public function __construct($hideCategory)
    {
        $this->hideCategory = $hideCategory;
    }
}