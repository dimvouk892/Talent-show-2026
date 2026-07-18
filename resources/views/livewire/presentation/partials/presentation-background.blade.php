@if ($talentShow->hasPresentationBackground())
    <div wire:key="presentation-bg-{{ $talentShow->presentation_bg_path }}"
         wire:ignore
         class="fixed inset-0 z-0 h-dvh w-screen overflow-hidden pointer-events-none"
         aria-hidden="true"
         x-data
         x-init="
            const video = $el.querySelector('video');
            if (!video) return;

            const ensurePlaying = () => {
                if (video.paused || video.ended) {
                    video.play().catch(() => {});
                }
            };

            ensurePlaying();
            video.addEventListener('pause', ensurePlaying);
            video.addEventListener('stalled', ensurePlaying);
            video.addEventListener('suspend', ensurePlaying);
            setInterval(ensurePlaying, 2500);
         ">
        @if ($talentShow->presentation_bg_type === 'video')
            <video class="absolute inset-0 h-full w-full min-h-full min-w-full object-cover"
                   src="{{ $talentShow->presentationBackgroundUrl() }}"
                   autoplay muted loop playsinline webkit-playsinline preload="auto"></video>
        @else
            <img class="absolute inset-0 h-full w-full min-h-full min-w-full object-cover"
                 src="{{ $talentShow->presentationBackgroundUrl() }}"
                 alt="">
        @endif
    </div>
@endif
