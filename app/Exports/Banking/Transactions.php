<?php

namespace App\Exports\Banking;

use App\Abstracts\Export;
use App\Utilities\Modules;
use App\Models\Banking\Account;
use App\Models\Setting\Category;
use App\Models\Setting\Currency;
use App\Models\Banking\Transaction as Model;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class Transactions extends Export implements WithColumnFormatting
{
    public function collection()
    {
        return Model::with('account', 'category', 'contact', 'document')->collectForExport($this->ids, ['paid_at' => 'desc']);
    }

    public function map($model): array
    {
        $model->account_name = $model->account->name;
        $model->contact_email = $model->contact->email;
        $model->category_name = $model->category->name;
        $model->invoice_bill_number = $model->document->document_number ?? 0;
        $model->parent_number = Model::isRecurring()->find($model->parent_id)?->number;

        return parent::map($model);
    }

    public function fields(): array
    {
        return [
            'type', 
            'number',
            'paid_at',
            'amount',
            'currency_code',
            'currency_rate',
            'account_name',
            'invoice_bill_number',
            'contact_email',
            'category_name',
            'description',
            'payment_method',
            'reference',
            'reconciled',
            'parent_number',
        ];
    }

    public function columnValidations(): array
    {
        return [
            [
                'columns_name' => 'A',
                'options' => [
                    Model::INCOME_TYPE, Model::INCOME_TRANSFER_TYPE, Model::INCOME_SPLIT_TYPE, Model::INCOME_RECURRING_TYPE,
                    Model::EXPENSE_TYPE, Model::EXPENSE_TRANSFER_TYPE, Model::EXPENSE_SPLIT_TYPE, Model::EXPENSE_RECURRING_TYPE,
                ]
            ],
            [
                'columns_name' => 'C',
                'type' => DataValidation::TYPE_NONE,
                'prompt_title' => 'Format Date: yyyy-mm-dd',
                'prompt' => 'Please enter the appropriate date format. Ex: 2023-10-29',
                'hide_error_notification' => true,
            ],
            [
                'columns_name' => 'E',
                'options' => Currency::pluck('code')->toArray(),
            ],
            [
                'columns_name' => 'G',
                'options' => Account::pluck('name')->toArray(),
            ],
            [
                'columns_name' => 'J',
                'options' => Category::pluck('name')->toArray(),
            ],
            [
                'columns_name' => 'L',
                'options' => Modules::getPaymentMethods(),
            ],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_DATE_YYYYMMDD,
        ];
    }
}
