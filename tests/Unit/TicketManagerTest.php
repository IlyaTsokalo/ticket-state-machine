<?php

namespace App\Tests\Unit;

use App\Entity\Ticket;
use App\Repository\TicketRepository;
use App\Service\TicketManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;
use Symfony\Component\Workflow\WorkflowInterface;

class TicketManagerTest extends TestCase
{
    private WorkflowInterface $workflow;
    private LoggerInterface|MockObject $logger;
    private TicketRepository|MockObject $repository;
    private EntityManagerInterface|MockObject $entityManager;
    private TicketManager $ticketManager;

    protected function setUp(): void
    {
        $places = ['registered', 'paid', 'cancelled'];
        $transitions = [
            new Transition('to_pay', 'registered', 'paid'),
            new Transition('to_cancel', 'registered', 'cancelled'),
            new Transition('to_cancel', 'paid', 'cancelled'), // Assuming "to_cancel" can also go from "paid" to "cancelled"
        ];
        $definition = new Definition($places, $transitions, 'registered');
        $markingStore = new MethodMarkingStore(true, 'state');

        $this->workflow = new Workflow($definition, $markingStore);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->repository = $this->createMock(TicketRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->ticketManager = new TicketManager(
            $this->workflow,
            $this->logger,
            $this->repository,
            $this->entityManager
        );
    }

    /**
     * @dataProvider stateTransitionProvider
     */
    public function testApplyStateTransitionSuccessfullyAppliesTransition($initialState, $transition, $expectedState): void
    {
        $ticket = new Ticket();
        $ticket->setState($initialState);

        $this->ticketManager->applyStateTransition($ticket, $transition);

        $this->assertSame($expectedState, $ticket->getState(), 'The ticket state should transition correctly.');
    }

    public static function stateTransitionProvider(): array
    {
        return [
            ['registered', 'to_pay', 'paid'],
            ['registered', 'to_cancel', 'cancelled'],
            ['paid', 'to_cancel', 'cancelled'],
        ];
    }

    public function testUpdateStateUpdatesTicketState(): void
    {
        $ticket = new Ticket();
        $ticketId = 1;
        $newState = 'cancelled';

        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->equalTo($ticketId))
            ->willReturn($ticket);

        $this->entityManager->expects($this->once())->method('persist')->with($ticket);
        $this->entityManager->expects($this->once())->method('flush');

        $this->ticketManager->updateState($ticketId, $newState);

        $this->assertEquals($newState, $ticket->getState(), "Ticket state should be updated to 'cancelled'.");
    }

    public function testUpdateEmailAddressUpdatesTicketEmail(): void
    {
        $ticketId = 1;
        $newEmail = 'test@example.com';
        $ticket = new Ticket();

        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->equalTo($ticketId))
            ->willReturn($ticket);

        $this->entityManager->expects($this->once())->method('persist')->with($this->equalTo($ticket));
        $this->entityManager->expects($this->once())->method('flush');

        $this->ticketManager->updateEmailAddress($ticketId, $newEmail);

        $this->assertEquals($newEmail, $ticket->getEmail(), "Email should be updated to {$newEmail}.");
    }
}
