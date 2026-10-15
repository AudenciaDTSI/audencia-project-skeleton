<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AccountController extends AbstractController
{
    #[Route('/mon-compte', name: 'app_account', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('account/index.html.twig');
    }
}
