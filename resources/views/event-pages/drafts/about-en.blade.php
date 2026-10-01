<h2>{{ $event['name'] }}</h2>
<p>[A short description of the event: who it is for and what happens there.]</p>
<h2>The organiser</h2>
<p>{{ $event['name'] }} is organised by [Company legal name], Commercial Registration [Commercial registration number], Tax ID [Tax ID], [Business address].</p>
@if($event['venue'])
<h2>Where</h2>
<p>{{ $event['venue'] }}{{ $event['address'] ? ', '.$event['address'] : '' }}</p>
@endif
