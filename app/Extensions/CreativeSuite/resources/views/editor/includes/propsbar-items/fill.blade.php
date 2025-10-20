@php
    $tabs = [
        'color' => [
            'label' => __('Color'),
        ],
        'image' => [
            'label' => __('Image'),
        ],
        // 'gradient' => [
        //     'label' => __('Gradient'),
        // ],
    ];

    $fill_cover_pos = [
        'left-top' => [
            'label' => __('Left Top'),
        ],
        'center-top' => [
            'label' => __('Center Top'),
        ],
        'right-top' => [
            'label' => __('Right Top'),
        ],
        'left-middle' => [
            'label' => __('Left Middle'),
        ],
        'center-middle' => [
            'label' => __('Center Middle'),
        ],
        'right-middle' => [
            'label' => __('Right Middle'),
        ],
        'left-bottom' => [
            'label' => __('Left Bottom'),
        ],
        'center-bottom' => [
            'label' => __('Center Bottom'),
        ],
        'right-bottom' => [
            'label' => __('Right Bottom'),
        ],
    ];
@endphp
<div
    class="flex gap-2.5"
    x-cloak
    x-show="!selectedNodes.find(n => ['Text', 'TextPath'].includes(n.getClassName()))"
>
    <div class="flex gap-[3px]">
        @foreach ($tabs as $key => $tab)
            <button
                class="flex grow items-center justify-center bg-foreground/[3%] px-9 py-[11px] text-center text-xs/none font-medium transition-all first:rounded-s-xl last:rounded-e-xl [&.active]:bg-primary/5 [&.active]:text-primary"
                :class="{ active: activeFillTab === '{{ $key }}' }"
                @click.prevent="activeFillTab = '{{ $key }}'"
                type="button"
            >
                {{ $tab['label'] }}
            </button>
        @endforeach
    </div>

    <div>
        {{-- Start Color Tab --}}
        <div
            class="flex w-full justify-start px-2 py-1"
            x-cloak
            x-show="activeFillTab === 'color'"
        >
            <div
                class="flex items-center"
                x-data="liquidColorPicker({ colorVal: selectedNodesProps.fill })"
            >
                <span
                    class="lqd-input-color-wrap !size-[26px] shrink-0 rounded-full border-[3px] border-background shadow-md shadow-black/10"
                    x-ref="colorInputWrap"
                    :style="{ backgroundColor: colorVal }"
                ></span>
                <input
                    class="invisible w-0 border-none bg-transparent p-0 text-3xs font-medium focus:outline-none"
                    type="text"
                    size="sm"
                    value="#ffffff"
                    x-ref="colorInput"
                    :value="selectedNodesProps.fill"
                    @input="selectedNodesProps = { fill: $event.target.value }"
                    @change="picker.setColor($event.target.value)"
                    @keydown.enter.prevent="picker?.setColor($event.target.value);"
                    @focus="picker.open(); $el.select();"
                    x-effect="selectedNodes.length === 1 && $nextTick(() => picker.setColor(selectedNodesProps.fill))"
                />
            </div>
        </div>
        {{-- End Color Tab --}}

        {{-- Start Image Tab --}}
        <div
            class="self-center"
            x-cloak
            x-show="activeFillTab === 'image'"
        >
            <div
                class="relative flex gap-1"
                :class="{ 'opacity-50 pointer-events-none': selectedNodes.find(node => node.getAttr('aiTaskInProgress')) }"
            >
                <input
                    class="hidden w-44 grow text-3xs font-medium file:px-1 file:py-0.5"
                    type="file"
                    accept="image/*"
                    x-ref="selectedNodeFillPatternInput"
                    @change.prevent="const uploadedImage = await uploadImage({event: $event, node: selectedNodes[0]}); await setNodeFillPattern({node: selectedNodes[0], url: uploadedImage.url}); selectedNodes.at(0)?.getAttr('fillCover') && applyFillCover()"
                    @cancel.prevent="console.log($event.target.files)"
                    @nodes-selected.window="$el.value = ''"
                >

                <x-button
                    class="flex grow items-center justify-center gap-2.5 self-center rounded-xl border px-3 py-2.5 text-center text-xs/none font-medium transition-all"
                    variant="outline"
                    type="button"
                    @click.prevent="$refs.selectedNodeFillPatternInput.click()"
                >
                    <x-tabler-upload class="size-4 shrink-0" />
                    {{ __('Select Image') }}
                </x-button>

                <x-button
                    class="aspect-square size-[38px] shrink-0 bg-red-400"
                    variant="danger"
                    size="none"
                    type="button"
                    x-show="selectedNodes[0]?.fillPatternImage()"
                    @click.prevent="removeFillPattern"
                    title="{{ __('Remove Image') }}"
                >
                    <x-tabler-photo-x class="size-4" />
                </x-button>
            </div>

            {{-- <div
                class="py-1"
                :class="{ 'opacity-50 pointer-events-none': !selectedNodes[0]?.fillPatternImage() }"
            >
                <x-forms.input
                    class:label="text-3xs font-medium text-foreground"
                    size="sm"
                    type="checkbox"
                    switcher
                    label="{{ __('Cover The Shape') }}"
                    ::checked="selectedNodes.at(0)?.getAttr('fillCover')"
                    @change="selectedNodes.at(0)?.setAttr('fillCover', $event.target.checked); $event.target.checked && applyFillCover()"
                />

                <div
                    class="pt-1"
                    x-show="selectedNodes.at(0)?.getAttr('fillCover')"
                >
                    <p class="m-0 cursor-default py-0.5 text-3xs font-semibold opacity-85">
                        {{ __('Anchor Position') }}
                    </p>

                    <div class="flex shrink-0 grow flex-wrap">
                        @foreach ($fill_cover_pos as $key => $pos)
                            <button
                                @class([
                                    'group h-5 w-1/3 flex items-center p-1 hover:bg-foreground/10 rounded',
                                    'active' => $key === 'center-middle',
                                    'justify-start' => str_contains($key, 'left'),
                                    'justify-center' => str_contains($key, 'center'),
                                    'justify-end' => str_contains($key, 'right'),
                                ])
                                type="button"
                                title="{{ $pos['label'] }}"
                                :class="{ active: selectedNodes.at(0)?.getAttr('fillAlign') === '{{ $key }}' }"
                                @click.prevent="selectedNodes.at(0)?.setAttr('fillAlign', '{{ $key }}'); selectedNodes.at(0)?.getAttr('fillCover') && applyFillCover()"
                            >
                                <span
                                    class="relative inline-block size-1 rounded-sm bg-foreground before:absolute before:left-1/2 before:top-1/2 before:inline-block before:size-2.5 before:-translate-x-1/2 before:-translate-y-1/2 before:rounded before:bg-primary before:opacity-0 before:transition-opacity group-hover:before:opacity-100 group-[&.active]:before:opacity-100"
                                ></span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div x-show="!selectedNodes.at(0)?.getAttr('fillCover')">
                <div>
                    <p class="m-0 cursor-default text-4xs font-semibold opacity-85">
                        {{ __('Position') }}
                    </p>
                    <div
                        class="grid grid-cols-2 gap-1"
                        :class="{ 'opacity-50 pointer-events-none': !selectedNodes[0]?.fillPatternImage() }"
                    >
                        <div
                            class="group relative flex w-24 min-w-full font-semibold uppercase"
                            x-data="dynamicInput()"
                        >
                            <span
                                class="absolute start-0 top-0 grid h-full w-6 cursor-ew-resize select-none place-items-center text-4xs opacity-80"
                                x-ref="dynamicLabel"
                            >
                                <x-tabler-letter-x-small class="size-4" />
                            </span>
                            <input
                                class="col-start-1 col-end-1 row-start-1 row-end-1 w-full appearance-none rounded-md border border-foreground/10 bg-transparent py-0.5 pe-1 ps-6 text-base sm:text-3xs transition-all focus:border-primary focus:outline-none group-[&.dragging]:border-primary"
                                x-ref="dynamicInput"
                                :value="selectedNodesProps.fillPatternX"
                                @input="selectedNodesProps = { fillPatternX: $event.target.value }"
                                @focus="selectedNodes.length > 1 && $el.select()"
                            >
                        </div>
                        <div
                            class="group relative flex w-24 min-w-full font-semibold uppercase"
                            x-data="dynamicInput()"
                        >
                            <span
                                class="absolute start-0 top-0 grid h-full w-6 cursor-ew-resize select-none place-items-center text-4xs opacity-80"
                                x-ref="dynamicLabel"
                            >
                                <x-tabler-letter-y-small class="size-4" />
                            </span>
                            <input
                                class="col-start-1 col-end-1 row-start-1 row-end-1 w-full appearance-none rounded-md border border-foreground/10 bg-transparent py-0.5 pe-1 ps-6 text-base sm:text-3xs transition-all focus:border-primary focus:outline-none group-[&.dragging]:border-primary"
                                x-ref="dynamicInput"
                                :value="selectedNodesProps.fillPatternY"
                                @input="selectedNodesProps = { fillPatternY: $event.target.value }"
                                @focus="selectedNodes.length > 1 && $el.select()"
                            >
                        </div>
                    </div>
                </div>

                <div>
                    <p class="m-0 cursor-default text-4xs font-semibold opacity-85">
                        {{ __('Size') }}
                    </p>
                    <div
                        class="flex gap-1"
                        x-data="{ linked: true }"
                        :class="{ 'opacity-50 pointer-events-none': !selectedNodes[0]?.fillPatternImage() }"
                    >
                        <div class="grid grow grid-cols-2 gap-1">
                            <div
                                class="group relative flex w-20 min-w-full font-semibold uppercase"
                                x-data="dynamicInput({ step: 0.1, min: 0.1 })"
                            >
                                <span
                                    class="absolute start-0 top-0 grid h-full w-6 cursor-ew-resize select-none place-items-center text-4xs opacity-80"
                                    x-ref="dynamicLabel"
                                >
                                    <x-tabler-letter-x-small class="size-4" />
                                </span>
                                <input
                                    class="col-start-1 col-end-1 row-start-1 row-end-1 w-full appearance-none rounded-md border border-foreground/10 bg-transparent py-0.5 pe-1 ps-6 text-base sm:text-3xs transition-all focus:border-primary focus:outline-none group-[&.dragging]:border-primary"
                                    x-ref="dynamicInput"
                                    :value="selectedNodesProps.fillPatternScaleX"
                                    @input="selectedNodesProps = { fillPatternScaleX: $event.target.value, fillPatternScaleY: linked ? $event.target.value : null };"
                                    @focus="selectedNodes.length > 1 && $el.select()"
                                >
                            </div>
                            <div
                                class="group relative flex w-20 min-w-full font-semibold uppercase"
                                x-data="dynamicInput({ step: 0.1, min: 0.1 })"
                            >
                                <span
                                    class="absolute start-0 top-0 grid h-full w-6 cursor-ew-resize select-none place-items-center text-4xs opacity-80"
                                    x-ref="dynamicLabel"
                                >
                                    <x-tabler-letter-y-small class="size-4" />
                                </span>
                                <input
                                    class="col-start-1 col-end-1 row-start-1 row-end-1 w-full appearance-none rounded-md border border-foreground/10 bg-transparent py-0.5 pe-1 ps-6 text-base sm:text-3xs transition-all focus:border-primary focus:outline-none group-[&.dragging]:border-primary"
                                    x-ref="dynamicInput"
                                    :value="selectedNodesProps.fillPatternScaleY"
                                    @input="selectedNodesProps = { fillPatternScaleY: $event.target.value, fillPatternScaleX: linked ? $event.target.value : null }"
                                    @focus="selectedNodes.length > 1 && $el.select()"
                                >
                            </div>
                        </div>

                        <div class="flex rounded-lg bg-foreground/5 p-0.5">
                            <button
                                class="active inline-grid size-6 shrink-0 place-items-center rounded-md bg-foreground/5 transition-all [&.active]:bg-background [&.active]:shadow-md [&.active]:shadow-black/5"
                                :class="{ active: linked }"
                                type="button"
                                @click.prevent="linked = !linked"
                            >
                                <x-tabler-link class="size-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <div :class="{ 'opacity-50 pointer-events-none': !selectedNodes[0]?.fillPatternImage() }">
                    <p class="m-0 cursor-default text-4xs font-semibold opacity-85">
                        {{ __('Repeat') }}
                    </p>
                    <select
                        class="w-full rounded-input border border-input-border bg-transparent px-2 py-1 text-3xs font-medium"
                        :value="selectedNodesProps.fillPatternRepeat"
                        @change="selectedNodesProps = { fillPatternRepeat: $event.target.value }"
                    >
                        <option value="no-repeat">{{ __('No Repeat') }}</option>
                        <option value="repeat">{{ __('Repeat') }}</option>
                        <option value="repeat-x">{{ __('Repeat X') }}</option>
                        <option value="repeat-y">{{ __('Repeat Y') }}</option>
                    </select>
                </div>
            </div> --}}
        </div>
        {{-- End Image Tab --}}

        {{-- Start Gradient Tab --}}
        {{-- <div
            x-cloak
            x-show="activeFillTab === 'gradient'"
        >

        </div> --}}
        {{-- End Gradient Tab --}}
    </div>
</div>
