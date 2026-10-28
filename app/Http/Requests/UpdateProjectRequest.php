<?php

namespace App\Http\Requests;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use Hekmatinasser\Verta\Verta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $startDate = $this->input('start_date');
        $deadline = $this->input('deadline');

        $this->merge([
            'start_date' => $this->convertJalaliToGregorian($startDate),
            'deadline' => $this->convertJalaliToGregorian($deadline),
        ]);
    }

    public function rules(): array
    {
        $projectId = $this->route('project')?->id ?? $this->route('project');

        return [
            'organization_node_id' => ['required', 'integer', 'exists:organization_nodes,id'],
            'parent_project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'project_key' => ['required', 'string', 'max:100', Rule::unique('projects', 'project_key')->ignore($projectId)],
            'description' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'deadline' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', new Enum(ProjectStatus::class)],
            'priority' => ['required', new Enum(ProjectPriority::class)],
        ];
    }

    private function convertJalaliToGregorian(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            $cleanDate = $this->convertPersianDigits($date);

            // 1. If already in Gregorian YYYY-MM-DD or YYYY/MM/DD (starts with 19xx or 20xx)
            if (preg_match('/^(19|20)\d{2}[\/\-]\d{1,2}[\/\-]\d{1,2}$/', $cleanDate)) {
                return Carbon::parse(str_replace('/', '-', $cleanDate))->format('Y-m-d');
            }

            // 2. If in valid Jalali format YYYY/MM/DD (starts with 13xx or 14xx)
            if (preg_match('/^(13|14)\d{2}[\/\-]\d{1,2}[\/\-]\d{1,2}$/', $cleanDate)) {
                $verta = Verta::parse(str_replace('-', '/', $cleanDate));

                return $verta->datetime()->format('Y-m-d');
            }

            return Carbon::parse($cleanDate)->format('Y-m-d');
        } catch (\Throwable $e) {
            return $date;
        }
    }

    private function convertPersianDigits(string $string): string
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $string = str_replace($persian, $english, trim($string));

        return str_replace($arabic, $english, $string);
    }
}
