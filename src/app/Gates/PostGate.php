<?php

namespace App\Gates;

use App\Models\User;
use App\Models\Post;

class PostGate
{
    // adminとeditorが作成と編集可能
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'editor'], true);
    }

    // adminと作成者が削除可能
    public function destroy(User $user, Post $post): bool
    {
        return $post->user_id === $user->id || $user->role === 'admin';
    }
}
