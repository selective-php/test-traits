<?php

namespace Selective\TestTrait\Test;

use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Selective\TestTrait\Traits\ContainerTestTrait;
use Selective\TestTrait\Traits\MailerTestTrait;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\EventListener\EnvelopeListener;
use Symfony\Component\Mailer\EventListener\MessageListener;
use Symfony\Component\Mailer\EventListener\MessageLoggerListener;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;

final class MailerTestTraitTest extends TestCase
{
    use ContainerTestTrait;
    use MailerTestTrait;

    /**
     * Test.
     *
     * @throws TransportExceptionInterface
     *
     * @return void
     */
    public function testMailer(): void
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->addDefinitions(
            [
                // Mailer
                MailerInterface::class => function (ContainerInterface $container) {
                    return new Mailer($container->get(TransportInterface::class));
                },

                // Mailer transport
                TransportInterface::class => function (ContainerInterface $container) {
                    $eventDispatcher = $container->get(EventDispatcherInterface::class);

                    return Transport::fromDsn('null://user:pass@smtp.example.com:25', $eventDispatcher);
                },

                EventDispatcherInterface::class => function () {
                    $eventDispatcher = new EventDispatcher();
                    $eventDispatcher->addSubscriber(new MessageListener());
                    $eventDispatcher->addSubscriber(new EnvelopeListener());
                    $eventDispatcher->addSubscriber(new MessageLoggerListener());

                    return $eventDispatcher;
                },
            ]
        );

        $this->setUpContainer($containerBuilder->build());

        $mailer = $this->container->get(MailerInterface::class);

        // Send email
        $email = (new Email())
            ->from('hello@example.com')
            ->to('you@example.com')
            ->subject('Time for Symfony Mailer!')
            ->text('Sending emails is fun again!')
            ->html('<p>My HTML content</p>');

        $mailer->send($email);

        $this->assertEmailCount(1);
        $this->assertEmailTextBodyContains($this->getMailerMessage(), 'Sending emails is fun again!');
        $this->assertEmailHtmlBodyContains($this->getMailerMessage(), '<p>My HTML content</p>');
    }
}