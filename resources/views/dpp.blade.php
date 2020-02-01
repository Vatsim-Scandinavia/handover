@extends('layouts.app')

@section('content')

        <h3>Handover</h3>
        <p>Centralised Authentication Service</p>

        <hr>

        <h5>Privacy Policy</h5>
        <p>In order to log into our services, we require you to first accept our privacy policy and grant us permission to process your data.</p>
        <p>Last update: {{ env('APP_PP_DATE') }}, <a class="text-info" target="_blank" href="{{ env('APP_PP_URL') }}">read the full privacy policy.</a></p>

        <div class="card mb-4 border-left-danger">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Simplified Data Protection Policy</h6>
            </div>
            <div class="card-body text-left">
                <div class="pp-bullet">
                    <i class="fas fa-database"></i>
                    We get your data from VATSIM CERT.
                </div>
                <div class="pp-bullet">
                    <i class="fas fa-cogs"></i>
                    We process your data in order to provide records of trainings, endorsements, member lists and to contact our users and improve our services.
                </div>
                <div class="pp-bullet">
                    <i class="fas fa-handshake-alt"></i>
                    If you log into the following third-party services, we will share the data with the following services: Discord.
                </div>
                <div class="pp-bullet">
                    <i class="fas fa-shield"></i>
                    We process the personal data of our members confidently, and we only share it with third-parties for authentication purposes.
                </div>
                <div class="pp-bullet">
                    <i class="fas fa-globe-europe"></i>
                    All first-party data is processed on our own servers within the EU and European legislation.
                </div>
                <div class="pp-bullet">
                    <i class="fas fa-save"></i>
                    We store your data for the whole duration of your membership in the vACC.
                </div>
                <div class="pp-bullet">
                    <i class="fas fa-eye"></i>
                    You have the right to inquire access, rectification and erasure of your data.
                </div>
                <div class="pp-bullet">
                    <i class="fas fa-undo"></i>
                    You have the right to withdraw your consent at any time.
                </div>
                <div class="pp-bullet">
                    <i class="fas fa-envelope"></i>
                    
                For questions or inquires, contact our Data Protection Officer at <a href="mailto:{{ env('APP_DPO_MAIL') }}">{{ env('APP_DPO_MAIL') }}</a>
                </div>
            </div>
        </div>

        <a href="{{route('login')}}" class="btn btn btn-primary">Yes, I accept</a>

@endsection