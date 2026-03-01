<?php

namespace App\Traits;

use App\Events\Setting\CategoryTabsCollecting;
use App\Events\Setting\CategoryTypesCollecting;
use App\Models\Setting\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

trait Categories
{
    public function getCategoryTypes(bool $translate = true, bool $group = false): array
    {
        $types = [];
        $configs = config('type.category');

        foreach ($configs as $type => $attr) {
            $plural_type = Str::plural($type);

            $name = $attr['translation']['prefix'] . '.' . $plural_type;

            if (!empty($attr['alias'])) {
                $name = $attr['alias'] . '::' . $name;
            }

            if ($group) {
                $group_key = $attr['tab'] ?? $type;
                $types[$group_key][$type] = $translate ? trans_choice($name, 1) : $name;
            } else {
                $types[$type] = $translate ? trans_choice($name, 1) : $name;
            }
        }

        return $types;
    }

    public function getCategoryTabs(): array
    {
        $tabs = [];
        $configs = config('type.category');

        // Only get core categories (without alias)
        foreach ($configs as $type => $attr) {
            // Skip module categories
            if (!empty($attr['alias'])) {
                continue;
            }

            $plural_type = Str::plural($type);

            $name = $attr['translation']['prefix'] . '.' . $plural_type;

            $tabs[] = [
                'key' => $type,
                'name' => trans_choice($name, 1),
                'tab' => $attr['tab'] ?? $type,
            ];
        }

        event(new CategoryTabsCollecting($tabs));

        return $tabs;
    }

    public function getCategoryWithoutChildren(int $id): mixed
    {
        return Category::getWithoutChildren()->find($id);
    }

    public function getTransferCategoryId(): mixed
    {
        // 1 hour set cache for same query
        return Cache::remember('transferCategoryId', 60, function () {
            return Category::other()->pluck('id')->first();
        });
    }

    public function isTransferCategory(): bool
    {
        $id = $this->id ?? $this->category->id ?? $this->model->id ?? 0;

        return $id == $this->getTransferCategoryId();
    }

    public function getChildrenCategoryIds($category)
    {
        $ids = [];

        foreach ($category->sub_categories as $sub_category) {
            $ids[] = $sub_category->id;

            if ($sub_category->sub_categories) {
                $ids = array_merge($ids, $this->getChildrenCategoryIds($sub_category));
            }
        }

        return $ids;
    }

    /**
     * Finds existing maximum code and increase it
     *
     * @return mixed
     */
    public function getNextCategoryCode()
    {
        return Category::isNotSubCategory()->get(['code'])->reject(function ($category) {
            return !preg_match('/^[0-9]*$/', $category->code);
        })->max('code') + 1;
    }
}
