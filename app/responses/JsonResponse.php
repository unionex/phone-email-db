<?php /** @noinspection PhpInconsistentReturnPointsInspection */

namespace App\Responses;

use App\Helpers\ArrayHelper;

class JsonResponse extends Response implements ResponseInterface
{
    /**
     * @param mixed $result
     * @param bool $error
     * @param array $additionalMeta
     */
    public function send($result, $error = false, $additionalMeta = [])
    {
        /** @var \Phalcon\Http\ResponseInterface $response */
        $response = $this->getDI()->getShared('response');

        $eTag = md5(json_encode($result));

        $meta = ArrayHelper::merge(
            [
                'e-tag' => $eTag,
                'status' => $error == false ? "SUCCESS" : "ERROR"
            ],
            $additionalMeta
        );

        $result = [
            'meta' => $meta,
            'result' => $result
        ];

        $response->setHeader('E-Tag', $eTag);
        $response->setContentType("application/json");
        $response->setJsonContent($result);

        $response->send();
    }
}