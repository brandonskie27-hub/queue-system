// A two-note "ding-dong" made with the Web Audio API, so there's no sound file to load.
//
// Browsers only allow audio after the person has interacted with the page (a tap or click).
// A student has already tapped "Get Ticket", so their ticket page can play it. The display
// screen has no such tap, so it shows an "Enable sound" button that calls unlockAudio().

let audioContext = null;

function context() {
    audioContext ??= new (window.AudioContext || window.webkitAudioContext)();

    return audioContext;
}

export function unlockAudio() {
    return context().resume();
}

export function playChime() {
    try {
        const ctx = context();
        ctx.resume();

        [880, 659.25].forEach((frequency, index) => {
            const start = ctx.currentTime + index * 0.35;
            const oscillator = ctx.createOscillator();
            const gain = ctx.createGain();

            oscillator.type = 'sine';
            oscillator.frequency.value = frequency;
            gain.gain.setValueAtTime(0.0001, start);
            gain.gain.exponentialRampToValueAtTime(0.4, start + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.9);

            oscillator.connect(gain).connect(ctx.destination);
            oscillator.start(start);
            oscillator.stop(start + 1);
        });
    } catch {
        // No audio available (old browser, or blocked). The visual alert still shows.
    }
}

export function vibrate(pattern) {
    navigator.vibrate?.(pattern);
}
