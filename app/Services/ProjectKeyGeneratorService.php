<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\DB;

class ProjectKeyGeneratorService
{
    /**
     * Generates a unique, concurrency-safe project key.
     * Default format: PRJ-000001
     */
    public function generateUniqueKey(string $prefix = 'PRJ-'): string
    {
        return DB::transaction(function () use ($prefix) {
            // Find max existing key matching prefix
            $maxKey = Project::withTrashed()
                ->where('project_key', 'LIKE', "{$prefix}%")
                ->lockForUpdate()
                ->orderByRaw('CAST(SUBSTRING(project_key, ?) AS UNSIGNED) DESC', [strlen($prefix) + 1])
                ->value('project_key');

            if ($maxKey) {
                $number = (int) substr($maxKey, strlen($prefix)) + 1;
            } else {
                $number = 1;
            }

            $key = $prefix.str_pad((string) $number, 6, '0', STR_PAD_LEFT);

            // Double check uniqueness loop for safety
            while (Project::withTrashed()->where('project_key', $key)->exists()) {
                $number++;
                $key = $prefix.str_pad((string) $number, 6, '0', STR_PAD_LEFT);
            }

            return $key;
        });
    }
}
