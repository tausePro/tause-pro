@extends('panel.layout.app')
@section('title', __('Embed External Chatbot'))
@section('content')
@php
    $host = request()->getSchemeAndHttpHost();
    $scriptUrl = $host . '/embed/' . $widgetKey . '.js';
    $snippet = "<script src=\"{$scriptUrl}\" defer></script>";
@endphp
<div class="mx-auto max-w-[900px] px-6">
    <x-card class="space-y-4">
        <h3 class="text-lg font-semibold">{{ __('Embed Code') }}</h3>
        <p>{{ __('Copy and paste this script before </body> on your website:') }}</p>
        <textarea class="w-full rounded border border-input-border p-3 text-sm" rows="3" readonly onclick="this.select();">{{ $snippet }}</textarea>
        <p class="text-sm opacity-70">{{ __('Preview (live)') }}</p>
        <div class="rounded border border-input-border p-3">
            <small class="opacity-70">{{ $scriptUrl }}</small>
        </div>
    </x-card>
</div>
<script>
// Cargar el widget real como vista previa en esta misma página
(function(){
  var d=document; if(d.getElementById('mx-bot')) return;
  var s=d.createElement('script'); s.src='{{ $scriptUrl }}'; s.defer=true; d.body.appendChild(s);
})();
</script>
@endsection


