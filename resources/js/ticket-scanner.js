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
        lastCode: '',
        lastCodeAt: 0,

        async toggle() {
            this.running ? await this.stop() : await this.start();
        },

        async start() {
            this.cameraError = '';

            try {
                const { Html5Qrcode } = await import('html5-qrcode');

                this.scanner = new Html5Qrcode('scanner-view', { verbose: false });

                await this.scanner.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: { width: 250, height: 250 } },
                    (text) => this.onScan(text),
                    () => {},
                );

                this.running = true;
            } catch (error) {
                this.cameraError = this.labels.blocked;
                this.scanner = null;
            }
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
            } catch (error) {
                this.verdict = 'invalid';
                this.message = this.labels.offline;
                this.ticket = null;
            }
        },
    };
}
