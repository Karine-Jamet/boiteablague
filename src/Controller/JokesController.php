<?php
// src/Controller/JokesController.php
namespace App\Controller;

use App\Entity\Jokes;
use App\Entity\Users;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\QueryBuilder;
use mysql_xdevapi\Exception;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use phpDocumentor\Reflection\DocBlock\Tags\Author;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class JokesController extends AbstractController
{

    /**
     * @Route("jokes/list/{page}", name="jokes_list", requirements={"page"="\d+"}, defaults={"page": 0, "filterType": "date", "filterValue": "ASC"})
     */
    public function list(int $page, string $filterType, string $filterValue)
    {

        $entityManager = $this->getDoctrine()->getManager();

        if ($filterType == "date" && in_array($filterValue, ['ASC', 'DESC'])) {
            $qb = $entityManager->createQueryBuilder('jokes');
            $qb
                ->select('j')
                ->from('App\Entity\Jokes', 'j')
                ->orderBy('j.date', $filterValue)
                ->setFirstResult($page)
                ->setMaxResults(10);
        } elseif ($filterType == "author" && is_int($filterValue)) {


            $qb = $entityManager->createQueryBuilder('jokes');
            $qb
                ->select('j')
                ->from('App\Entity\Jokes', 'j')
                ->join('App\Entity\Users', 'u')
                ->where('u.Author = ' . $filterValue)
                ->setFirstResult($page)
                ->setMaxResults(10);
        } elseif ($filterType == "likes" && in_array($filterValue, ['ASC', 'DESC'])) {
            $qb = $entityManager->createQueryBuilder('jokes');
            $qb
                ->select('j')
                ->from('App\Entity\Jokes', 'j')
                ->join('App\Entity\Likes', 'l')
                ->orderBy('COUNT(l.likes)', $filterValue)
                ->setFirstResult($page)
                ->setMaxResults(10);
        } else {
            $qb = $entityManager->createQueryBuilder('jokes');
            $qb
                ->select('j')
                ->from('App\Entity\Jokes', 'j')
                ->setFirstResult($page)
                ->setMaxResults(10);
        }


        $result = new Paginator($qb);

        $pageCount = count($result);

        $jokes = [];

        foreach ($result as $joke) {

            $joke = [
                'text' => $joke->getText(),
                'authorName' => $joke->getAuthor()->getName() . " " . $joke->getAuthor()->getFirstname(),
                'authorId' => $joke->getAuthor()->getId(),
                'date' => $joke->getDate()
            ];

            array_push($jokes, $joke);
        }

        $jokesData = ['pageTotal' => $pageCount, 'currentPage' => $page + 1, 'jokes' => $jokes];

        return new JsonResponse($jokesData);
    }

    /**
     * @Route("jokes/{id}", name="jokes_id", requirements={"id"="\d+"}, defaults={"id": 1})
     */
    public function id(int $id)
    {
        $joke = $this->getDoctrine()
            ->getRepository(Jokes::class)
            ->find($id);


        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                return $object->getId();
            },
        ];


        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer(null, null, null, null, null, null, $defaultContext)];
        $serializer = new Serializer($normalizers, $encoders);


        if (isset($joke)) {

            $json_joke = $serializer->serialize($joke, 'json');

            return new Response(
                $json_joke
            );
        } else {

            return new JsonResponse(
                ['error' => 'Not exist']
            );
        }

    }


    /**
     * @Route("jokes/random")
     */
    public function random()
    {

        $entityManager = $this->getDoctrine()->getManager();


        $qb = $entityManager->createQueryBuilder();
        $qb->select('count(j.id)');
        $qb->from('App\Entity\Jokes', 'j');

        $count = $qb->getQuery()->getSingleScalarResult();

        $random = rand(1, $count-1);

        $joke = $this->getDoctrine()
            ->getRepository(Jokes::class)
            ->findBy([], [], 1, $random);


        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                return $object->getId();
            },
        ];


        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer(null, null, null, null, null, null, $defaultContext)];
        $serializer = new Serializer($normalizers, $encoders);


        $json_joke = $serializer->serialize($joke, 'json');

        return new Response(
            $json_joke
        );

    }

    /**
     * @Route("jokes/add", name="jokes_add", methods={"POST"})
     */
    public function add(Request $request, ValidatorInterface $validator)
    {
        $contentRAW = $request->getContent();
        $content = json_decode($contentRAW, true);
        $text = $content['text'];
        $authorId = $content['authorId'];
        $date = new \DateTime($content['date']);

        $entityManager = $this->getDoctrine()->getManager();


        try {
            $author = $this->getDoctrine()
                ->getRepository(Users::class)
                ->find($authorId);
        } catch (\Exception $e) {
            return new Response((string)$e, 500);
        }


        if (isset($author)) {

            $joke = new Jokes();
            $joke->setText($text);
            $joke->setAuthor($author);
            $joke->setDate($date);

            $errors = $validator->validate($joke);
            if (count($errors) > 0) {
                return new Response((string)$errors, 400);
            }

            $entityManager->persist($joke);
            $entityManager->flush();

            $userId = $joke->getId();


            return new JsonResponse(
                ['id' => $userId]
            );

        } else {
            return new JsonResponse(
                ['error' => 'Author unknowed']
            );
        }


    }
}
