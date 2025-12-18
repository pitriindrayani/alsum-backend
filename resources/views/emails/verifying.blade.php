@component('mail::message')
Selamat Datang

Ini adalah pesan verifikasi untuk dapat mengakses SDC Apps.

@component('mail::button', ['url' => $url])
Verifikasikan Akun Anda
@endcomponent

Terima kasih,<br>
{{ config('app.name') }}
@endcomponent