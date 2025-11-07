<div
    class="col-start-1 col-end-1 row-start-1 row-end-1"
    x-show="activeTab === '{{ \App\Enums\AiInfluencer\ScriptTabEnum::AUTO_GENERATED_SCRIPT->value }}'"
    x-transition.opacity.150ms
>
    <div class="flex flex-col gap-9">
        <template x-for="(generatedScript, index) in generatedScripts">
            <div
                class="relative flex cursor-pointer flex-col gap-3 rounded-xl border px-7 py-5"
                @click.prevent="selectScript(index)"
                :class="index == selectedScriptId ? 'shadow-xl' : ''"
            >
                <span
                    class="text-sm font-semibold text-heading-foreground"
                    x-text="generatedScript.script_name"
                ></span>
                <span
                    class="text-xs font-normal text-foreground"
                    x-text="generatedScript.paragraphs"
                ></span>
                <span
                    class="absolute end-2 top-2 flex items-center justify-center rounded-full bg-background/10 p-2 shadow-lg"
                    x-show="index == selectedScriptId"
                >
                    <x-tabler-check class="size-4" />
                </span>
            </div>
        </template>
    </div>
</div>
