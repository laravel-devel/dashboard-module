<?php

namespace Modules\DevelDashboard\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportDataRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'items' => 'required|array|min:1',
            'export_fields' => 'required|array|min:1',
            'export_format' => 'required|string|in:csv,txt',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
