<div class="tab-pane fade" id="examination" role="tabpanel">
    <div class="card card-success card-outline">
        <div class="card-header">
            <h3 class="card-title">{{ __('Clinical Examination') }}</h3>
        </div>
        <div class="card-body">
            @if($encounter->examinations->isNotEmpty())
                @foreach($encounter->examinations as $exam)
                    <div class="callout callout-info">
                        <h5>{{ $exam->section_name }}</h5>
                        <p class="mb-0">{{ $exam->findings }}</p>
                        @if($exam->notes)
                            <small class="text-muted">{{ $exam->notes }}</small>
                        @endif
                    </div>
                @endforeach
            @else
                <p class="text-muted">{{ __('No examination recorded.') }}</p>
            @endif

            @if(!$encounter->locked_at)
                <form method="POST" action="{{ route('admin.encounters.examinations.store', $encounter) }}" class="mt-3">
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('Section') }}</label>
                                <input type="text" name="section_name" class="form-control" required placeholder="{{ __('General, CVS, RS, etc.') }}">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>{{ __('Findings') }}</label>
                                <textarea name="findings" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mt-2">
                        <label>{{ __('Notes') }}</label>
                        <textarea name="notes" class="form-control" rows="1"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary mt-2">{{ __('Add Examination') }}</button>
                </form>
            @endif
        </div>
    </div>
</div>
