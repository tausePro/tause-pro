<style>
    .konvajs-content {
        transform-origin: center center;
        will-change: transform;
        backface-visibility: hidden;
    }

    body.is-panning,
    .lqd-cs-canvas-container.is-panning {
        cursor: grab !important;
    }

    body.is-panning-active,
    .lqd-cs-canvas-container.is-panning-active {
        cursor: grabbing !important;
    }
</style>
<div
    class="lqd-cs-canvas-wrap h-screen w-screen overflow-hidden transition-all"
    x-ref="editorCanvasWrap"
>
    <div
        class="lqd-cs-canvas-inner relative grid h-screen w-screen grid-cols-1 overflow-hidden"
        x-ref="editorCanvasInner"
    >
        <div
            class="lqd-cs-start-panel group relative z-1 col-start-1 col-end-1 row-start-1 row-end-1 grid h-screen w-screen place-items-center py-[calc(var(--header-h)+1.5rem)] pe-5 ps-[calc(var(--sidebar-w)+1.25rem)]"
            x-show="showWelcomeScreen"
        >
            <div
                class="mx-auto flex h-[calc(min(570px,90vh)-calc(var(--header-h)+1.5rem))] w-[min(100%,720px)] flex-col items-center justify-center gap-6 overflow-y-auto rounded-3xl bg-background/50 py-5 text-center backdrop-blur-3xl transition-colors">
                <a
                    class="peer absolute start-0 top-0 z-2 h-full w-full"
                    href="#"
                    @click.prevent="$nextTick(() => activeTool = 'templates')"
                ></a>
                <span
                    class="inline-grid size-[53px] shrink-0 place-items-center rounded-full bg-heading-foreground text-heading-background transition-all peer-hover:scale-110 peer-hover:shadow-lg peer-hover:shadow-black/10"
                >
                    <x-tabler-plus class="size-7" />
                </span>
                <p class="mb-0 text-base font-bold">
                    {{ __('Select a template from library') }}
                </p>
                <div class="mx-auto flex w-[min(400px,85%)] items-center gap-8 text-2xs font-medium">
                    <span class="inline-block h-px grow bg-foreground/10"></span>
                    {{ __('or') }}
                    <span class="inline-block h-px grow bg-foreground/10"></span>
                </div>

                <x-button
                    class="relative z-3 px-8 py-3.5 outline-foreground/5 hover:bg-primary hover:text-primary-foreground hover:shadow-primary/15 hover:outline-primary"
                    variant="outline"
                    @click.prevent="$nextTick(() => activeTool = 'resize')"
                >
                    {{ __('Choose a preset size') }}
                </x-button>
            </div>
        </div>

        <div
            class="lqd-cs-canvas-container-wrap max-w-screen col-start-1 col-end-1 row-start-1 row-end-1 grid h-screen max-h-screen w-screen select-none grid-cols-1 place-content-center place-items-center overflow-hidden"
            x-ref="editorCanvasWrap"
        >
            <div
                class="lqd-cs-canvas-container col-start-1 col-end-1 row-start-1 row-end-1 grid h-full max-h-screen min-h-screen w-full min-w-full max-w-full touch-none select-none grid-cols-1 place-content-center place-items-center overflow-hidden transition-colors focus:outline-none [&_.konvajs-content]:w-full [&_.konvajs-content]:origin-center [&_.konvajs-content]:translate-x-[--zoom-offset-x] [&_.konvajs-content]:translate-y-[--zoom-offset-y] [&_.konvajs-content]:scale-[--zoom-level]"
                id="lqd-cs-canvas-container"
                x-ref="editorCanvasContainer"
                tabindex="0"
                :class="{ '[&_.konvajs-content]:bg-background [&_.konvajs-content]:shadow-sm': !showWelcomeScreen }"
                style="-webkit-tap-highlight-color: transparent;"
            >
            </div>

            @include('creative-suite::editor.includes.tooltip')
        </div>
    </div>
</div>
