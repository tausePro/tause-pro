@extends('panel.layout.app')
@section('title', __('External Chatbots'))
@section('content')
<div class="mx-auto max-w-[1200px] px-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">{{ __('External Chatbots') }}</h1>
        <x-button href="{{ route('externalbots.create') }}">{{ __('Add New') }}</x-button>
    </div>
    <x-card>
        <table class="w-full text-sm">
            <thead><tr><th class="text-left p-2">ID</th><th class="text-left p-2">Title</th><th class="text-left p-2">Actions</th></tr></thead>
            <tbody>
            @forelse($bots as $b)
                <tr>
                    <td class="p-2">{{ $b->id }}</td>
                    <td class="p-2">{{ $b->title }}</td>
                    <td class="p-2"><a class="text-primary" href="{{ route('externalbots.embed', $b->id) }}">{{ __('Embed') }}</a></td>
                </tr>
            @empty
                <tr><td class="p-4" colspan="3">{{ __('No bots yet') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </x-card>
</div>
@endsection


