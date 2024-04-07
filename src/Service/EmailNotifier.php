<?php

namespace App\Service;

use App\Contracts\EmailNotifierInterface;
use App\Contracts\TicketInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailNotifier implements EmailNotifierInterface
{
    public function __construct(
        protected MailerInterface $mailer,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function sendEmail(TicketInterface $ticket, string $transition, string $toState): void
    {
        $emailAddress = $ticket->getEmail();

        if (null === $emailAddress) {
            $this->logger->error('Missing email address , you will not receive email notification');

            return;
        }

        $message = (new Email())
            ->from('helloworld@test.com')
            ->to($emailAddress)
            ->subject(sprintf('Your ticket transitioned to %s state', $toState))
            ->text(sprintf('Hello, your ticket #%s has been moved to the %s state via the %s transition.', $ticket->getId(), $toState, $transition));

        try {
            $this->mailer->send($message);
            $this->logger->info('Email sent for workflow transition', ['transition' => $transition, 'to' => $toState, 'email' => $emailAddress]);
        } catch (\Exception $exception) {
            $this->logger->error('Failed to send email for workflow transition', ['error' => $exception->getMessage()]);
        }
    }
}
