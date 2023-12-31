<?php

declare(strict_types=1);

namespace App\EventSubscriber\Post;

use App\Event\Post\PostBeforePurgeEvent;
use App\Event\Post\PostDeletedEvent;
use App\Message\ActivityPub\Outbox\DeleteMessage;
use App\Message\Notification\PostDeletedNotificationMessage;
use App\Repository\PostRepository;
use App\Service\ActivityPub\Wrapper\DeleteWrapper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class PostDeleteSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly PostRepository      $postRepository,
        private readonly DeleteWrapper       $deleteWrapper,
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PostDeletedEvent::class => 'onPostDeleted',
            PostBeforePurgeEvent::class => 'onPostBeforePurge',
        ];
    }

    public function onPostDeleted(PostDeletedEvent $event)
    {
        $this->bus->dispatch(new PostDeletedNotificationMessage($event->post->getId()));
    }

    public function onPostBeforePurge(PostBeforePurgeEvent $event): void
    {
        $event->post->magazine->postCount = $this->postRepository->countPostsByMagazine($event->post->magazine) - 1;

        $this->bus->dispatch(new PostDeletedNotificationMessage($event->post->getId()));

        if (!$event->post->apId) {
            $this->bus->dispatch(
                new DeleteMessage(
                    $this->deleteWrapper->build($event->post, Uuid::v4()->toRfc4122()),
                    $event->post->user->getId(),
                    $event->post->magazine->getId()
                )
            );
        }
    }
}
