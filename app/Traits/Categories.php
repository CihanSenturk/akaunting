<?php

namespace App\Traits;

use App\Events\Setting\CategoryTabsCollecting;
use App\Events\Setting\CategoryTypesCollecting;
use App\Models\Setting\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

trait Categories
{
    public function isIncomeCategory(): bool
    {
        $type = $this->type ?? $this->category->type ?? $this->model->type ?? Category::INCOME_TYPE;

        return in_array($type, $this->getIncomeCategoryTypes());
    }

    public function isExpenseCategory(): bool
    {
        $type = $this->type ?? $this->category->type ?? $this->model->type ?? Category::EXPENSE_TYPE;

        return in_array($type, $this->getExpenseCategoryTypes());
    }

    public function isItemCategory(): bool
    {
        $type = $this->type ?? $this->category->type ?? $this->model->type ?? Category::ITEM_TYPE;

        return in_array($type, $this->getItemCategoryTypes());
    }

    public function isOtherCategory(): bool
    {
        $type = $this->type ?? $this->category->type ?? $this->model->type ?? Category::OTHER_TYPE;

        return in_array($type, $this->getOtherCategoryTypes());
    }

    public function getIncomeCategoryTypes(string $return = 'array'): string|array
    {
        return $this->getCategoryTypesByIndex(Category::INCOME_TYPE, $return);
    }

    public function getExpenseCategoryTypes(string $return = 'array'): string|array
    {
        return $this->getCategoryTypesByIndex(Category::EXPENSE_TYPE, $return);
    }

    public function getItemCategoryTypes(string $return = 'array'): string|array
    {
        return $this->getCategoryTypesByIndex(Category::ITEM_TYPE, $return);
    }

    public function getOtherCategoryTypes(string $return = 'array'): string|array
    {
        return $this->getCategoryTypesByIndex(Category::OTHER_TYPE, $return);
    }

    public function getCategoryTypesByIndex(string $index, string $return = 'array'): string|array
    {
        $types = (string) setting('category.type.' . $index);

        return ($return == 'array') ? explode(',', $types) : $types;
    }

    public function addIncomeCategoryType(string $new_type): void
    {
        $this->addCategoryType($new_type, Category::INCOME_TYPE);
    }

    public function addExpenseCategoryType(string $new_type): void
    {
        $this->addCategoryType($new_type, Category::EXPENSE_TYPE);
    }

    public function addItemCategoryType(string $new_type): void
    {
        $this->addCategoryType($new_type, Category::ITEM_TYPE);
    }

    public function addOtherCategoryType(string $new_type): void
    {
        $this->addCategoryType($new_type, Category::OTHER_TYPE);
    }

    public function addCategoryType(string $new_type, string $index): void
    {
        $types = explode(',', setting('category.type.' . $index));

        if (in_array($new_type, $types)) {
            return;
        }

        $types[] = $new_type;

        setting([
            'category.type.' . $index => implode(',', $types),
        ])->save();
    }

    public function getCategoryTypes(bool $translate = true, bool $group = false): array
    {
        $types = [];
        $configs = config('type.category');

        foreach ($configs as $type => $attr) {
            $name = null;

            if (isset($attr['translation']['type_title'])) {
                $name = $attr['translation']['type_title'];
            } else {
                $plural_type = Str::plural($type);
                $name = $attr['translation']['prefix'] . '.' . $plural_type;

                if (!empty($attr['alias'])) {
                    $name = $attr['alias'] . '::' . $name;
                }

                $name = $translate ? trans_choice($name, 1) : $name;
            }

            if ($group) {
                $group_key = $attr['group'] ?? $type;
                $types[$group_key][$type] = $name;
            } else {
                $types[$type] = $name;
            }
        }

        return $types;
    }

    public function getCategoryTabs(): array
    {
        $tabs = [];
        $added_tabs = [];
        $configs = config('type.category');

        foreach ($configs as $type => $attr) {
            $tab = $attr['group'] ?? $type;

            if (in_array($tab, $added_tabs)) {
                continue;
            }

            $name = null;

            if (isset($attr['translation']['group_title'])) {
                $name = $attr['translation']['group_title'];
            } else {
                $plural_type = Str::plural($tab);

                $trans = $attr['translation']['prefix'] . '.' . $plural_type;

                if (!empty($attr['alias'])) {
                    $trans = $attr['alias'] . '::' . $name;
                }

                $name = trans_choice($trans, 1);
            }

            $tabs[] = [
                'key' => $tab,
                'name' => $name,
                'tab' => $tab,
            ];

            $added_tabs[] = $tab;
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
        return Cache::remember('transferCategoryId.' . company_id(), 60, function () {
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
