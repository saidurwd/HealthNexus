<div class="tab-pane fade" id="instructions" role="tabpanel">
    <div class="card card-light card-outline">
        <div class="card-header">
            <h3 class="card-title">{{ __('Instructions') }}</h3>
        </div>
        <div class="card-body">
            @if($encounter->instructions->isNotEmpty())
                @foreach($encounter->instructions as $instruction)
                    <div class="callout callout-light">
                        <h5>{{ ucfirst($instruction->instruction_type) }}</h5>
                        <p class="mb-0">{{ $instruction->content }}</p>
                    </div>
                @endforeach
            @else
                <p class="text-muted">{{ __('No instructions recorded.') }}</p>
            @endif

            @if(!$encounter->locked_at)
                <form method="POST" action="{{ route('admin.encounters.instructions.store', $encounter) }}" class="mt-3">
                    @csrf
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>{{ __('Type') }}</label>
                                <select name="instruction_type" class="form-control" required>
                                    <option value="medication">{{ __('Medication') }}</option>
                                    <option value="diet">{{ __('Diet') }}</option>
                                    <option value="lifestyle">{{ __('Lifestyle') }}</option>
                                    <option value="warning">{{ __('Warning Signs') }}</option>
                                    <option value="follow_up">{{ __('Follow-up') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="form-group">
                                <label>{{ __('Content') }}</label>
                                <textarea name="content" class="form-control" rows="2" required></textarea>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-2">{{ __('Add Instruction') }}</button>
                </form>
            @endif
        </div>
    </div>
</div>
