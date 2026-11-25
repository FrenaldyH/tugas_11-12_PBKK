@props(['failed' => false, 'pulse' => false])

{{-- Assistant avatar: black tile with the PromptLab green square (red when the answer failed) --}}
<span class="mt-0.5 grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-black" aria-hidden="true">
    <span @class(['h-2.5 w-2.5', 'bg-danger' => $failed, 'bg-brand' => ! $failed, 'animate-pulse motion-reduce:animate-none' => $pulse])></span>
</span>
