<?php declare(strict_types = 1);

namespace App\Controller;

use App\Repository\Criteria;
use App\Repository\EntryCommentRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\EntryCommentManager;
use App\DTO\EntryCommentDto;
use App\Entity\EntryComment;
use App\Form\CommentType;
use App\Entity\Magazine;
use App\Entity\Entry;

class EntryCommentController extends AbstractController
{
    private EntryCommentManager $commentManager;
    private EntityManagerInterface $entityManager;

    public function __construct(EntryCommentManager $commentManager, EntityManagerInterface $entityManager)
    {
        $this->commentManager = $commentManager;
        $this->entityManager  = $entityManager;
    }

    /**
     * @ParamConverter("magazine", options={"mapping": {"magazine_name": "name"}})
     * @ParamConverter("entry", options={"mapping": {"entry_id": "id"}})
     * @ParamConverter("comment", options={"mapping": {"comment_id": "id"}})
     *
     * @IsGranted("ROLE_USER")
     * @IsGranted("comment", subject="entry")
     */
    public function createComment(Magazine $magazine, Entry $entry, ?EntryComment $comment, Request $request): Response
    {
        $commentDto = new EntryCommentDto();
        $commentDto->setEntry($entry);

        $form = $this->createForm(CommentType::class, $commentDto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commentManager->createComment($commentDto, $this->getUserOrThrow());
        }

        return $this->redirectToRoute(
            'entry',
            [
                'magazine_name' => $magazine->getName(),
                'entry_id'      => $entry->getId(),
            ]
        );
    }

    /**
     * @ParamConverter("magazine", options={"mapping": {"magazine_name": "name"}})
     * @ParamConverter("entry", options={"mapping": {"entry_id": "id"}})
     * @ParamConverter("comment", options={"mapping": {"comment_id": "id"}})
     *
     * @IsGranted("ROLE_USER")
     * @IsGranted("edit", subject="comment")
     */
    public function editComment(
        Magazine $magazine,
        Entry $entry,
        EntryComment $comment,
        Request $request,
        EntryCommentRepository $commentRepository
    ): Response {
        $commentDto = $this->commentManager->createCommentDto($comment);

        $form = $this->createForm(CommentType::class, $commentDto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commentManager->editComment($comment, $commentDto);

            return $this->redirectToRoute(
                'entry',
                [
                    'magazine_name' => $magazine->getName(),
                    'entry_id'      => $entry->getId(),
                ]
            );
        }

        $criteria = (new Criteria((int) $request->get('strona', 1)))
            ->setEntry($entry);

        $comments = $commentRepository->findByCriteria($criteria);

        return $this->render(
            'entry/comment/edit.html.twig',
            [
                'magazine' => $magazine,
                'entry'    => $entry,
                'comments' => $comments,
                'comment'  => $comment,
                'form'     => $form->createView(),
            ]
        );
    }

    /**
     * @ParamConverter("magazine", options={"mapping": {"magazine_name": "name"}})
     * @ParamConverter("entry", options={"mapping": {"entry_id": "id"}})
     * @ParamConverter("comment", options={"mapping": {"comment_id": "id"}})
     *
     * @IsGranted("ROLE_USER")
     * @IsGranted("purge", subject="comment")
     */
    public function purgeComment(Magazine $magazine, Entry $entry, EntryComment $comment, Request $request): Response
    {
        $this->validateCsrf('entry_comment_purge', $request->request->get('token'));

        $this->commentManager->purgeComment($comment);

        return $this->redirectToRoute(
            'entry',
            [
                'magazine_name' => $magazine->getName(),
                'entry_id'      => $entry->getId(),
            ]
        );
    }

    public function commentForm(string $magazineName, int $entryId, int $commentId = null): Response
    {
        $routeParams = [
            'magazine_name' => $magazineName,
            'entry_id'      => $entryId,
        ];

        if ($commentId !== null) {
            $routeParams['comment_id'] = $commentId;
        }

        $form = $this->createForm(CommentType::class, null, ['action' => $this->generateUrl('entry_comment_create', $routeParams)]);

        return $this->render(
            'entry/comment/_form.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }
}