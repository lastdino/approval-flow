<?php

namespace Lastdino\ApprovalFlow\Helpers;

class UserDisplayHelper
{
    /**
     * ユーザーの表示名を取得する
     */
    public static function getDisplayName($user): string
    {
        if (!$user) {
            return '';
        }

        // メインの表示名カラムを取得
        $mainColumn = config('approval-flow.user.display_name_column', 'name');

        // メインカラムが存在する場合はそれを返す
        if (isset($user[$mainColumn]) && !empty($user[$mainColumn])) {
            return $user[$mainColumn];
        }

        // フォールバックカラムを順番に試す
        $fallbackColumns = config('approval-flow.user.fallback_columns', ['Full_name', 'full_name', 'display_name']);

        foreach ($fallbackColumns as $column) {
            if (isset($user[$column]) && !empty($user[$column])) {
                return $user[$column];
            }
        }

        // すべて失敗した場合は、IDまたは空文字を返す
        return $user->id ?? '';
    }
}
