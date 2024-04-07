<?php

namespace App\Tests\Unit;

use App\Entity\Ticket;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowEvents;

class EmailNotifierTest extends WebTestCase
{
    public function testMailIsSentAndContentIsOk(): void
    {
        $container = static::getContainer();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $ticket = new Ticket(); // Ensure this is an actual Ticket entity, properly instantiated.
        $ticket->setEmail('test@gmail.com');
        $ticket->setState('registered');
        $entityManager->persist($ticket);
        $entityManager->flush();

        $transitionName = 'to_pay';
        $finalState = 'paid';
        $transition = new Transition($transitionName, 'registered', $finalState);

        $workflow = $container->get('state_machine.ticket');

        $event = new CompletedEvent($ticket, new Marking(['registered' => 1]), $transition, $workflow);

        $eventDispatcher = $container->get('event_dispatcher');

        $eventDispatcher->dispatch($event, WorkflowEvents::COMPLETED);
        $this->assertEmailCount(1);

        $email = $this->getMailerMessage();

        $this->assertEmailTextBodyContains($email, sprintf('Hello, your ticket #%s has been moved to the %s state via the %s transition.', $ticket->getId(), $finalState, $transitionName));
    }
}
