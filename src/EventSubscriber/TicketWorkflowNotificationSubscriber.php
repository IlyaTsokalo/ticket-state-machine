<?php

namespace App\EventSubscriber;

use App\Contracts\EmailNotifierInterface;
use App\Contracts\TicketInterface;
use App\Contracts\TicketManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\WorkflowEvents;

readonly class TicketWorkflowNotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected EmailNotifierInterface $emailNotifier,
        protected TicketManagerInterface $ticketService,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function onWorkflowCompleted(CompletedEvent $event): void
    {
        $subject = $event->getSubject();

        $transition = $event->getTransition()->getName();
        $toState = current($event->getTransition()->getTos());

        if (!$subject instanceof TicketInterface) {
            throw new \Exception('Unexpected subject');
        }

        $this->emailNotifier->sendEmail($subject, $transition, $toState);
        $this->ticketService->updateState($subject->getId(), $toState);
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            WorkflowEvents::COMPLETED => 'onWorkflowCompleted',
        ];
    }
}
