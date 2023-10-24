<?php

namespace App\Abstracts;

use App\Events\Export\HeadingsPreparing;
use App\Events\Export\RowsPreparing;
use App\Notifications\Common\ExportFailed;
use App\Utilities\Date;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

abstract class Export implements FromCollection, HasLocalePreference, ShouldAutoSize, ShouldQueue, WithHeadings, WithMapping, WithTitle, WithStrictNullComparison, WithEvents
{
    use Exportable;

    public $ids;

    public $fields;

    public $user;

    public $row_count; //number of rows that will have the dropdown

    public $column_count; //number of columns to be auto sized

    public $column_validations; //selects should have column_name and options

    public function __construct($ids = null)
    {
        $this->ids = $ids;
        $this->fields = $this->fields();
        $this->column_validations = $this->columnValidations();
        $this->user = user();
        $this->row_count = 200;
        $this->column_count = 20;
    }

    public function title(): string
    {
        return Str::snake((new \ReflectionClass($this))->getShortName());
    }

    public function fields(): array
    {
        return [];
    }

    public function map($model): array
    {
        $map = [];

        $date_fields = ['paid_at', 'invoiced_at', 'billed_at', 'due_at', 'issued_at', 'transferred_at'];

        $evil_chars = ['=', '+', '-', '@'];

        foreach ($this->fields as $field) {
            $value = $model->$field;

            // created_by is equal to the owner id. Therefore, the value in export is owner email.
            if ($field == 'created_by') {
                $value = $model->owner->email ?? null;
            }

            if (in_array($field, $date_fields)) {
                $value = ExcelDate::PHPToExcel(Date::parse($value)->format('Y-m-d'));
            }

            // Prevent CSV injection https://security.stackexchange.com/a/190848
            if (Str::startsWith($value, $evil_chars)) {
                $value = "'" . $value;
            }

            $map[] = $value;
        }

        return $map;
    }

    public function headings(): array
    {
        event(new HeadingsPreparing($this));

        return $this->fields;
    }

    public function prepareRows($rows)
    {
        event(new RowsPreparing($this, $rows));

        return $rows;
    }

    public function preferredLocale()
    {
        return $this->user->locale;
    }

    public function failed(\Throwable $exception): void
    {
        $this->user->notify(new ExportFailed($exception->getMessage()));
    }

    public function columnValidations(): array
    {
        return [];
    }

    public function afterSheet($event)
    {
        if ($this->column_validations) {
            foreach ($this->column_validations as $column_validation){
                $drop_column = $column_validation['columns_name'];

                // set dropdown list for first data row
                $validation = $event->sheet->getCell("{$drop_column}2")->getDataValidation();

                $validation->setType(! empty($column_validation['type']) ? $column_validation['type'] : DataValidation::TYPE_LIST);

                if (empty($column_validation['hide_prompt'])) {
                    $validation->setAllowBlank(! empty($column_validation['allow_blank']) ? $column_validation['allow_blank'] : false);
                    $validation->setShowInputMessage(! empty($column_validation['show_input_message']) ? $column_validation['show_input_message'] : true);
                    $validation->setPromptTitle(! empty($column_validation['prompt_title']) ? $column_validation['prompt_title'] : 'Pick from list');
                    $validation->setPrompt(! empty($column_validation['prompt']) ? $column_validation['prompt'] : 'Please pick a value from the drop-down list.');
                }

                if (empty($column_validation['hide_error'])) {
                    $validation->setErrorStyle(! empty($column_validation['error_style']) ? $column_validation['error_style'] : DataValidation::STYLE_INFORMATION);
                    $validation->setShowErrorMessage(! empty($column_validation['show_error_message']) ? $column_validation['show_error_message'] : true);
                    $validation->setErrorTitle(! empty($column_validation['error_title']) ? $column_validation['error_title'] : 'Input error');
                    $validation->setError(! empty($column_validation['error']) ? $column_validation['error'] : 'Value is not in list.');
                }

                if (! empty($column_validation['options'])) {
                    $validation->setFormula1(sprintf('"%s"', implode(',', $column_validation['options'])));
                    $validation->setShowDropDown(! empty($column_validation['show_dropdown']) ? $column_validation['show_dropdown'] : true);
                }

                // clone validation to remaining rows
                for ($i = 3; $i <= $this->row_count; $i++) {
                    $event->sheet->getCell("{$drop_column}{$i}")->setDataValidation(clone $validation);
                }
                
                // set columns to autosize
                for ($i = 1; $i <=  $this->column_count; $i++) {
                    $column = Coordinate::stringFromColumnIndex($i);
                    $event->sheet->getColumnDimension($column)->setAutoSize(true);
                }
            }
        }
    }

    public function registerEvents(): array
    {
        return [		   
            AfterSheet::class => function(AfterSheet $event) {
                $this->afterSheet($event);
            },
        ];
    }
}
