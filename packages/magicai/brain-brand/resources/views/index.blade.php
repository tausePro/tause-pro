@extends('panel.layout.app')

@section('title', __('Brain Brand'))

@section('content')
<div class="mx-auto flex max-w-[1400px] flex-col gap-6 px-6 pb-10">
    <p class="text-sm opacity-70">{{ __('Centro de control de la marca: estado, conocimiento e insights.') }}</p>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-card class="space-y-4">
            <x-forms.input id="bb_company" type="select" label="{{ __('Company') }}">
                @foreach($companies as $c)
                    <option value="{{ $c->id }}" data-website="{{ $c->website }}">{{ $c->name }}</option>
                @endforeach
            </x-forms.input>
            <x-forms.input id="bb_chatbot" type="select" label="{{ __('Chatbot') }}">
                @foreach($chatbots as $b)
                    <option value="{{ $b->id }}">{{ $b->title }}</option>
                @endforeach
            </x-forms.input>
            <div class="flex gap-2">
                <x-button href="{{ route('dashboard.user.brand.index') }}" variant="outline">
                    <x-tabler-brand-trello class="size-4" />
                    {{ __('Brand Voice') }}
                </x-button>
                <x-button variant="secondary" id="bb_research">
                    <x-tabler-refresh class="size-4" />
                    {{ __('Rebuscar (SERP/Tavily)') }}
                </x-button>
                <x-button id="bb_train">
                    <x-tabler-brain class="size-4" />
                    {{ __('Entrenar') }}
                </x-button>
            </div>
        </x-card>

        <x-card class="lg:col-span-2">
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4" id="bb_metrics">
                <div>
                    <div class="text-xs opacity-60">{{ __('Coverage') }}</div>
                    <div class="text-2xl font-semibold" data-metric="coverage">—</div>
                </div>
                <div>
                    <div class="text-xs opacity-60">{{ __('Freshness (days)') }}</div>
                    <div class="text-2xl font-semibold" data-metric="freshness">—</div>
                </div>
                <div>
                    <div class="text-xs opacity-60">{{ __('Gaps') }}</div>
                    <div class="text-2xl font-semibold" data-metric="gaps">—</div>
                </div>
                <div>
                    <div class="text-xs opacity-60">{{ __('Competitors') }}</div>
                    <div class="text-2xl font-semibold" data-metric="competitors">—</div>
                </div>
            </div>
        </x-card>
    </div>

    <x-card class="space-y-2">
        <h3 class="text-base font-semibold mb-2">{{ __('Overview') }}</h3>
        <p class="text-sm opacity-70 mb-6">{{ __('Resumen y alertas aparecerán aquí.') }}</p>

        <h3 class="text-base font-semibold mb-2">{{ __('Knowledge') }}</h3>
        <div class="flex items-center gap-3 mb-3">
            <label class="text-sm">{{ __('Type') }}</label>
            <select id="bb_type" class="border rounded p-1">
                <option value="text">text</option>
                <option value="qa">qa</option>
                <option value="url">url</option>
                <option value="pdf">pdf</option>
            </select>
            <x-button id="bb_refresh" size="sm">{{ __('Refresh') }}</x-button>
        </div>
        <div id="bb_table" class="rounded border border-input-border p-3 text-sm opacity-80">{{ __('No data') }}</div>

        <h3 class="text-base font-semibold mt-6 mb-2">{{ __('Insights') }}</h3>
        <p class="text-sm opacity-70">{{ __('Agency Insights y Suggested Actions listados como text waiting.') }}</p>
    </x-card>
</div>
@endsection

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const companySelect = document.getElementById('bb_company');
  const table = document.getElementById('bb_table');
  const typeSelect = document.getElementById('bb_type');
  const refreshBtn = document.getElementById('bb_refresh');
  const btnResearch = document.getElementById('bb_research');
  const btnTrain = document.getElementById('bb_train');
  const chatbotSelect = document.getElementById('bb_chatbot');
  const base = window.location.origin;
  const urls = {
    metrics: base + '/dashboard/brain-brand/metrics',
    knowledge: base + '/dashboard/brain-brand/knowledge',
    research: base + '/dashboard/brain-brand/research',
    train: base + '/dashboard/brain-brand/train',
  };
  async function loadMetrics() {
    if (!companySelect) return;
    const id = companySelect.value;
    const bot = chatbotSelect?.value || '';
    const res = await fetch(`${urls.metrics}?company_id=${id}&chatbot_id=${bot}`);
    const j = await res.json();
    document.querySelector('[data-metric="coverage"]').textContent = (j.coverage ?? 0) + '%';
    document.querySelector('[data-metric="freshness"]').textContent = j.freshness_days ?? '—';
    document.querySelector('[data-metric="gaps"]').textContent = j.gaps ?? 0;
    document.querySelector('[data-metric="competitors"]').textContent = j.competitors ?? 0;
  }
  async function loadKnowledge() {
    if (!table) return;
    table.textContent = 'Loading...';
    const id = companySelect.value;
    const type = typeSelect.value;
    const res = await fetch(`${urls.knowledge}?company_id=${id}&type=${type}`);
    const j = await res.json();
    if (!Array.isArray(j.items) || j.items.length === 0) { table.textContent = 'No data'; return; }
    const rows = j.items.map(i => `<tr><td class="px-2 py-1">${i.id}</td><td class="px-2 py-1">${i.type}</td><td class="px-2 py-1 truncate max-w-[480px]">${i.type_value ?? ''}</td><td class="px-2 py-1">${i.status}</td></tr>`).join('');
    table.innerHTML = `<table class="w-full"><thead><tr><th class="text-left px-2">ID</th><th class="text-left px-2">Type</th><th class="text-left px-2">Value</th><th class="text-left px-2">Status</th></tr></thead><tbody>${rows}</tbody></table>`;
  }
  companySelect?.addEventListener('change', () => { loadMetrics(); loadKnowledge(); });
  refreshBtn?.addEventListener('click', loadKnowledge);
  btnResearch?.addEventListener('click', async function() {
    const id = companySelect.value; if (!id) return; const bot = chatbotSelect?.value || '';
    // usar website de la compañía cuando esté disponible
    const website = companySelect?.options[companySelect.selectedIndex]?.dataset?.website || companySelect?.options[companySelect.selectedIndex]?.text || '';
    try {
      // usar GET para evitar bloqueos si alguna capa filtra POST
      const qs = new URLSearchParams({company_id:id, chatbot_id:bot, website}).toString();
      const resp = await fetch(`${urls.research}?${qs}`, {headers:{'Accept':'application/json'}});
      // ignorar errores silenciosos, pero refrescar
    } catch(e) {}
    await loadKnowledge();
    await loadMetrics();
  });
  btnTrain?.addEventListener('click', async function() {
    const id = companySelect.value; if (!id) return;
    const type = typeSelect.value; const bot = chatbotSelect?.value || '';
    try {
      const qs = new URLSearchParams({company_id:id, chatbot_id:bot, type}).toString();
      await fetch(`${urls.train}?${qs}`, {headers:{'Accept':'application/json'}});
    } catch(e) {}
    await loadKnowledge();
    await loadMetrics();
  });
  loadMetrics();
  loadKnowledge();
});
</script>
@endpush


