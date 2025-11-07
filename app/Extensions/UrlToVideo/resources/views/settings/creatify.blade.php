@php use App\Domains\Entity\Enums\EntityEnum; @endphp
@extends('panel.layout.settings', ['layout' => 'wide'])
@section('title', __('Creatify Setting'))
@section('titlebar_actions', '')
@section('titlebar_subtitle', __('This API Configuration is used for AI Influencer'))

@section('additional_css')
    <link
        href="{{ custom_theme_url('/assets/libs/select2/select2.min.css') }}"
        rel="stylesheet"
    />
@endsection

@section('settings')
    <form
        class="grid grid-cols-1 gap-5"
        id="creatify-setting-form"
        method="POST"
        action="{{ route('dashboard.admin.settings.creatify') }}"
    >
        @csrf

        <!-- API ID -->
        <x-forms.input
            id="creatify_api_id"
            size="lg"
            type="text"
            name="creatify_api_id"
            label="{{ __('Creatify API ID') }}"
            placeholder="{{ __('Enter your Creatify API ID') }}"
            tooltip="{{ __('Please enter your Creatify API ID') }}"
            value="{{ setting('creatify_api_id') }}"
            required
        />

        <!-- API Key Field -->
        <x-forms.input
            id="creatify_api_key"
            size="lg"
            type="text"
            name="creatify_api_key"
            label="{{ __('Creatify API Key') }}"
            placeholder="{{ __('Enter your Creatify API key') }}"
            tooltip="{{ __('Please enter your Creatify API key') }}"
            value="{{ setting('creatify_api_key') }}"
            required
        />

        <!-- Submit Button -->
        <x-button
            class="w-full"
            id="creatify_save_button"
            size="lg"
            type="submit"
        >
            {{ __('Save') }}
        </x-button>
    </form>
@endsection
@push('script')
    <script src="{{ custom_theme_url('/assets/js/panel/settings.js') }}"></script>
    <script src="{{ custom_theme_url('/assets/libs/select2/select2.min.js') }}"></script>
@endpush
