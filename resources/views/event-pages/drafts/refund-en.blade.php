<h2>Before payment</h2>
<p>A ticket request costs nothing. You can withdraw a pending or approved request at any time before paying by emailing {{ $event['email'] ?? '[Contact email]' }} with your reference number.</p>
<h2>After payment</h2>
<ul>
<li>Paid tickets can be refunded if you ask [Refund window, e.g. at least 7 days before the event].</li>
<li>Refunds are paid back to the card used, through our payment provider, within [Refund processing time, e.g. 14 business days]. Amounts are refunded in {{ $event['currency'] }}.</li>
<li>Tickets are not refundable after that window, or once the ticket has been used to enter the event.</li>
</ul>
<h2>If the event changes</h2>
<p>If {{ $event['name'] }} is cancelled, every paid ticket is refunded in full. If the date or venue changes, your ticket stays valid, and you can ask for a refund within [Change refund window, e.g. 7 days] of our announcement.</p>
<h2>How to ask for a refund</h2>
<p>Email {{ $event['email'] ?? '[Contact email]' }}{{ $event['phone'] ? ' or call '.$event['phone'] : '' }} with your name and reference number.</p>
