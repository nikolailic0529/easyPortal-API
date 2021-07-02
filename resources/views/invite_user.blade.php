@component('mail::message')
# IT Asset Hub

Your have been invited to use IT Asset Hub dashboard.

@component('mail::button', ['url' => $url])
Join
@endcomponent

Thanks,<br>
Support Team<br>
IT Asset Hub
@endcomponent