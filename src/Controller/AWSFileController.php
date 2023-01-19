<?php

namespace App\Controller;

use App\Entity\File;
use App\Form\FileUploadType;
use Aws\S3\S3Client;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;


class AWSFileController extends AbstractController
{

    #[Route('/aws_files/list', 'aws_files_list')]
    public function filesList(Request $request, S3Client $s3Client): Response
    {
        $file = new File();
        $form = $this->createForm(FileUploadType::class, $file);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $brochureFile */
            $brochureFile = $form->get('file_binary')->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($brochureFile) {

                $originalFilename = pathinfo($brochureFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
    //var_dump($this->getParameter('aws_key'));exit;
                $s3Client->putObject([
                    'Body' => $brochureFile,
                    'Key' => $this->getParameter('aws_key'),
                    'Secret' => $this->getParameter('aws_secret'),
                    'Bucket' => $this->getParameter('aws_bucket'),
                ]);

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $file->setPath('aws_files_list');
            }

            // ... persist the $product variable or any other work

            return $this->redirectToRoute('aws_files_list');
        }

        return $this->renderForm('index.html.twig', [
            'form' => $form,
            'a' => 22
        ]);
    }

}
