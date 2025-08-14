@extends('panel.layout.app')
@section('title', __('Create External Chatbot'))
@section('content')
<div class="mx-auto max-w-[900px] px-6">
    <x-card class="space-y-4">
        <form method="post" action="{{ route('externalbots.store') }}">
            @csrf
            <x-forms.input label="{{ __('Title') }}" name="title" />
            <x-forms.input label="{{ __('Role') }}" name="role" value="assistant" />
            <x-button type="submit">{{ __('Create') }}</x-button>
        </form>
    </x-card>
</div>
@endsection


