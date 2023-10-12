<?php

namespace App\Exports\Common\Sheets;

use App\Abstracts\Export;
use App\Models\Common\Item as Model;

class Items extends Export
{
    public function collection()
    {
        $model = Model::with('category')->collectForExport($this->ids);

        if (empty($this->ids)) {
            $this->ids = $model->pluck('id')->toArray();
        }

        return $model;
    }

    public function map($model): array
    {
        $model->category_name = $model->category->name;

        return parent::map($model);
    }

    public function fields(): array
    {
        return [
            'name',
            'type',
            'description',
            'sale_price',
            'purchase_price',
            'category_name',
            'enabled',
        ];
    }
}
