<?php

namespace App\Security\Voter;

use App\Entity\SupportMessage;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class SupportThreadVoter extends Voter
{
    public const VIEW = 'SUPPORT_THREAD_VIEW';
    public const REPLY = 'SUPPORT_THREAD_REPLY';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::REPLY], true)
            && $subject instanceof SupportMessage;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var SupportMessage $thread */
        $thread = $subject;

        if (\in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        if ($thread->getType() === SupportMessage::TYPE_CUSTOMER) {
            if ($thread->getCreatedBy() && $thread->getCreatedBy()->getId() === $user->getId()) {
                return $attribute === self::VIEW
                    || ($attribute === self::REPLY && $thread->getStatus() !== SupportMessage::STATUS_CLOSED);
            }
        }

        if ($user->isStaff() && $user->getSupportDepartment()) {
            $dept = $user->getSupportDepartment();

            $canSee =
                ($thread->getDepartment()?->getId() === $dept->getId())
                || ($thread->getFromDepartment()?->getId() === $dept->getId());

            if (!$canSee) {
                return false;
            }

            return $attribute === self::VIEW || $attribute === self::REPLY;
        }

        return false;
    }
}
