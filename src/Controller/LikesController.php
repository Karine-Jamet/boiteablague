<?php
// src/Controller/UsersController.php
namespace App\Controller;

use App\Entity\Jokes;
use App\Entity\Likes;
use App\Entity\Users;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LikesController extends AbstractController
{
    /**
     * @Route("/like", name="like", methods={"POST"})
     */
    public function like(Request $request)
    {

        $userId = $request->query->get('userId');
        $jokeId = $request->query->get('jokeId');

        if(isset($userId) && isset($jokeId)){
            $user = $this->getDoctrine()
                ->getRepository(Users::class)
                ->find($userId);

            $joke = $this->getDoctrine()
                ->getRepository(Jokes::class)
                ->find($jokeId);

            $like = $this->getDoctrine()
                ->getRepository(Likes::class)
                ->findOneBy(['user' => $user, 'joke' => $joke ]);

            if(isset($like)){
                return new JsonResponse(
                    ['error' => 'Already vote']
                );
            }else{

                $entityManager = $this->getDoctrine()->getManager();

                $user = new Likes();
                $user->setUser($user);
                $user->setJoke($joke);

                $entityManager->persist($user);
                $entityManager->flush();


                return new JsonResponse(
                    ['status' => 'Done']
                );


            }

        }

        return new JsonResponse(
            ['error' => 'invalid parameteres']
        );
    }
}