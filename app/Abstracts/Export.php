<?php

namespace App\Abstracts;

use App\Abstracts\Http\FormRequest;
use App\Events\Export\HeadingsPreparing;
use App\Events\Export\RowsPreparing;
use App\Notifications\Common\ExportFailed;
use App\Utilities\Date;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

abstract class Export implements FromCollection, HasLocalePreference, ShouldAutoSize, ShouldQueue, WithHeadings, WithMapping, WithTitle, WithStrictNullComparison
{
    use Exportable;

    public $ids;

    public $fields;

    public $user;

    public $request_class = null;

    public $required_values = [];

    public function __construct($ids = null)
    {
        $this->ids = $ids;
        $this->fields = $this->fields();
        $this->user = user();
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
        $validator = $this->withValidator($model);

        if ($validator instanceof ValidationException) {
            return [];
        }

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

    public function withValidator($model)
    {
        $condition = class_exists($this->request_class)
                    ? ! ($request = new $this->request_class) instanceof FormRequest
                    : true;

        if (! $condition) {
            $rules = $this->prepareRules($request->rules());

            try {
                Validator::make($model->toArray(), $rules)->validate();
            } catch (ValidationException $e) {
                return $e;
            }
        }

        if (is_array($this->required_values) && ! empty($this->required_values)) {
            $rules = array_map(function ($value) {
                return 'required';
            }, array_flip($this->required_values));
            
            try {
                Validator::make($model->toArray(), $rules)->validate();
            } catch (ValidationException $e) {
                return $e;
            }
        }

        return true;
    }

    /**
     * You can override this method to add custom rules for each row.
     */
    public function prepareRules(array $rules): array
    {
        return $rules;
    }
}
