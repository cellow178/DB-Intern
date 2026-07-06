<?php

    namespace App\Models;
    use Illuminate\Database\Eloquent\Model;

    class Mission extends Model
    {
        protected $fillable = [
            'content',
            'order',
            'status_code',
            'created_by',
            'updated_by'
        ];

        protected $casts = [
            'status_code' => 'boolean',
        ];

        public function createdBy()
        {
            return $this->belongsTo(User::class, 'created_by');
        }

        public function updatedBy()
        {
            return $this->belongsTo(User::class, 'updated_by');
        }
    }