<?php

namespace App\Jobs\Setting;

use App\Abstracts\Job;
use App\Models\Setting\Category;
use App\Interfaces\Job\ShouldDelete;
use App\Events\Setting\CategoryDeleted;
use App\Events\Setting\CategoryDeleting;
use App\Exceptions\Settings\LastCategoryDelete;

class DeleteCategory extends Job implements ShouldDelete
{
    public function handle(): bool
    {   
        foreach ($this->model->sub_categories as $sub_category) {
            try {
                $this->ajaxDispatch(new DeleteCategory($sub_category));
            } catch (\Exception $e) {
                flash($e->getMessage())->error()->important();
            }
        }

        $this->model->refresh();

        $this->authorize();

        event(new CategoryDeleting($this->model));

        \DB::transaction(function () {
            $this->model->delete();
        });

        event(new CategoryDeleted($this->model));

        return true;
    }

    /**
     * Determine if this action is applicable.
     */
    public function authorize(): void
    {
        // Can not delete transfer category
        if ($this->model->isTransferCategory()) {
            $message = trans('messages.error.transfer_category', ['type' => $this->model->name]);

            throw new \Exception($message);
        }

        // Can not delete the last category by type
        if (Category::where('type', $this->model->type)->count() == 1 && $this->model->parent_id === null) {
            $message = trans('messages.error.last_category', ['type' => strtolower(trans_choice('general.' . $this->model->type . 's', 1))]);

            throw new LastCategoryDelete($message);
        }

        if ($relationships = $this->getRelationships()) {
            $message = trans('messages.warning.deleted', ['name' => $this->model->name, 'text' => implode(', ', $relationships)]);

            throw new \Exception($message);
        }

        if ($this->model->sub_categories->count() >= 1) {
            $relationships = [];

            foreach ($this->model->sub_categories as $sub_category) {
                $relationships[] = $this->getRelationships($sub_category);
            }

            $text = null;

            $relationships = array_map('unserialize', array_unique(array_map('serialize', $relationships)));

            foreach ($relationships as $relationship) {
                $text = ($text ? $text . ', ' : $text) . implode(', ', $relationship);
            }

            $message = trans('messages.warning.deleted', ['name' => $this->model->name, 'text' => $text]);


            throw new \Exception($message);
        }
    }

    public function getRelationships($model = null): array
    {
        if (! $model) {
            $model = $this->model;
        } 

        $rels = [
            'items' => 'items',
            'invoices' => 'invoices',
            'bills' => 'bills',
            'transactions' => 'transactions',
        ];

        $relationships = $this->countRelationships($model, $rels);

        foreach ($model->sub_categories as $sub_category) {
            $this->countRelationships($sub_category, $rels);
        }

        if ($model->id == setting('default.income_category')) {
            $relationships[] = strtolower(trans_choice('general.incomes', 1));
        }

        if ($model->id == setting('default.expense_category')) {
            $relationships[] = strtolower(trans_choice('general.expenses', 1));
        }

        return $relationships;
    }
}
