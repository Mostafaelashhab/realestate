@extends('layouts.app')
@section('bare', '1')
@section('title', 'الصفحة مش موجودة')
@section('content')
    <x-error-page code="404" icon="search" color="from-rail-500 to-rail-700"
        title="الصفحة مش موجودة"
        message="الرابط اللي دوّرت عليه مش موجود أو اتنقل لمكان تاني." />
@endsection
