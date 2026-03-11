<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            $model->logActivity('created');
        });

        static::updated(function ($model) {
            $model->logActivity('updated');
        });

        static::deleting(function ($model) {
            $model->logActivity('deleted');
        });
    }

    protected function getActivityLogExcludedFields(): array
    {
        return ['id', 'created_at', 'updated_at', 'deleted_at', 'remember_token', 'password'];
    }

    protected function getActivityLogLabel(): string
    {
        if (method_exists($this, 'getFullNameAttribute') || method_exists($this, 'fullName')) {
            try { return $this->full_name ?? "#{$this->getKey()}"; } catch (\Throwable $e) {}
        }
        if (isset($this->attributes['title']) || array_key_exists('title', $this->attributes ?? [])) {
            return $this->title ?? "#{$this->getKey()}";
        }
        if (isset($this->attributes['name']) || array_key_exists('name', $this->attributes ?? [])) {
            return $this->name ?? "#{$this->getKey()}";
        }
        if (method_exists($this, 'getPrimaryNameAttribute')) {
            try { return $this->primary_name ?? "#{$this->getKey()}"; } catch (\Throwable $e) {}
        }
        if (isset($this->attributes['contract_number'])) {
            return $this->contract_number;
        }
        if (isset($this->attributes['invoice_number'])) {
            return $this->invoice_number;
        }
        if (isset($this->attributes['description'])) {
            return Str::limit($this->description ?? '', 50);
        }

        return "#{$this->getKey()}";
    }

    protected function logActivity(string $action): void
    {
        $userId = Auth::id();
        $userName = Auth::user()?->name ?? 'System';
        $excluded = $this->getActivityLogExcludedFields();
        $label = $this->getActivityLogLabel();

        if ($action === 'created') {
            $attributes = $this->getAttributes();

            foreach ($attributes as $field => $value) {
                if (in_array($field, $excluded) || $value === null || $value === '') {
                    continue;
                }

                if (is_array($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                }

                ActivityLog::create([
                    'user_id' => $userId,
                    'user_name' => $userName,
                    'model_type' => get_class($this),
                    'model_id' => $this->getKey(),
                    'model_label' => Str::limit($label, 250),
                    'action' => 'created',
                    'field' => $field,
                    'old_value' => null,
                    'new_value' => (string) $value,
                ]);
            }
        } elseif ($action === 'updated') {
            $dirty = $this->getDirty();
            $original = $this->getOriginal();

            foreach ($dirty as $field => $newValue) {
                if (in_array($field, $excluded)) {
                    continue;
                }

                $oldValue = $original[$field] ?? null;

                if (is_array($newValue)) {
                    $newValue = json_encode($newValue, JSON_UNESCAPED_UNICODE);
                }
                if (is_array($oldValue)) {
                    $oldValue = json_encode($oldValue, JSON_UNESCAPED_UNICODE);
                }

                if ((string) ($oldValue ?? '') === (string) ($newValue ?? '')) {
                    continue;
                }

                ActivityLog::create([
                    'user_id' => $userId,
                    'user_name' => $userName,
                    'model_type' => get_class($this),
                    'model_id' => $this->getKey(),
                    'model_label' => Str::limit($label, 250),
                    'action' => 'updated',
                    'field' => $field,
                    'old_value' => $oldValue !== null ? (string) $oldValue : null,
                    'new_value' => $newValue !== null ? (string) $newValue : null,
                ]);
            }
        } elseif ($action === 'deleted') {
            ActivityLog::create([
                'user_id' => $userId,
                'user_name' => $userName,
                'model_type' => get_class($this),
                'model_id' => $this->getKey(),
                'model_label' => Str::limit($label, 250),
                'action' => 'deleted',
                'field' => null,
                'old_value' => null,
                'new_value' => null,
            ]);
        }
    }
}
