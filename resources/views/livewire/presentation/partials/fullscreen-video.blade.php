@props([
    'videoUrl',
    'wireKey',
    'finishMethod' => null,
    'label',
    'loop' => false,
])

<div
    wire:key="{{ $wireKey }}"
    wire:ignore
    class="text-center w-full px-2"
    @if (! $loop && $finishMethod)
        x-data="{
            finish() {
                @this.call('{{ $finishMethod }}');
            }
        }"
    @endif
>
    <video
        src="{{ $videoUrl }}"
        class="screen-media w-full max-w-4xl mx-auto rounded-2xl shadow-2xl bg-black"
        autoplay
        playsinline
        @if ($loop) loop @endif
        @if (! $loop && $finishMethod) @ended="finish()" @endif
    ></video>
    @if ($label)
        <p class="text-base sm:text-xl text-gray-500 mt-4">{{ $label }}</p>
    @endif
</div>
