@extends('layouts.app')

@section('content')
    <x-monitoring-dashboard
        :data="$data"
        :allSiswaHariIni="$allSiswaHariIni"
        :pengaturan="$pengaturan"
        :kpiGuru="$kpiGuru"
        :currentMonth="$currentMonth"
        :currentYear="$currentYear"
        :isAdminDashboard="true"
    />
@endsection
