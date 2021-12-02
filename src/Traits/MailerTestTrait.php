<?php

namespace Selective\TestTrait\Traits;

use PHPUnit\Framework\Constraint\LogicalNot;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\Event\MessageEvents;
use Symfony\Component\Mailer\EventListener\MessageLoggerListener;
use Symfony\Component\Mailer\Test\Constraint\EmailCount;
use Symfony\Component\Mailer\Test\Constraint\EmailIsQueued;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Mime\Test\Constraint\EmailAddressContains;
use Symfony\Component\Mime\Test\Constraint\EmailAttachmentCount;
use Symfony\Component\Mime\Test\Constraint\EmailHasHeader;
use Symfony\Component\Mime\Test\Constraint\EmailHeaderSame;
use Symfony\Component\Mime\Test\Constraint\EmailHtmlBodyContains;
use Symfony\Component\Mime\Test\Constraint\EmailTextBodyContains;
use UnexpectedValueException;

/**
 * Array Test Trait.
 */
trait MailerTestTrait
{
    protected function assertEmailCount(int $count, string $transport = null, string $message = ''): void
    {
        $this->assertThat($this->getMessageMailerEvents(), new EmailCount($count, $transport), $message);
    }

    protected function assertQueuedEmailCount(int $count, string $transport = null, string $message = ''): void
    {
        $this->assertThat(
            $this->getMessageMailerEvents(),
            new EmailCount($count, $transport, true),
            $message
        );
    }

    protected function assertEmailIsQueued(MessageEvent $event, string $message = ''): void
    {
        $this->assertThat($event, new EmailIsQueued(), $message);
    }

    protected function assertEmailIsNotQueued(MessageEvent $event, string $message = ''): void
    {
        $this->assertThat($event, new LogicalNot(new EmailIsQueued()), $message);
    }

    protected function assertEmailAttachmentCount(RawMessage $email, int $count, string $message = ''): void
    {
        $this->assertThat($email, new EmailAttachmentCount($count), $message);
    }

    protected function assertEmailTextBodyContains(RawMessage $email, string $text, string $message = ''): void
    {
        $this->assertThat($email, new EmailTextBodyContains($text), $message);
    }

    protected function assertEmailTextBodyNotContains(RawMessage $email, string $text, string $message = ''): void
    {
        $this->assertThat($email, new LogicalNot(new EmailTextBodyContains($text)), $message);
    }

    protected function assertEmailHtmlBodyContains(RawMessage $email, string $text, string $message = ''): void
    {
        $this->assertThat($email, new EmailHtmlBodyContains($text), $message);
    }

    protected function assertEmailHtmlBodyNotContains(RawMessage $email, string $text, string $message = ''): void
    {
        $this->assertThat($email, new LogicalNot(new EmailHtmlBodyContains($text)), $message);
    }

    protected function assertEmailHasHeader(RawMessage $email, string $headerName, string $message = ''): void
    {
        $this->assertThat($email, new EmailHasHeader($headerName), $message);
    }

    protected function assertEmailNotHasHeader(RawMessage $email, string $headerName, string $message = ''): void
    {
        $this->assertThat($email, new LogicalNot(new EmailHasHeader($headerName)), $message);
    }

    protected function assertEmailHeaderSame(
        RawMessage $email,
        string $headerName,
        string $expectedValue,
        string $message = ''
    ): void {
        $this->assertThat($email, new EmailHeaderSame($headerName, $expectedValue), $message);
    }

    protected function assertEmailHeaderNotSame(
        RawMessage $email,
        string $headerName,
        string $expectedValue,
        string $message = ''
    ): void {
        $this->assertThat(
            $email,
            new LogicalNot(new EmailHeaderSame($headerName, $expectedValue)),
            $message
        );
    }

    protected function assertEmailAddressContains(
        RawMessage $email,
        string $headerName,
        string $expectedValue,
        string $message = ''
    ): void {
        $this->assertThat($email, new EmailAddressContains($headerName, $expectedValue), $message);
    }

    /**
     * @param string|null $transport
     *
     * @return MessageEvent[]
     */
    protected function getMailerEvents(string $transport = null): array
    {
        return $this->getMessageMailerEvents()->getEvents($transport);
    }

    protected function getMailerEvent(int $index = 0, string $transport = null): ?MessageEvent
    {
        return $this->getMailerEvents($transport)[$index] ?? null;
    }

    /**
     * @param string|null $transport
     *
     * @return RawMessage[]
     */
    protected function getMailerMessages(string $transport = null): array
    {
        return $this->getMessageMailerEvents()->getMessages($transport);
    }

    protected function findMailerMessage(int $index = 0, string $transport = null): ?RawMessage
    {
        return $this->getMailerMessages($transport)[$index] ?? null;
    }

    protected function getMailerMessage(int $index = 0, string $transport = null): RawMessage
    {
        $message = $this->findMailerMessage($index, $transport);

        if ($message === null) {
            throw new UnexpectedValueException('The Mailer message was not found.');
        }

        return $message;
    }

    protected function getMessageMailerEvents(): MessageEvents
    {
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $this->container->get(EventDispatcherInterface::class);

        /** @var EventSubscriberInterface[] $listeners */
        foreach ($dispatcher->getListeners() as $listeners) {
            /** @var array $listener */
            foreach ($listeners as $listener) {
                $listenerInstance = $listener[0];

                if (!$listenerInstance instanceof MessageLoggerListener) {
                    continue;
                }

                return $listenerInstance->getEvents();
            }
        }

        throw new RuntimeException('The Mailer event dispatcher must be enabled to make email assertions.');
    }
}
