@extends('layouts.template')

@section('title', 'Shop')

@section('main')
    <h1>Shop -alternative listing</h1>
   @foreach($genres as $genre)
        <h2 style="text-transform: capitalize">{{ $genre->name }}</h2>
        @foreach($genre->records as $record)
            <p><a href="shop/{{ $record->id }}">{{$record->artist}} - {{$record->title}}</a> | Price: € {{$record->price}} | Stock: {{$record->stock}} </p>
        @endforeach
    @endforeach

@endsection
