<p>{{ __('Hi') }} {{ $ticket->name }},</p>
<p>{{ __('Your payment was received and your ticket has been issued.') }}</p>
<p>{{ __('Event') }}: {{ $ticket->event->name_en }}</p>
<p>{{ __('Reference') }}: {{ $ticket->ticket_number }}</p>
<p>{{ __('Ticket ID') }}: {{ $ticket->ticket_id }}</p>
<p>{{ __('Your QR code is attached to this email.') }}</p>