<div
    class="col-start-1 col-end-1 row-start-1 row-end-1"
    x-show="activeTab === '{{ \App\Enums\AiInfluencer\ScriptTabEnum::CUSTOM_SCRIPT->value }}'"
    x-transition.opacity.150ms
>
    <div>
        <x-forms.input
            type="textarea"
            placeholder="{{ __('Add your custom script') }}"
            rows="5"
            x-model="customScriptContent"
            size="lg"
        />
    </div>
</div>
