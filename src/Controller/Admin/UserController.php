<?php declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AbstractController;

class UserController extends AbstractController
{
    public function __construct()
    {
    }

    public function __invoke()
    {
        throw $this->createAccessDeniedException();
    }
}
