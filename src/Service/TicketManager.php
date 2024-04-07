<?php

namespace App\Service;

use App\Contracts\TicketInterface;
use App\Contracts\TicketManagerInterface;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\Workflow\WorkflowInterface;

class TicketManager implements TicketManagerInterface
{
    use MailerAssertionsTrait;

    public function __construct(
        protected WorkflowInterface $ticketStateMachine,
        protected LoggerInterface $logger,
        protected TicketRepository $ticketRepository,
        protected EntityManagerInterface $entityManager,
    ) {
    }

    public function applyStateTransition(TicketInterface $ticket, string $transition): TicketInterface
    {
        try {
            $this->ticketStateMachine->apply($ticket, $transition);
        } catch (\LogicException $exception) {
            $this->logger->error('Failed to update ticket workflow', ['error' => $exception->getMessage()]);
        }

        return $ticket;
    }

    public function getFirstRegisteredTicket(): TicketInterface
    {
        $initialState = $this->getInitialState();

        return $this->ticketRepository->findOneBy(['state' => $initialState]);
    }

    public function updateEmailAddress(int $id, string $emailAddress): void
    {
        $this->updateTicket($id, function (TicketInterface $ticket) use ($emailAddress) {
            $ticket->setEmail($emailAddress);
        });
    }

    public function updateState(int $id, string $state): void
    {
        $this->updateTicket($id, function (TicketInterface $ticket) use ($state) {
            $ticket->setState($state);
        });
    }

    protected function updateTicket(int $id, callable $updateAction): void
    {
        $ticket = $this->ticketRepository->find($id);

        if (!$ticket) {
            $this->logger->error('No ticket found');

            return;
        }

        $updateAction($ticket);

        $this->entityManager->persist($ticket);
        $this->entityManager->flush();
    }

    public function getInitialState(): string
    {
        return current($this->ticketStateMachine->getDefinition()->getInitialPlaces());
    }
}
