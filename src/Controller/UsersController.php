<?php
// src/Controller/UsersController.php
namespace App\Controller;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;



class UsersController extends AbstractController
{
    /**
     * @Route("/users/list", name="users_list")
     */
    public function list()
    {

        $users =$this->getDoctrine()
            ->getRepository(Users::class)
            ->findAll();

        $encoders = [new XmlEncoder(), new JsonEncoder()];

        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                return $object->getId();
            },
        ];

        $normalizers = [new ObjectNormalizer(null, null, null, null, null, null, $defaultContext)];
        $serializer = new Serializer($normalizers, $encoders);
        $json_users = $serializer->serialize($users, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => [ 'likes']]);

        return new Response(
            $json_users
        );

    }


    /**
     * @Route("/users/login", name="users_login", methods={"POST"})
     */
    public function login(Request $request, ValidatorInterface $validator)
    {

        $contentRAW = $request->getContent();
        $content = json_decode($contentRAW, true);
        $mail = $content['mail'];


        try{
            $user = $this->getDoctrine()
                ->getRepository(Users::class)
                ->findOneBy(['mail' => $mail ]);

        } catch (\Exception $e) {
            return new Response((string)$e, 500);
        }



        if(isset($user)) {

            return new JsonResponse(
                ['id' => $user->getId()]
            );

        }else{

            return new JsonResponse(
                ['error' => 'This user doesn\'t exist']
            );

        }


    }


    /**
     * @Route("/users/add", name="users_add", methods={"POST"})
     */
    public function add(Request $request, ValidatorInterface $validator)
    {

        $contentRAW = $request->getContent();
        $content = json_decode($contentRAW, true);
        $name = $content['name'];
        $firstname = $content['firstname'];
        $mail = $content['mail'];

        $entityManager = $this->getDoctrine()->getManager();

        if(empty($mail) or ampty($name) or empty($firstname)){
            return new JsonResponse(
                ['code' => 0,
                    'error' => 'Invalid variables']
            );
        }


        if( $this->getDoctrine()
            ->getRepository(Users::class)
            ->findOneBy(['mail' => $mail ])) {

            return new JsonResponse(
                ['code' => 0,
                 'error' => 'Already exist']
            );

        }

        $user = new Users();
        $user->setName($name);
        $user->setFirstname($firstname);
        $user->setMail($mail);

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return new Response((string) $errors, 400);
        }

        $entityManager->persist($user);
        $entityManager->flush();

        $userId = $user->getId();


        return new JsonResponse(
            ['id' => $userId]
        );
    }

}