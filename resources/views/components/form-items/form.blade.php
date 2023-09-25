<form {{ $attributes }} enctype="multipart/form-data">
    @csrf
    {{$slot}}
</form>