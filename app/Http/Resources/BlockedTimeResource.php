<?php

namespace App\Http\Resources;

use App\Models\BlockedTime;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BlockedTime */
class BlockedTimeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'starts_at' => $this->starts_at->toIso8601String(),
            'ends_at' => $this->ends_at->toIso8601String(),
            'reason' => $this->reason,
            'notes' => $this->when($this->notes !== null, $this->notes),
        ];
    }
}
