<?php

namespace App\Services\Nursing;

use App\Models\File;
use App\Models\Nursing\NursingEpisode;
use App\Models\Nursing\NursingWoundAssessment;
use App\Models\User;
use App\Services\FileService;
use Illuminate\Http\UploadedFile;

class NursingWoundAssessmentService
{
    public function __construct(private readonly FileService $files) {}

    public function record(NursingEpisode $episode, array $data, User $user): NursingWoundAssessment
    {
        return NursingWoundAssessment::create([
            ...$data,
            'company_id' => $episode->company_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'admission_id' => $episode->admission_id,
            'patient_id' => $episode->patient_id,
            'assessed_at' => $data['assessed_at'] ?? now(),
            'assessed_by' => $user->id,
        ]);
    }

    public function attachImage(NursingWoundAssessment $assessment, UploadedFile $upload, User $user): File
    {
        return $this->files->store($upload, $assessment, $user);
    }
}
