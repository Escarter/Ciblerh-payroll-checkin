@if ($errors->any())
<div class="alert alert-danger alert-fixed border-danger-dash alert-important alert-dimissable ">
    <div class='d-flex justify-content-between align-items-start'>

        <ul class="list-unstyled">
            @foreach ($errors->all() as $error)
            <li class="px-n4">{{ $error }}</li>
            @endforeach
        </ul>


        <div class='d-flex justify-content-end align-items-start'>
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
</div>
@endif