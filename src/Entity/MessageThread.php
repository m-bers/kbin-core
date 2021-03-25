<?php declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\UpdatedAtTrait;
use App\Repository\MessageThreadRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=MessageThreadRepository::class)
 */
class MessageThread
{
    use UpdatedAtTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\JoinTable(name="message_thread_participants", joinColumns={
     *     @ORM\JoinColumn(name="message_thread_id", referencedColumnName="id")
     * }, inverseJoinColumns={
     *     @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     * @ORM\ManyToMany(targetEntity="User")
     */
    private Collection $participants;

    /**
     * @ORM\OneToMany(targetEntity="Message", mappedBy="thread",
     *     cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"createdAt": "DESC"})
     */
    private Collection $messages;


    public function __construct(User ...$participants)
    {
        $this->participants  = new ArrayCollection($participants);
        $this->messages      = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function getOtherParticipants(User $self): array
    {
        return $this->participants->filter(
            static function (User $user) use ($self) {
                return $user !== $self;
            }
        )->getValues();
    }

    public function userIsParticipant($user): bool
    {
        return $this->participants->contains($user);
    }

    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function getNewMessages(User $user): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('status', Message::STATUS_NEW))
            ->andWhere(Criteria::expr()->neq('sender', $user));

        return $this->messages->matching($criteria);
    }

    public function countNewMessages(User $user): int
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('status', Message::STATUS_NEW))
            ->andWhere(Criteria::expr()->neq('sender', $user));

        return $this->messages->matching($criteria)->count();
    }

    public function addMessage(Message $message): void
    {
        if (!$this->messages->contains($message)) {
            if (!$this->userIsParticipant($message->getSender())) {
                throw new \DomainException('Sender is not allowed to participate');
            }

            $this->messages->add($message);
        }
    }

    public function removeMessage(Message $message): void
    {
        $this->messages->removeElement($message);
    }

    public function getTitle(): string
    {
        $body      = $this->messages[0]->getBody();
        $firstLine = preg_replace('/^# |\R.*/', '', $body);

        if (grapheme_strlen($firstLine) <= 80) {
            return $firstLine;
        }

        return grapheme_substr($firstLine, 0, 80).'…';
    }
}