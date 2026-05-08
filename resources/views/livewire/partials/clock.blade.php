{{--
    Live Clock
    Usage: @include('livewire.partials.ui.live-clock')
--}}
<div class="inline-flex items-center gap-2 px-2 py-1 text-gray-800 dark:text-gray-300 text-sm"
    x-data="{
        locale: '{{ app()->getLocale() }}',
        nowMs: Date.now(),
        get intlLocale() { return this.locale === 'cn' ? 'zh-CN' : this.locale; },
        tick() { this.nowMs = Date.now(); },
        start() { this.tick(); setInterval(() => this.tick(), 1000); },
        get formattedDate() {
            return new Intl.DateTimeFormat(this.intlLocale, {
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
            }).format(this.nowMs);
        },
        get formattedTime() {
            return new Intl.DateTimeFormat(this.intlLocale, {
                hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true
            }).format(this.nowMs);
        }
    }"
    x-init="start()">
    <span class="hidden sm:inline" x-text="formattedDate"></span>
    <span class="hidden sm:inline text-zinc-300 dark:text-zinc-600">•</span>
    <span x-text="formattedTime"></span>
</div>
