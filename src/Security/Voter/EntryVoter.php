<?php declare(strict_types=1);

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use App\Entity\Entry;
use App\Entity\User;

class EntryVoter extends Voter
{
    const EDIT = 'edit';
    const PURGE = 'purge';
    const COMMENT = 'comment';
    const VOTE = 'vote';

    protected function supports(string $attribute, $subject): bool
    {
        return $subject instanceof Entry && \in_array($attribute, [self::EDIT, self::PURGE, self::COMMENT, self::VOTE], true);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {
            case self::EDIT:
                return $this->canEdit($subject, $user);
            case self::PURGE:
                return $this->canPurge($subject, $user);
            case self::COMMENT:
                return $this->canComment($subject, $user);
            case self::VOTE:
                return $this->canVote($subject, $user);
        }

        throw new \LogicException();
    }

    private function canEdit(Entry $entry, User $user): bool
    {
        if ($entry->getUser() === $user) {
            return true;
        }

        if ($entry->getMagazine()->userIsModerator($user)) {
            return true;
        }

        return false;
    }

    private function canPurge(Entry $entry, User $user): bool
    {
        if ($entry->getUser() === $user) {
            return true;
        }

        if ($entry->getMagazine()->userIsModerator($user)) {
            return true;
        }

        return false;
    }

    private function canComment($subject, User $user): bool
    {
        return true;
    }

    private function canVote($subject, User $user): bool
    {
        if ($subject->getUser() === $user) {
            return false;
        }

        return true;
    }
}
