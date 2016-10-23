<?php
/**
 * This file is part of the he8us/feedback package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace He8us\FeedbackBundle\Controller;

use He8us\FeedbackBundle\Entity\Feedback;
use He8us\FeedbackBundle\Form\Type\FeedbackWithCaptcha;
use He8us\FeedbackBundle\Service\FeedbackService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class FeedbackController
 *
 * @package He8us\FeedbackBundle\Controller
 * @author Cedric Michaux <cedric@he8us.be>
 */
class FeedbackController extends Controller
{
    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function newAction(Request $request): JsonResponse
    {
        $feedback = new Feedback();
        $form = $this->createForm(FeedbackWithCaptcha::class, $feedback);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->persistFeedback($request, $feedback);
            $this->sendMail($feedback);

            return JsonResponse::create([
                'status' => true,
            ]);
        }

        return JsonResponse::create([
            'error'  => true,
            'errors' => (string) $form->getErrors(true, false),
        ], 500);
    }

    /**
     * @param Request  $request
     * @param Feedback $feedback
     */
    private function persistFeedback(Request $request, Feedback $feedback)
    {
        /** @var FeedbackService $feedbackService */
        $feedbackService = $this->get('he8us_feedback.feedback_service');
        $feedbackService->createFeedback($feedback, $request);
    }

    /**
     * @param Feedback $feedback
     */
    private function sendMail(Feedback $feedback)
    {
        if (
            !$this->container->hasParameter('feedback_email')
            || !$this->container->hasParameter('system_email')
            || !$this->container->hasParameter('project_name')
        ) {
            return;
        }

        $translator = $this->get('translator');
        $message = $this->get('mailer')->createMessage();
        $message = $message
            ->setSubject($translator->trans('feedback.feedback.received'))
            ->addFrom($this->getParameter('system_email'), $this->getParameter('project_name'))
            ->setTo($this->getParameter('feedback_email'), $this->getParameter('project_name'))
            ->setBody($feedback->getBody(), 'text/html');

        $this->get('mailer')->send($message);
    }
}
