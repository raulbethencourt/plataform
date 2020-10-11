<?php


namespace App\invitation;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * Class Invitation
 * This class manage email invitation for nex users
 * @package App\invitation
 */
class Invitation extends AbstractController
{
    /**
     * @var MailerInterface
     */
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @param $data
     * @param $classroom_id
     * @param $discipline
     * @throws TransportExceptionInterface
     */
    public function invite($data, $classroom_id, $discipline): void
    {
        // I creates a new email to send to the new user
        $email = (new TemplatedEmail())
            ->from(Address::fromString('Carpa <carpa@exemple.com>'))
            ->to($data->getEmail())
            ->subject('Invitation à Carpa')
            ->htmlTemplate('emails/invitation.html.twig')
            ->context(
                [
                    'data' => [
                        'type' => $data->getType(),
                        'name' => $data->getName(),
                        'classroom' => $classroom_id,
                        'discipline' => $discipline,
                    ],
                ]
            );
        $this->mailer->send($email);
    }
}