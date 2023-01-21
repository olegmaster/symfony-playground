<?php

namespace App\Controller;

use App\Entity\File;
use App\Form\FileUploadType;
use App\Repository\FileRepository;
use App\Service\FileService;
use Aws\Result;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;


class S3ObjectsController extends AbstractController
{

    /**
     * @throws \Exception
     */
    #[Route('/aws_files/list', 'aws_files_list')]
    public function getList(
        Request         $request,
        S3Client        $s3Client,
        ManagerRegistry $doctrine,
        FileRepository  $fileRepository
    ): Response
    {
        $entityManager = $doctrine->getManager();

        /** @var array $files */
        $files = $fileRepository->getAll();

        $form = $this->createForm(FileUploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $fileToStoreInS3 */
            $fileToStoreInS3 = $form->get('file_binary')->getData();

            if ($fileToStoreInS3) {

                $originalFilename = pathinfo($fileToStoreInS3->getClientOriginalName(), PATHINFO_FILENAME);
                $uniqueObjectPrefix = rand(1, 100000);
                $extension = $fileToStoreInS3->getClientOriginalExtension();

                /** @var Result $result */
                $result = $s3Client->putObject([
                    'Body' => $fileToStoreInS3->getContent(),
                    'Key' => $originalFilename . '_' . $uniqueObjectPrefix . '.' . $extension,
                    'Bucket' => $this->getParameter('aws_bucket'),
                ]);

                if (!$result->get('ObjectURL') || !(preg_match('/https:\/\/(.)+\.amazonaws.com\/(.)+/', $result->get('ObjectURL')) === 1)) {
                    throw new \Exception('Unable to reach a valid s3 Object url from S3');
                }

                $file = new File();

                // need to decode object's url to store in DB.
                $file->setPath($result->get('ObjectURL'));
                $entityManager->persist($file);
                $entityManager->flush();

            }

            return $this->redirectToRoute('aws_files_list');
        }

        return $this->renderForm('index.html.twig', [
            'form' => $form,
            'files' => $files
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/aws_download_file/{id}', 'aws_download_file')]
    public function download(
        int            $id,
        S3Client       $s3Client,
        FileRepository $fileRepository,
        FileService    $fileService
    )
    {
        $file = $fileRepository->findOneById($id);

        if (!$file) {
            throw new \Exception('File record is not found in the database');
        }

        $key = $fileService->getKeyByS3ObjectUrl($file->getPath());

        try {

            $result = $s3Client->getObject([
                'Bucket' => $this->getParameter('aws_bucket'),
                'Key' => $key
            ]);

            $response = new Response($result['Body']);
            $response->headers->set('Content-Type', $result['ContentType']);
            $response->headers->set('Content-Disposition', 'attachment; filename=' . $key);
            return $response;

        } catch (S3Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }

        return $this->redirectToRoute('aws_files_list');

    }
}
