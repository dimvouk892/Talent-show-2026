@if ($talentShow->hasPresentationBackground())
    <div class="fixed inset-0 z-0 h-dvh w-screen overflow-hidden pointer-events-none" aria-hidden="true">
        @if ($talentShow->presentation_bg_type === 'video')
            <video class="absolute inset-0 h-full w-full min-h-full min-w-full object-cover"
                   src="{{ $talentShow->presentationBackgroundUrl() }}"
                   autoplay muted loop playsinline></video>
        @else
            <img class="absolute inset-0 h-full w-full min-h-full min-w-full object-cover"
                 src="{{ $talentShow->presentationBackgroundUrl() }}"
                 alt="">
        @endif
    </div>
@endif
