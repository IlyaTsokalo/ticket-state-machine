<?php

namespace App\Command;

use App\Contracts\EmailValidatorInterface;
use App\Contracts\TicketManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsCommand(
    name: 'app:ticket-state-machine',
    description: 'Interactively changes the state of a ticket based on user input.',
)]
class TicketStateMachineCommand extends Command
{
    public function __construct(protected TicketManagerInterface $ticketService, protected EmailValidatorInterface $emailValidator, protected WorkflowInterface $ticketStateMachine)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $ticket = $this->ticketService->getFirstRegisteredTicket();

        $io->info(sprintf('You have been provided with first available ticket, id %s', $ticket->getId()));

        $question = new Question('Please specify your email address, we will notify you about further updates on your ticket: ');

        $question->setValidator(function ($answer) {
            if (!$this->emailValidator->isValidEmail($answer)) {
                throw new \RuntimeException('The email provided is not a valid email address.');
            }

            return $answer;
        });

        $email = $io->askQuestion($question);

        $this->ticketService->updateEmailAddress($ticket->getId(), $email);

        $transitions = $this->ticketStateMachine->getEnabledTransitions($ticket);

        if (empty($transitions)) {
            $io->warning('There are no available transitions for the current state.');

            return Command::SUCCESS;
        }

        $transitionQuestion = new ChoiceQuestion(
            'Please select the transition you want to apply (defaults to first option):',
            array_map(fn ($object) => $object->getName(), $transitions)
        );
        $transitionQuestion->setErrorMessage('Transition %s does not exist.');

        $selectedTransition = $io->askQuestion($transitionQuestion);

        $newState = $this->ticketService->applyStateTransition($ticket, $selectedTransition)->getState();

        $io->success(sprintf('The ticket has been transitioned to the "%s" state.', $newState));

        return Command::SUCCESS;
    }
}
