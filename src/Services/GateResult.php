<?php

namespace Lastdino\ApprovalFlow\Services;

/**
 * ゲート（AND/OR）の評価結果を表すクラス
 */
class GateResult
{
    public function __construct(
        private int $approvalCount,
        private int $requiredApprovals,
        private bool $hasRejection,
        private bool $needAllApproval
    ) {}

    /**
     * 承認条件が満たされているかチェック
     */
    public function isApproved(): bool
    {
        if ($this->hasRejection) {
            return false;
        }

        return $this->needAllApproval
            ? $this->approvalCount === $this->requiredApprovals
            : $this->approvalCount > 0;
    }

    /**
     * 却下されているかチェック
     */
    public function isRejected(): bool
    {
        return $this->hasRejection;
    }

    /**
     * まだ承認待ちかチェック
     */
    public function isPending(): bool
    {
        return !$this->isApproved() && !$this->isRejected();
    }

    /**
     * 承認数を取得
     */
    public function getApprovalCount(): int
    {
        return $this->approvalCount;
    }

    /**
     * 必要承認数を取得
     */
    public function getRequiredApprovals(): int
    {
        return $this->requiredApprovals;
    }

    /**
     * 却下があるかを取得
     */
    public function hasRejection(): bool
    {
        return $this->hasRejection;
    }
}
