<?php

namespace App\Controller;

use App\Entity\File;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AWSFileController extends AbstractController
{

    #[Route('/aws_files/list')]
    public function filesList(): Response
    {
        $file = new File();
        $form = $this->createFormBuilder($file)
            ->add('file', FileType::class)
            ->getForm();
        return new Response('<h1>Hello</h1>');
    }

    public function store()
    {

    }
}
