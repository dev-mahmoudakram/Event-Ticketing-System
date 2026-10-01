<h2>About these terms</h2>
<p>These terms apply to tickets for {{ $event['name'] }}, organised by [Company legal name] (Commercial Registration [Commercial registration number]). By requesting or buying a ticket you agree to them.</p>
<h2>How tickets work</h2>
<p>Tickets are requested on this website and reviewed by our team. If a request is approved, we email a payment link. Once payment is confirmed, the e-ticket — with its QR code and workshop booking key — is emailed to the address on the request.</p>
<ul>
<li>Each ticket admits one named person and cannot be transferred without our written agreement.</li>
<li>Prices are shown in {{ $event['currency'] }} and include any applicable taxes unless stated otherwise.</li>
<li>Entry requires the QR code on the e-ticket, which is checked once at the door.</li>
<li>Workshop places are limited and booked with the workshop booking key, first come, first served.</li>
</ul>
<h2>At the event</h2>
<p>We may refuse entry or remove anyone whose behaviour puts others at risk or breaks venue rules. The programme, speakers and timings may change; we will tell ticket holders about significant changes by email.</p>
<h2>Refunds</h2>
<p>Refunds and cancellations follow our Refund &amp; Cancellation Policy.</p>
<h2>Liability</h2>
<p>To the extent allowed by law, [Company legal name] is not liable for indirect losses, or for loss of or damage to personal belongings at the venue.</p>
<h2>Governing law</h2>
<p>These terms are governed by the laws of the Arab Republic of Egypt.</p>
<h2>Contact</h2>
<p>Questions about these terms: {{ $event['email'] ?? '[Contact email]' }}{{ $event['phone'] ? ' · '.$event['phone'] : '' }}.</p>
