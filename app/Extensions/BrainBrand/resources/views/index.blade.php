@extends('panel.layout.app', ['disable_tblr' => true])

@section('title', __('BrainBrand • Central intelligence'))

@section('content')
@php
    $brainBrandCollection = collect($brainBrands ?? []);

    $totalEmbeddings = $brainBrandCollection->sum(function ($brand) {
        if (! is_null(data_get($brand, 'embeddings_count'))) {
            return (int) data_get($brand, 'embeddings_count');
        }

        $embeddings = data_get($brand, 'embeddings');
        if ($embeddings instanceof \Countable) {
            return $embeddings->count();
        }

        if (is_array($embeddings)) {
            return count($embeddings);
        }

        return method_exists($brand, 'embeddings') ? $brand->embeddings()->count() : 0;
    });

    $connectedChatbots = $brainBrandCollection->sum(function ($brand) {
        return (int) data_get($brand, 'chatbots_count', 0);
    });

    $autoDistributionEnabled = $brainBrandCollection->contains(fn ($brand) => (bool) data_get($brand, 'auto_distribute', false));
@endphp

<div
    x-data="{
        showCreate: false,
        openCreate() { this.showCreate = true },
        closeCreate() { this.showCreate = false },
    }"
    class="py-10"
>
    <div class="container-fluid space-y-8">
        <div class="flex flex-col items-start justify-between gap-4 rounded-3xl bg-gradient-to-r from-primary/10 to-primary/5 p-8 shadow-sm ring-1 ring-primary/20 md:flex-row md:items-center">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-primary/80">{{ __('BrainBrand Command') }}</p>
                <h1 class="mt-2 text-2xl font-semibold text-heading-foreground md:text-3xl">
                    {{ __('Tu cerebro central para todos los agentes autónomos') }}
                </h1>
                <p class="mt-3 max-w-2xl text-sm text-muted-foreground">
                    {{ __('Entrena una vez y distribuye a chatbots, agentes y canales externos con un solo clic. BrainBrand mantiene la voz, tono y conocimiento sincronizado en toda la plataforma.') }}
                </p>
            </div>
        <div class="flex flex-wrap gap-3">
                <button
                    type="button"
                    class="btn btn-primary btn-lg"
                    @click="openCreate"
                >
                ✨ {{ __('Crear BrainBrand') }}
                        </button>
                <a
                    href="{{ route('dashboard.user.brain-brand.train', optional($brainBrandCollection->first())->id) }}"
                    class="btn btn-outline-primary btn-lg {{ $brainBrandCollection->isEmpty() ? 'pointer-events-none opacity-50' : '' }}"
                >
                🧪 {{ __('Panel de entrenamiento') }}
                </a>
            </div>
        </div>

        @if(isset($error))
            <x-alert variant="danger">
                <p class="font-semibold">{{ __('Configuración pendiente') }}</p>
                <p class="text-sm text-muted-foreground">{{ $error }}</p>
            </x-alert>
        @else
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach([
                    ['label' => __('BrainBrands activos'), 'value' => $brainBrandCollection->count(), 'hint' => '+1 este mes'],
                    ['label' => __('Embeddings globales'), 'value' => $totalEmbeddings, 'hint' => __('Actualizados')],
                    ['label' => __('Chatbots conectados'), 'value' => $connectedChatbots, 'hint' => __('Live')],
                    ['label' => __('Auto distribución'), 'value' => $autoDistributionEnabled ? __('Activa') : __('Manual'), 'hint' => $autoDistributionEnabled ? 'ON' : 'OFF'],
                ] as $stat)
                    <div class="rounded-2xl border border-border bg-white/80 p-5 shadow-sm ring-1 ring-black/5 dark:bg-background">
                        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-muted-foreground">{{ $stat['label'] }}</p>
                        <p class="mt-3 text-2xl font-semibold text-heading-foreground">{{ $stat['value'] }}</p>
                        <p class="text-xs text-muted-foreground">{{ $stat['hint'] }}</p>
                    </div>
                @endforeach
            </div>

            @if($brainBrandCollection->isEmpty())
                <div class="rounded-3xl border border-dashed border-border p-10 text-center">
                    <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-primary/10 text-primary text-3xl">
                        🧠
                </div>
                    <h2 class="text-2xl font-semibold text-heading-foreground">{{ __('Construye tu primer BrainBrand') }}</h2>
                    <p class="mt-2 text-sm text-muted-foreground max-w-2xl mx-auto">
                        {{ __('Centraliza todo el conocimiento de tu marca. Súbelo una vez, distribúyelo en todos los agentes y mantén coherencia absoluta.') }}
                    </p>
                    <div class="mt-6 flex flex-wrap justify-center gap-3">
                        <div class="rounded-2xl bg-white/60 p-4 text-left shadow-sm ring-1 ring-border max-w-xs">
                            <p class="font-semibold text-heading-foreground">{{ __('Entrenamiento inteligente') }}</p>
                            <p class="text-xs text-muted-foreground mt-1">{{ __('Crawling web, PDFs, texto estructurado y Q&A.') }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/60 p-4 text-left shadow-sm ring-1 ring-border max-w-xs">
                            <p class="font-semibold text-heading-foreground">{{ __('Distribución automática') }}</p>
                            <p class="text-xs text-muted-foreground mt-1">{{ __('Sincroniza en chatbots, LiveChat y agentes externos.') }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/60 p-4 text-left shadow-sm ring-1 ring-border max-w-xs">
                            <p class="font-semibold text-heading-foreground">{{ __('Control del tono') }}</p>
                            <p class="text-xs text-muted-foreground mt-1">{{ __('Define voz, personalidad y estilo por marca.') }}</p>
                                                        </div>
                                                    </div>
                    <button
                        class="btn btn-primary btn-lg mt-8"
                        @click="openCreate"
                    >
                        {{ __('Crear BrainBrand ahora') }}
                                                    </button>
                                                </div>
            @else
                <div class="grid gap-6 lg:grid-cols-2">
                    @foreach($brainBrandCollection as $brainBrand)
                        <div class="rounded-3xl border border-border bg-white/80 p-6 shadow-sm ring-1 ring-black/5 dark:bg-background">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.3em] text-muted-foreground">
                                        {{ __('BrainBrand') }}
                                    </p>
                                    <h3 class="text-xl font-semibold text-heading-foreground">{{ $brainBrand->name }}</h3>
                                    <p class="mt-2 text-sm text-muted-foreground">{{ $brainBrand->description ?? __('Sin descripción') }}</p>
                                </div>
                                @if($brainBrand->is_favorite)
                                    <span class="text-amber-400">★</span>
                                @endif
                            </div>

                            <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
                                <div class="rounded-2xl bg-muted/50 p-3">
                                    <dt class="text-muted-foreground">{{ __('Embeddings') }}</dt>
                                    <dd class="text-lg font-semibold text-heading-foreground">{{ $brainBrand->embeddings->count() }}</dd>
                                </div>
                                <div class="rounded-2xl bg-muted/50 p-3">
                                    <dt class="text-muted-foreground">{{ __('Tono') }}</dt>
                                    <dd class="text-lg font-semibold text-heading-foreground">
                                        {{ $brainBrand->tone ? ucfirst($brainBrand->tone) : __('No definido') }}
                                    </dd>
                                </div>
                            </dl>

                            <div class="mt-6 flex flex-wrap gap-2 text-xs">
                                @if($brainBrand->embeddings->whereNotNull('url')->count())
                                    <span class="rounded-full bg-primary/10 px-3 py-1 text-primary">{{ __('Sitios web') }}</span>
                                @endif
                                @if($brainBrand->embeddings->whereNotNull('file')->count())
                                    <span class="rounded-full bg-indigo-100 px-3 py-1 text-indigo-600">{{ __('Documentos') }}</span>
                                @endif
                                @if($brainBrand->embeddings->where('type', 'text')->count())
                                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-600">{{ __('Texto') }}</span>
                                @endif
                                @if($brainBrand->embeddings->where('type', 'qa')->count())
                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-amber-600">{{ __('Q&A') }}</span>
                                @endif
                            </div>

                            <div class="mt-8 flex flex-wrap gap-3">
                                <a
                                    href="{{ route('dashboard.user.brain-brand.train', $brainBrand) }}"
                                    class="btn btn-primary btn-sm"
                                >
                                    🚀 {{ __('Entrenar') }}
                                </a>
                                <form
                                    action="{{ route('dashboard.user.brain-brand.distribute', $brainBrand) }}"
                                    method="POST"
                                    class="{{ $brainBrand->auto_distribute ? 'pointer-events-none opacity-50' : '' }}"
                                >
                                    @csrf
                                    <button class="btn btn-outline-primary btn-sm" {{ $brainBrand->auto_distribute ? 'disabled' : '' }}>
                                        🔁 {{ __('Distribuir') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>

    <div
        x-show="showCreate"
        x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
        @click.self="closeCreate"
    >
        <div class="w-full max-w-3xl rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-border dark:bg-background">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-primary/70">{{ __('Nuevo BrainBrand') }}</p>
                    <h2 class="text-2xl font-semibold text-heading-foreground">{{ __('Define la voz de tu marca') }}</h2>
</div>
                <button class="btn btn-sm btn-light" @click="closeCreate">{{ __('Cerrar') }}</button>
            </div>

            <form action="{{ route('dashboard.user.brain-brand.store') }}" method="POST" class="mt-6 grid gap-5">
                @csrf
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-1 text-sm">
                        <label class="font-semibold text-heading-foreground" for="bb-name">{{ __('Nombre del BrainBrand') }}</label>
                        <input
                            id="bb-name"
                            type="text"
                            name="name"
                            class="w-full rounded-xl border border-border bg-background px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                            placeholder="ej. Marca Central LATAM"
                            required
                        >
                            </div>
                    <div class="space-y-1 text-sm">
                        <label class="font-semibold text-heading-foreground" for="bb-tone">{{ __('Tono principal') }}</label>
                        <select
                            id="bb-tone"
                            name="tone"
                            class="w-full rounded-xl border border-border bg-background px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                            required
                        >
                            <option value="friendly">{{ __('Amigable') }}</option>
                            <option value="professional">{{ __('Profesional') }}</option>
                            <option value="casual">{{ __('Casual') }}</option>
                            <option value="formal">{{ __('Formal') }}</option>
                                </select>
                    </div>
                </div>

                <div class="space-y-1 text-sm">
                    <label class="font-semibold text-heading-foreground" for="bb-description">{{ __('Descripción / voice guide') }}</label>
                    <textarea
                        id="bb-description"
                        name="description"
                        rows="3"
                        class="w-full rounded-xl border border-border bg-background px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                        placeholder="{{ __('Describe contexto, palabras prohibidas, mensajes clave...') }}"
                    ></textarea>
                    </div>
                    
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-1 text-sm">
                        <label class="font-semibold text-heading-foreground" for="bb-personality">{{ __('Rasgos de personalidad') }}</label>
                        <input
                            id="bb-personality"
                            type="text"
                            name="personality"
                            class="w-full rounded-xl border border-border bg-background px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                            placeholder="Innovadora, experta, cercana"
                        >
                    </div>
                    <div class="space-y-1 text-sm">
                        <label class="font-semibold text-heading-foreground" for="bb-language">{{ __('Estilo de lenguaje') }}</label>
                        <select
                            id="bb-language"
                            name="language_style"
                            class="w-full rounded-xl border border-border bg-background px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                        >
                            <option value="conversational">{{ __('Conversacional') }}</option>
                            <option value="technical">{{ __('Técnico') }}</option>
                            <option value="simple">{{ __('Simple y claro') }}</option>
                            <option value="detailed">{{ __('Detallado') }}</option>
                                </select>
                    </div>
                        </div>

                <div class="rounded-2xl border border-dashed border-border p-4">
                    <p class="text-sm font-semibold text-heading-foreground">{{ __('Automatizaciones iniciales') }}</p>
                    <div class="mt-3 grid gap-3 md:grid-cols-2">
                        <label class="flex items-start gap-3 rounded-xl border border-border/70 bg-muted/30 p-3">
                            <input type="checkbox" name="auto_crawl" value="1" class="mt-1">
                            <span class="text-sm">
                                <strong>{{ __('Crawling automático') }}</strong>
                                <p class="text-xs text-muted-foreground">{{ __('Ejecuta LinkParser luego de crear el BrainBrand.') }}</p>
                            </span>
                                        </label>
                        <label class="flex items-start gap-3 rounded-xl border border-border/70 bg-muted/30 p-3">
                            <input type="checkbox" name="auto_distribute" value="1" checked class="mt-1">
                            <span class="text-sm">
                                <strong>{{ __('Distribución inmediata') }}</strong>
                                <p class="text-xs text-muted-foreground">{{ __('Sincroniza automáticamente con todos los chatbots conectados.') }}</p>
                            </span>
                                        </label>
                    </div>
                </div>

                <div class="flex flex-wrap justify-end gap-3">
                    <button type="button" class="btn btn-light" @click="closeCreate">{{ __('Cancelar') }}</button>
                    <button type="submit" class="btn btn-primary">
                        ✨ {{ __('Crear BrainBrand') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
