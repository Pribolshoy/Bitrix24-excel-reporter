<?php


namespace App\Service;


use Psr\Container\ContainerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;

class MailSender
{
    protected $mailer;

    protected $container;

    protected $filename;

    public function __construct(ContainerInterface $container, \Swift_Mailer $Mailer)
    {
        $this->container = $container;
        $this->mailer = $Mailer;
    }

    public function run() {
        if (!$this->filename) {
            throw new Exception('Не задано название файла xlsx!');
        }

        $to = explode(',', $this->container->getParameter('app.emails_to'));
        $subject = $this->container->getParameter('app.email_subject');

        $message = (new \Swift_Message($subject))
            ->setFrom('report@kraust.ru')
            ->setTo($to)
            ->setBody(
                $this->container->get('twig')->render(
                    'emails/index.html.twig', [
                    'file_url' => $this->getFileUrl(),
                    'filename' => $this->filename,
                ]),
                'text/html'
            );

        // И отправляем его с помощью объекта Swift_Mailer
        $this->mailer->send($message);
    }

    public function getFileUrl() {
        $file_url = 'https://' . $_SERVER['HTTP_HOST'] . DIRECTORY_SEPARATOR .
            'files' . DIRECTORY_SEPARATOR .
            $this->filename;

        return $file_url;
    }

    public function setFilename($filename) {
        $this->filename = $filename;
    }
}