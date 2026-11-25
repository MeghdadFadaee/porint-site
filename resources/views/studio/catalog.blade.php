@extends('studio.layout')
@section('content')
    <section class="shell inner-page">
        <div class="section-heading"><h1>{{ __('studio.titles.catalog') }}</h1></div>
        @include('studio.collection-grid')
    </section>
@endsection
