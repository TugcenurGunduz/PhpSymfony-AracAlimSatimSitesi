<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class SampleController extends AbstractController
{
    /**
     * @Route("/sample", name="sample")
     */
    public function index()
    {
        $name="Karabuk Umiversity";
        return $this->render('sample/index.html.twig', [
            'name' => $name,
        ]);
    }
}
