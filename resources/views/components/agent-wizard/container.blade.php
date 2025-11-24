@props([
    'steps' => [],
    'current' => 1,
])

@php
    $preparedSteps = collect($steps)
        ->map(fn ($step, $index) => [
            'index' => $index + 1,
            'title' => $step['title'] ?? __('Paso :number', ['number' => $index + 1]),
            'description' => $step['description'] ?? '',
        ])
        ->values()
        ->toArray();
@endphp

<div
    x-data="agentWizard({
        steps: {{ \Illuminate\Support\Js::from($preparedSteps) }},
        initialStep: {{ $current }},
    })"
    class="space-y-6"
    x-cloak
>
    <div class="grid gap-3 rounded-2xl bg-white/80 p-4 shadow-sm ring-1 ring-border dark:bg-background">
        @foreach($preparedSteps as $step)
            <div
                class="flex items-start gap-3 rounded-xl p-3 text-xs font-medium transition"
                :class="{
                    'bg-primary/10 text-primary ring-1 ring-primary/20': isCurrent({{ $step['index'] }}),
                    'opacity-50 hover:opacity-80 cursor-pointer': !isCurrent({{ $step['index'] }})
                }"
                @click="goTo({{ $step['index'] }})"
            >
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold"
                    :class="isCompleted({{ $step['index'] }})
                        ? 'bg-primary text-white'
                        : 'bg-muted text-muted-foreground'"
                >
                    <span x-text="formattedIndex({{ $step['index'] }})"></span>
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-heading-foreground">
                        {{ __($step['title']) }}
                    </p>
                    @if($step['description'])
                        <p class="text-xs text-muted-foreground">
                            {{ __($step['description']) }}
                        </p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="rounded-2xl bg-white p-6 shadow ring-1 ring-border dark:bg-background">
        {{ $slot }}
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('agentWizard', ({ steps = [], initialStep = 1 } = {}) => ({
                    steps,
                    currentStep: initialStep,
                    goTo(step) {
                        if (step < 1 || step > this.steps.length) {
                            return;
                        }
                        this.currentStep = step;
                        this.$dispatch('agent-wizard:step-changed', { step });
                    },
                    next() {
                        if (this.currentStep < this.steps.length) {
                            this.goTo(this.currentStep + 1);
                        }
                    },
                    previous() {
                        if (this.currentStep > 1) {
                            this.goTo(this.currentStep - 1);
                        }
                    },
                    isCurrent(step) {
                        return this.currentStep === step;
                    },
                    isCompleted(step) {
                        return this.currentStep > step;
                    },
                    formattedIndex(step) {
                        return String(step).padStart(2, '0');
                    },
                    isStep(step) {
                        return this.isCurrent(step);
                    }
                }));
            });
        </script>
    @endpush
@endonce

