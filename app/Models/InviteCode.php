<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Utils\Helper;

class InviteCode extends Model
{
    protected $table = 'v2_invite_code';
    protected $dateFormat = 'U';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'status' => 'boolean',
    ];

    const STATUS_UNUSED = 0;
    const STATUS_USED = 1;

    /**
     * 为指定用户生成一条未使用的邀请码
     */
    public static function generateForUser(User $user): self
    {
        $inviteCode = new self();
        $inviteCode->user_id = $user->id;
        $inviteCode->code = Helper::randomChar(8);
        $inviteCode->status = self::STATUS_UNUSED;
        $inviteCode->save();

        return $inviteCode;
    }
}
