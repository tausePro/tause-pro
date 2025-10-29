@php
	//Replicating table styles from table component
	$base_class = 'transition-colors max-lg:group-[&[data-view-mode=list]]:overflow-x-auto';

	$variations = [
		'variant' => [
			'solid' => 'rounded-card bg-card-background pt-1 group-[&[data-view-mode=grid]]:bg-transparent',
			'outline' => 'rounded-card border border-card-border pt-1 group-[&[data-view-mode=grid]]:border-0',
			'shadow' => ' rounded-card shadow-card bg-card-background pt-1 group-[&[data-view-mode=grid]]:shadow-none group-[&[data-view-mode=grid]]:bg-transparent',
			'outline-shadow' => 'rounded-card border border-card-border pt-1 shadow-card bg-card-background',
			'plain' => '',
		],
	];

	$variant =
		isset($variant) && isset($variations['variant'][$variant])
			? $variations['variant'][$variant]
			: $variations['variant'][Theme::getSetting('defaultVariations.table.variant', 'outline')];

	$class = @twMerge($base_class, $variant);
@endphp

<div class="flex justify-between items-center mb-4">
	<x-button
		id="delete-selected"
		variant="ghost-shadow"
		size="sm"
		onclick="bulkDelete('selected')"
		href="javascript:void(0);"
	>
		<small>{{__('Delete Selected')}}</small>
	</x-button>

	<x-button
		id="move-selected"
		variant="danger"
		size="sm"
		id="delete-all"
		onclick="bulkDelete('all')"
		href="javascript:void(0);"
	>
		<small>{{__('Delete All')}}</small>
	</x-button>
</div>

<div
	class="lqd-posts-container lqd-docs-container group transition-all [&[aria-busy=true]]:animate-pulse max-lg:[&[data-view-mode=list]]:max-w-full"
	id="lqd-docs-container"
	data-view-mode="list"
	x-bind:data-view-mode="$store.docsViewMode.docsViewMode"
	x-init
	x-merge.transition
>
	{{-- Setting the view mode attribute before contents load to avoid page flashes --}}
	<script>
		document.querySelector('.lqd-docs-container')?.setAttribute('data-view-mode', localStorage.getItem('docsViewMode')?.replace(/\"/g, '') || 'list');
	</script>

	<div class="{{ $class }}">
		<div
			class="lqd-posts-head lqd-docs-head grid gap-x-4 border-b px-4 py-3 text-4xs font-medium uppercase leading-tight tracking-wider text-foreground/50 [grid-template-columns:3fr_repeat(2,minmax(0,1fr))_100px_1fr] group-[&[data-view-mode=grid]]:hidden">
            <span>
                {{ __('Name') }}
            </span>

			<span>
                {{ __('Type') }}
            </span>

			<span>
                {{ __('Date') }}
            </span>

			<span>
                {{ __('Cost') }}
            </span>

			<span class="text-center">
                {{ __('Actions') }}
            </span>
		</div>

		@include('panel.user.openai.documents_list')
	</div>

	@if (!isset($disablePagination))
		{{ $items->links('pagination::ajax', [
			'action' => route('dashboard.user.openai.documents.all', ['id' => $currfolder?->id, 'listOnly' => true]),
			'currfolder' => $currfolder,
			'target_id' => 'lqd-docs-container',
		]) }}
	@endif
</div>

@push('script')
	<script>
		function bulkDelete(mode = 'selected') {
			let payload = {};

			if (mode === 'all') {
				if (!confirm("{{__('Are you sure you want to delete all documents?')}}")) {
					return;
				}
				payload.all = true;
			} else {
				const selected = Array.from(document.querySelectorAll('.document-checkbox:checked')).map(cb => cb.value);

				if (selected.length === 0) {
					alert("{{__('Please select documents to delete.')}}");
					return;
				}

				if (!confirm("{{__('Are you sure you want to delete the selected documents?')}}")) {
					return;
				}

				payload.ids = selected;
			}

			fetch("{{ route('dashboard.user.openai.documents.bulkDelete') }}", {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
				},
				body: JSON.stringify(payload)
			})
				.then(res => res.json())
				.then(data => {
					if (data.success) {
						alert("{{__('Documents deleted successfully')}}");
						location.reload();
					} else {
						alert("{{__('Failed to delete documents')}}");
					}
				})
				.catch(err => {
					console.error(err);
					alert("{{__('Something went wrong')}}");
				});
		}
	</script>
@endpush

