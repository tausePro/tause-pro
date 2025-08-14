<?php

namespace App\Http\Requests\User\Brand;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class BrandRequest extends FormRequest
{
    public function rules(): array
    {
        // Soporta dos flujos: guardado normal y autocompletado (prefill=1)
        if ($this->boolean('prefill')) {
            return [
                'user_id' => 'required|integer',
                'website' => 'required|url',
            ];
        }

        return [
            'user_id'         => 'required|integer',
            'name'            => 'required|string|max:255',
            'website'         => 'sometimes|nullable|url',
            'tagline'         => 'sometimes|nullable|string',
            'description'     => 'required|string',
            'brand_color'     => 'sometimes|nullable|string',
            'industry'        => 'sometimes|nullable|string',
            'tone_of_voice'   => 'sometimes|nullable|string',
            'target_audience' => 'sometimes|nullable|string',

            'inputNames'    => 'sometimes',
            'inputFeatures' => 'sometimes',
            'inputTypes'    => 'sometimes',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Build optional deep brand context without requiring DB schema changes.
        $baseDescription = (string) $this->c_description;
        $contextParts = [];
        if (! empty($this->uvps))             { $contextParts[] = 'UVPs: ' . trim($this->uvps); }
        if (! empty($this->objections))       { $contextParts[] = 'Frequent Objections: ' . trim($this->objections); }
        if (! empty($this->answers))          { $contextParts[] = 'Standard Answers: ' . trim($this->answers); }
        if (! empty($this->competitors))      { $contextParts[] = 'Competitors: ' . trim($this->competitors); }
        if (! empty($this->content_pillars))  { $contextParts[] = 'Content Pillars: ' . trim($this->content_pillars); }
        if (! empty($this->preferred_ctas))   { $contextParts[] = 'Preferred CTAs: ' . trim($this->preferred_ctas); }
        if (! empty($this->priority_channels)) { $contextParts[] = 'Priority Channels: ' . trim($this->priority_channels); }
        if (! empty($this->tone_restrictions)) { $contextParts[] = 'Tone Restrictions: ' . trim($this->tone_restrictions); }

        $contextBlock = '';
        if (! empty($contextParts)) {
            $contextBlock = "\n\n### Brand Context (Auto) ###\n" . implode("\n", $contextParts);
        }

        $this->merge([
            'user_id'	        => Auth::id(),
            'name'            => $this->c_name,
            'website'         => $this->c_website,
            'tagline'         => $this->c_tagline,
            'description'     => $baseDescription . $contextBlock,
            'brand_color'     => $this->c_color,
            'industry'        => $this->c_industry,
            'tone_of_voice'   => $this->tone_of_voice,
            'target_audience' => $this->target_audience,
            'inputNames'      => explode(',', $this->input_name),
            'inputFeatures'   => explode(',', $this->input_features),
            'inputTypes'      => explode(',', $this->input_type),
        ]);
    }
}
