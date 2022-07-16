<?php declare(strict_types=1);

namespace App\Controller\ActivityPub;

use App\Entity\Magazine;
use App\Entity\Post;
use App\Factory\ActivityPub\PostNoteFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PostController
{
    public function __construct(private PostNoteFactory $postNoteFactory)
    {
    }

    #[ParamConverter('magazine', options: ['mapping' => ['magazine_name' => 'name']])]
    #[ParamConverter('post', options: ['mapping' => ['post_id' => 'id']])]
    public function __invoke(
        Magazine $magazine,
        Post $post,
        Request $request
    ): Response {
        $response = new JsonResponse($this->postNoteFactory->create($post));

        $response->headers->set('Content-Type', 'application/activity+json');

        return $response;
    }
}