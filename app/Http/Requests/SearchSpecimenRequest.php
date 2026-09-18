<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchSpecimenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $input = array_map(
            fn (mixed $value): mixed => is_string($value) ? trim($value) : $value,
            $this->all()
        );

        foreach ([
            'scientific_name', 'family', 'genus', 'specific_epithet', 'infraspecific_epithet',
            'vernacular_name', 'type_status', 'continent', 'country', 'state', 'county',
            'locality', 'water_body', 'verbatim_depth', 'event_year', 'event_month', 'event_day',
            'verbatim_event_date', 'sampling_protocol', 'preparation', 'identified_by',
        ] as $key) {
            if (isset($input[$key]) && is_string($input[$key])) {
                $input[$key] = [$input[$key]];
            }
        }

        $this->merge($input);
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'quick_search' => ['nullable', 'string', 'max:120'],
            'catalog_number' => ['nullable', 'string', 'max:100'],
            'field_number' => ['nullable', 'string', 'max:120'],
            'year_from' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'year_to' => ['nullable', 'integer', 'min:1', 'max:9999', 'gte:year_from'],
            'latitude_min' => ['nullable', 'numeric', 'between:-90,90'],
            'latitude_max' => ['nullable', 'numeric', 'between:-90,90', 'gte:latitude_min'],
            'longitude_min' => ['nullable', 'numeric', 'between:-180,180'],
            'longitude_max' => ['nullable', 'numeric', 'between:-180,180', 'gte:longitude_min'],
            'sort' => ['nullable', 'in:catalog_number,event_year,family,genus,specific_epithet'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'in:25,50,100'],
            'scientific_name' => ['nullable', 'array'], 'scientific_name.*' => ['string', 'max:255'],
            'family' => ['nullable', 'array'], 'family.*' => ['string', 'max:255'],
            'genus' => ['nullable', 'array'], 'genus.*' => ['string', 'max:255'],
            'specific_epithet' => ['nullable', 'array'], 'specific_epithet.*' => ['string', 'max:255'],
            'infraspecific_epithet' => ['nullable', 'array'], 'infraspecific_epithet.*' => ['string', 'max:255'],
            'vernacular_name' => ['nullable', 'array'], 'vernacular_name.*' => ['string', 'max:255'],
            'type_status' => ['nullable', 'array'], 'type_status.*' => ['string', 'max:120'],
            'continent' => ['nullable', 'array'], 'continent.*' => ['string', 'max:255'],
            'country' => ['nullable', 'array'], 'country.*' => ['string', 'max:255'],
            'state' => ['nullable', 'array'], 'state.*' => ['string', 'max:255'],
            'county' => ['nullable', 'array'], 'county.*' => ['string', 'max:255'],
            'locality' => ['nullable', 'array'], 'locality.*' => ['string', 'max:255'],
            'water_body' => ['nullable', 'array'], 'water_body.*' => ['string', 'max:255'],
            'verbatim_depth' => ['nullable', 'array'], 'verbatim_depth.*' => ['string', 'max:255'],
            'event_year' => ['nullable', 'array'], 'event_year.*' => ['integer', 'min:1', 'max:9999'],
            'event_month' => ['nullable', 'array'], 'event_month.*' => ['integer', 'between:1,12'],
            'event_day' => ['nullable', 'array'], 'event_day.*' => ['integer', 'between:1,31'],
            'verbatim_event_date' => ['nullable', 'array'], 'verbatim_event_date.*' => ['string', 'max:255'],
            'sampling_protocol' => ['nullable', 'array'], 'sampling_protocol.*' => ['string', 'max:255'],
            'preparation' => ['nullable', 'array'], 'preparation.*' => ['string', 'max:255'],
            'identified_by' => ['nullable', 'array'], 'identified_by.*' => ['string', 'max:255'],
        ];
    }
}
