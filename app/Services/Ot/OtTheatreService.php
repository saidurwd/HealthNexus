<?php

namespace App\Services\Ot;

use App\Models\Ot\OtComplex;
use App\Models\Ot\OtTheatre;

class OtTheatreService
{
    public function createComplex(array $data): OtComplex
    {
        return OtComplex::create($data);
    }

    public function create(array $data): OtTheatre
    {
        $capabilities = $data['capabilities'] ?? [];
        unset($data['capabilities']);

        $theatre = OtTheatre::create($data);

        foreach (array_filter($capabilities) as $capability) {
            $theatre->capabilities()->create(['capability' => $capability]);
        }

        return $theatre;
    }

    public function update(OtTheatre $theatre, array $data): OtTheatre
    {
        $theatre->update($data);

        return $theatre->refresh();
    }
}
