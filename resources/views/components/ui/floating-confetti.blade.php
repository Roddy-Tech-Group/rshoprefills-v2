{{-- Ambient floating-confetti decoration (zendit-style promo cards): scattered
     geometric bits that gently bob in a corner. Decorative only -
     pointer-events-none + aria-hidden, and motion is disabled for reduced-motion
     users. Drop it as the FIRST child of a `relative overflow-hidden` card and
     keep the real content at `relative z-10` so it sits above the bits. --}}
<div {{ $attributes->merge(['class' => 'pointer-events-none absolute inset-0 overflow-hidden opacity-10']) }} aria-hidden="true">
    <style>
        .cf { position: absolute; }
    </style>

    {{-- star --}}
    <svg class="cf text-amber-400" style="top:11%; left:58%; width:15px; height:15px; --d:5.5s; --dl:0s;" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.5 7H22l-6 4.5 2.3 7L12 17l-6.3 3.5L8 13.5 2 9h7.5z"/></svg>
    {{-- rounded square --}}
    <span class="cf rounded-[3px] bg-pink-300" style="top:7%; left:71%; width:13px; height:13px; --r:18deg; --d:6.2s; --dl:0.5s;"></span>
    {{-- dot --}}
    <span class="cf rounded-full bg-emerald-400" style="top:22%; left:53%; width:9px; height:9px; --d:4.6s; --dl:1.1s;"></span>
    {{-- dot --}}
    <span class="cf rounded-full bg-blue-400" style="top:31%; left:80%; width:8px; height:8px; --d:5.8s; --dl:0.3s;"></span>
    {{-- small star --}}
    <svg class="cf text-yellow-400" style="top:35%; left:65%; width:12px; height:12px; --d:5s; --dl:1.6s;" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.5 7H22l-6 4.5 2.3 7L12 17l-6.3 3.5L8 13.5 2 9h7.5z"/></svg>
    {{-- rounded square --}}
    <span class="cf rounded-[3px] bg-sky-300" style="top:16%; left:86%; width:11px; height:11px; --r:-12deg; --d:6.6s; --dl:0.9s;"></span>
    {{-- dot --}}
    <span class="cf rounded-full bg-amber-300" style="top:47%; left:74%; width:7px; height:7px; --d:4.8s; --dl:2s;"></span>
    {{-- triangle --}}
    <span class="cf" style="top:41%; left:89%; width:0; height:0; border-left:6px solid transparent; border-right:6px solid transparent; border-bottom:10px solid #c4b5fd; --r:8deg; --d:6s; --dl:1.3s;"></span>
    {{-- dot --}}
    <span class="cf rounded-full bg-rose-300" style="top:9%; left:49%; width:6px; height:6px; --d:5.2s; --dl:2.4s;"></span>
    {{-- rounded square --}}
    <span class="cf rounded-[2px] bg-blue-300" style="top:53%; left:61%; width:9px; height:9px; --r:24deg; --d:5.6s; --dl:0.2s;"></span>
</div>
