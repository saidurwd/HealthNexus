<div class="tab-pane fade" id="summary" role="tabpanel">
    <div class="card card-outline card-secondary">
        <div class="card-header">
            <h3 class="card-title">{{ __('Encounter Summary') }}</h3>
        </div>
        <div class="card-body">
            <h5>{{ __('Chief Complaint') }}</h5>
            @if($encounter->complaints->isNotEmpty())
                <ul>
                    @foreach($encounter->complaints as $complaint)
                        <li>{{ $complaint->complaint }} ({{ $complaint->duration }} {{ $complaint->duration_unit }})</li>
                    @endforeach
                </ul>
            @else
                <p class="text-muted">{{ __('None') }}</p>
            @endif

            <h5 class="mt-3">{{ __('Diagnoses') }}</h5>
            @if($encounter->diagnoses->isNotEmpty())
                <ul>
                    @foreach($encounter->diagnoses as $diagnosis)
                        <li>{{ $diagnosis->description }} ({{ $diagnosis->code }}) - {{ $diagnosis->status }}</li>
                    @endforeach
                </ul>
            @else
                <p class="text-muted">{{ __('None') }}</p>
            @endif

            <h5 class="mt-3">{{ __('Prescriptions') }}</h5>
            @if($encounter->prescriptions->isNotEmpty())
                <ul>
                    @foreach($encounter->prescriptions as $prescription)
                        <li>{{ $prescription->prescription_no }}</li>
                    @endforeach
                </ul>
            @else
                <p class="text-muted">{{ __('None') }}</p>
            @endif

            <h5 class="mt-3">{{ __('Referrals') }}</h5>
            @if($encounter->referrals->isNotEmpty())
                <ul>
                    @foreach($encounter->referrals as $referral)
                        <li>{{ $referral->referral_type }} - {{ $referral->referred_to }} ({{ $referral->status }})</li>
                    @endforeach
                </ul>
            @else
                <p class="text-muted">{{ __('None') }}</p>
            @endif

            <h5 class="mt-3">{{ __('Instructions') }}</h5>
            @if($encounter->instructions->isNotEmpty())
                <ul>
                    @foreach($encounter->instructions as $instruction)
                        <li>{{ ucfirst($instruction->instruction_type) }}: {{ $instruction->content }}</li>
                    @endforeach
                </ul>
            @else
                <p class="text-muted">{{ __('None') }}</p>
            @endif
        </div>
    </div>
</div>
