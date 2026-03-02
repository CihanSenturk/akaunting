<?php

namespace App\View\Components\Form\Group;

use App\Abstracts\View\Components\Form;
use App\Models\Setting\Category as Model;
use App\Traits\Categories;
use App\Traits\Modules;

class Category extends Form
{
    use Categories, Modules;

    public $type = Model::INCOME_TYPE;

    public $path;

    public $remoteAction;

    public $categories;

    public $has_double_entry = false;

    /** @var bool */
    public $group;

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        if (empty($this->name)) {
            $this->name = 'category_id';
        }

        $this->group = true;

        $this->path = route('modals.categories.create', ['type' => $this->type]);
        $this->remoteAction = route('categories.index', ['search' => 'type:' . $this->type . ' enabled:1']);

        $de_categories = [];

        $typeGroups = collect(config('type.category', []))
            ->keys()
            ->mapWithKeys(fn($type) => [
                $type => trans_choice('double-entry::category_types.' . \Illuminate\Support\Str::plural($type), 1)
            ]);

        $types = $this->getIncomeCategoryTypes();

        Model::whereNotNull('code')
            ->enabled()
            ->type($typeGroups->keys()->toArray())
            ->orderBy('code')
            ->get()
            ->each(function ($category) use (&$de_categories, $typeGroups) {
                $group = $typeGroups[$category->type] ?? trans_choice('general.others', 1);

                $category->title = ($category->code ? $category->code . ' - ' : '') . $category->name;

                $de_categories[$group][$category->id] = $category;
            });

        ksort($de_categories);

        $this->categories = $de_categories;

        //$this->has_double_entry = $this->moduleIsEnabled('double-entry');

        $model = $this->getParentData('model');

        $category_id = old('category.id', old('category_id', null));

        if (! empty($category_id)) {
            $this->selected = $category_id;

            $has_category = $this->categories->search(function ($category, int $key) use ($category_id) {
                return $category->id === $category_id;
            });

            if ($has_category === false) {
                $category = Model::find($category_id);

                $this->categories->push($category);
            }
        }

        if (! empty($model) && ! empty($model->category_id)) {
            $this->selected = $model->category_id;

            $selected_category = $model->category;
        }

        if ($this->selected === null && in_array($this->type, [Model::INCOME_TYPE, Model::EXPENSE_TYPE])) {
            $this->selected = setting('default.' . $this->type . '_category');

            $selected_category = Model::find($this->selected);
        }

        if (! empty($selected_category)) {
            $selected_category_id = $selected_category->id;

            $has_selected_category = $this->categories->search(function ($category, int $key) use ($selected_category_id) {
                return $category->id === $selected_category_id;
            });

            if ($has_selected_category === false) {
                $this->categories->push($selected_category);
            }
        }

        return view('components.form.group.category');
    }
}
