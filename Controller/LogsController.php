<?php

namespace LogBundle\Controller;

use JMS\Serializer\SerializationContext;
use CoreBundle\Traits\JsonResponseControllerTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\VarDumper\VarDumper;

/**
 * @Route("rco")
 */
class LogsController extends Controller
{
    use JsonResponseControllerTrait;

    /**
     * @Route("/logs/view", name="service_logs", options={"expose"=true} )
     * @Method("POST")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logsAction(Request $request)
    {
        $requestData = json_decode($request->getContent());
        $identifier  = isset($requestData->id) ? intval($requestData->id) : null;

        if (empty($identifier) && empty($requestData->type)) {
            return $this->renderBadRequestResponse();
        }


        if (!is_array($requestData->type)) {
            $requestData->type = (array)$requestData->type;
        }
        $type = array_map('strval', $requestData->type);


        try {
            $records = $this->get('maksym.logger')->getLogs($identifier, $type);
        } catch (\RuntimeException $exception) {
            return $this->renderBadRequestResponse();
        }

        $context = new SerializationContext();
        $context->setSerializeNull(true);

        return $this->renderJsonResponse('OK', 200, [
            'logs' =>
                $this->get('jms_serializer')->serialize($records, 'json', $context)
        ]);
    }


}
