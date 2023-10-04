@if ($errors->any())
<div  class="alert alert-danger alert-fixed border-danger-dash alert-important alert-dimissable">

    <ul class="list-unstyled">
        @foreach ($errors->all() as $error)
        <li class="">{{ $error }}</li>
        @endforeach
    </ul>

    <div class='d-flex justify-content-end align-items-start'>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
</div>
@endif
@if (session()->has('message'))
<div x-data="{ show: {{ session()->has('message') }} }" x-show="show" x-init="setTimeout(() => show = false, 3500)" class="alert alert-success alert-fixed border-success-dash alert-important " id="notif">

    {{ session('message') }}

    <div class='d-flex justify-content-end align-items-start'>
        <button type="button" x-on:click="show = ! show " class="btn-close" aria-label="Close"></button>
    </div>
</div>
@endif
@if (session()->has('error'))
<div x-data="{ show: {{ session()->has('error') }} }" x-show="show" x-init="setTimeout(() => show = false, 5500)" class="alert alert-danger alert-fixed border-danger-dash alert-important " id="notif">

    {{ session('error') }}

    <div class='d-flex justify-content-end align-items-start'>
        <button type="button" x-on:click="show = ! show " class="btn-close" aria-label="Close"></button>
    </div>
</div>
@endif