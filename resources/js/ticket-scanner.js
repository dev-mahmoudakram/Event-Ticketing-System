/**
 * The registration desk scanner.
 *
 * html5-qrcode is loaded only when somebody actually starts the camera, so the admin bundle
 * every other screen downloads stays free of it.
 */
export default function ticketScanner({ endpoint, arrived, labels }) {
    return {
        labels,
        arrived,
        running: false,
        cameraError: '',
        verdict: '',
        message: '',
        ticket: null,
        scanner: null,
        audio: null,
        cameras: [],
        cameraId: '',
        lastCode: '',
        lastCodeAt: 0,

        async toggle() {
            this.running ? await this.stop() : await this.start();
        },

        async start() {
            this.cameraError = '';

            // A browser hands out a camera only on a secure origin: https, or localhost.
            // Anywhere else the API is not merely blocked, it is absent — so say that
            // plainly rather than sending somebody hunting through permission settings that
            // were never the problem.
            if (! window.isSecureContext) {
                this.cameraError = this.labels.insecure;

                return;
            }

            if (! navigator.mediaDevices?.getUserMedia) {
                this.cameraError = this.labels.unsupported;

                return;
            }

            try {
                const { Html5Qrcode } = await import('html5-qrcode');

                this.scanner = new Html5Qrcode('scanner-view', { verbose: false });

                await this.scanner.start(
                    await this.chooseCamera(Html5Qrcode),
                    { fps: 10, qrbox: { width: 250, height: 250 } },
                    (text) => this.onScan(text),
                    () => {},
                );

                this.running = true;
            } catch (error) {
                console.error('[check-in] camera failed to start:', error);
                this.cameraError = this.messageForCameraError(error);
                this.scanner = null;
            }
        },

        /**
         * Which camera to open.
         *
         * A phone at the door should use its back camera, which is what somebody holds a
         * ticket up to. A desktop has only a front-facing webcam, and asking it for a rear
         * camera is not a preference — it is a constraint the browser cannot satisfy, so it
         * reports no device at all. Hence: ask for the back camera only when the device
         * actually has one, and otherwise take whatever camera exists.
         */
        async chooseCamera(Html5Qrcode) {
            const cameras = await Html5Qrcode.getCameras();

            this.cameras = cameras;

            if (this.cameraId) {
                return this.cameraId;
            }

            const back = cameras.find((camera) => /back|rear|environment/i.test(camera.label));

            if (back) {
                this.cameraId = back.id;

                return back.id;
            }

            if (cameras.length > 0) {
                this.cameraId = cameras[0].id;

                return cameras[0].id;
            }

            // Nothing enumerated: let the browser pick and report its own failure.
            return { facingMode: 'environment' };
        },

        /**
         * Switch cameras without losing the desk's place.
         */
        async useCamera(id) {
            this.cameraId = id;

            if (this.running) {
                await this.stop();
                await this.start();
            }
        },

        /**
         * Why the camera did not open, in the words of whoever has to fix it.
         */
        messageForCameraError(error) {
            const name = error?.name ?? String(error ?? '');

            if (name === 'NotAllowedError' || name === 'SecurityError') {
                return this.labels.denied;
            }

            if (name === 'NotFoundError' || name === 'OverconstrainedError' || name === 'DevicesNotFoundError') {
                return this.labels.noCamera;
            }

            if (name === 'NotReadableError' || name === 'TrackStartError') {
                return this.labels.busy;
            }

            return this.labels.blocked + ' (' + name + ')';
        },

        async stop() {
            if (this.scanner) {
                try {
                    await this.scanner.stop();
                    this.scanner.clear();
                } catch (error) {
                    // Already stopped; nothing left to release.
                }
                this.scanner = null;
            }

            this.running = false;
        },

        /**
         * A camera reads the same code many times a second while it sits in frame. Only the
         * first read is sent; the rest are ignored until the code has been out of frame long
         * enough that a second look is deliberate.
         */
        onScan(text) {
            const now = Date.now();

            if (text === this.lastCode && now - this.lastCodeAt < 3000) {
                return;
            }

            this.lastCode = text;
            this.lastCodeAt = now;

            this.verify(text);
        },

        async verify(code) {
            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify({ qr_code: code }),
                });

                if (! response.ok) {
                    throw new Error('scan rejected');
                }

                const data = await response.json();

                this.verdict = data.result;
                this.message = data.message;
                this.ticket = data.ticket;

                if (data.result === 'verified') {
                    this.arrived += 1;
                }

                this.announce(data.result);
            } catch (error) {
                this.verdict = 'invalid';
                this.message = this.labels.offline;
                this.ticket = null;
                this.announce('invalid');
            }
        },

        /**
         * A short tone per outcome.
         *
         * Somebody working a queue watches the person in front of them, not the screen, so
         * the verdict has to be audible: a rising note admits, a low buzz refuses. Generated
         * rather than loaded, so the desk carries no audio files and works offline.
         */
        announce(result) {
            const Context = window.AudioContext ?? window.webkitAudioContext;

            if (! Context) {
                return;
            }

            try {
                this.audio ??= new Context();

                const oscillator = this.audio.createOscillator();
                const gain = this.audio.createGain();
                const now = this.audio.currentTime;
                const admitted = result === 'verified';

                oscillator.type = admitted ? 'sine' : 'square';
                oscillator.frequency.setValueAtTime(admitted ? 880 : 200, now);

                if (admitted) {
                    oscillator.frequency.exponentialRampToValueAtTime(1320, now + 0.12);
                }

                gain.gain.setValueAtTime(0.0001, now);
                gain.gain.exponentialRampToValueAtTime(admitted ? 0.2 : 0.3, now + 0.01);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + (admitted ? 0.18 : 0.35));

                oscillator.connect(gain).connect(this.audio.destination);
                oscillator.start(now);
                oscillator.stop(now + (admitted ? 0.2 : 0.4));
            } catch (error) {
                // Sound is a courtesy, never the record: the verdict is on screen regardless.
            }
        },
    };
}
